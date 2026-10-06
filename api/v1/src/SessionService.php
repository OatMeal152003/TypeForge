<?php
// TypeForge REST v1 — SessionService.
// Validates + persists typing sessions. Mirrors api/save-result.php guards.

class V1_SessionService {
    public static function create(PDO $db, int $user_id, array $data): array {
        $allowed_modes = ['words', 'time', 'quote', 'code', 'invaders', 'blocks', 'meteors', 'rush'];
        $game_modes = ['invaders', 'blocks', 'meteors', 'rush'];
        $raw_mode = strtolower(trim((string)($data['mode'] ?? 'words')));
        $mode = in_array($raw_mode, $allowed_modes, true) ? $raw_mode : 'words';
        $is_game = in_array($mode, $game_modes, true);

        $mode_value       = max(1, min((int)($data['mode_value'] ?? 0), 3600));
        $has_punctuation  = !empty($data['has_punctuation']) ? 1 : 0;
        $has_numbers      = !empty($data['has_numbers']) ? 1 : 0;
        $text_id          = (int)($data['text_id'] ?? 0);
        if ($text_id <= 0) $text_id = null;
        if ($text_id !== null) {
            // Guard the FK: ignore forged ids instead of 500ing.
            $stmt_t = $db->prepare("SELECT 1 FROM typing_texts WHERE id = :id LIMIT 1");
            $stmt_t->execute([':id' => $text_id]);
            if (!$stmt_t->fetchColumn()) $text_id = null;
        }        $duration_seconds = max(1, min((int)($data['duration_seconds'] ?? 0), 3600));
        $total_keystrokes = max(0, min((int)($data['total_keystrokes'] ?? 0), 20000));
        $error_count      = max(0, min((int)($data['error_count'] ?? 0), 20000));

        if ($mode === 'words') {
            $mode_value = max(5, min($mode_value, 500));
        } elseif ($mode === 'time') {
            $mode_value = max(5, min($mode_value, 600));
        } elseif ($mode === 'invaders' || $mode === 'blocks' || $mode === 'meteors' || $mode === 'rush') {
            $mode_value = max(1, min($mode_value, 3600));
        }

        if ($total_keystrokes === 0 || $duration_seconds === 0) {
            throw new InvalidArgumentException('Unprocessable test metrics. Keystrokes or duration cannot be zero.', 422);
        }
        if ($error_count > $total_keystrokes) {
            throw new InvalidArgumentException('Unprocessable test metrics. Error count exceeds keystrokes.', 422);
        }
        $cps = $total_keystrokes / max(1, $duration_seconds);
        if ($cps > 40) {
            throw new InvalidArgumentException('Unprocessable test metrics. Typing speed is not plausible.', 422);
        }

        $correct_chars_server = max(0, $total_keystrokes - $error_count);
        $server_raw = (int)round(($total_keystrokes / 5.0) / ($duration_seconds / 60.0));
        $server_wpm = (int)calculateNetWpm($total_keystrokes, $error_count, (float)$duration_seconds);
        $server_accuracy = (float)calculateAccuracy($correct_chars_server, $total_keystrokes);
        $server_raw = max(0, min($server_raw, 400));
        $server_wpm = max(0, min($server_wpm, 350));

        // Arcade score clamped to keystroke math so scripts cannot farm it.
        $game_score = null;
        if ($is_game) {
            $game_score = max(0, min((int)($data['game_score'] ?? 0), 99999, $total_keystrokes * 5));
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                INSERT INTO typing_sessions
                    (user_id, text_id, mode, mode_value, has_punctuation, has_numbers, wpm, raw_wpm, game_score, accuracy, duration_seconds, total_chars, correct_chars, incorrect_chars, corrected_errors, created_at)
                VALUES
                    (:user_id, :text_id, :mode, :mode_value, :has_punctuation, :has_numbers, :wpm, :raw_wpm, :game_score, :accuracy, :duration_seconds, :total_chars, :correct_chars, :incorrect_chars, :corrected_errors, NOW())
            ");
            $stmt->execute([
                'user_id'          => $user_id,
                'text_id'          => $text_id,
                'mode'             => $mode,
                'mode_value'       => $mode_value,
                'has_punctuation'  => $has_punctuation,
                'has_numbers'      => $has_numbers,
                'wpm'              => $server_wpm,
                'raw_wpm'          => $server_raw,
                'game_score'       => $game_score,
                'accuracy'         => $server_accuracy,
                'duration_seconds' => $duration_seconds,
                'total_chars'      => $total_keystrokes,
                'correct_chars'    => $correct_chars_server,
                'incorrect_chars'  => $error_count,
                'corrected_errors' => 0,
            ]);
            $result_id = (int)$db->lastInsertId();

            $stmt_pb = $db->prepare("SELECT MAX(wpm) as highest_wpm FROM typing_sessions WHERE user_id = :user_id AND id != :result_id");
            $stmt_pb->execute(['user_id' => $user_id, 'result_id' => $result_id]);
            $previous_best = (int)(($stmt_pb->fetch(PDO::FETCH_ASSOC)['highest_wpm'] ?? 0));
            $is_new_pb = ($server_wpm > $previous_best);

            $db->commit();
            $_SESSION['last_result_save'] = time();

            // Achievement pipeline: server-side grants, never blocking the save.
            $new_achievements = [];
            try {
                if (!function_exists('tf_check_achievements')) {
                    require_once __DIR__ . '/../../../includes/achievements.php';
                }
                $new_achievements = tf_check_achievements($db, (int)$user_id);
            } catch (Throwable $e) { error_log('Achievements hook: ' . $e->getMessage()); }

            return [
                'result_id' => $result_id,
                'is_new_pb' => $is_new_pb,
                'game_score' => $game_score,
                'new_achievements' => $new_achievements,
                'metrics'   => ['wpm' => $server_wpm, 'raw_wpm' => $server_raw, 'accuracy' => $server_accuracy],
            ];
        } catch (Exception $e) {
            $db->rollBack();
            error_log("V1 SessionService Exception: " . $e->getMessage());
            throw new RuntimeException('Failed to save test result due to a database error.', 500);
        }
    }
}
