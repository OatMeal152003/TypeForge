<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

 
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please log in.']);
    exit();
}

// Admins manage the system and cannot submit player scores.
if (($_SESSION['user_role'] ?? 'user') === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin accounts cannot submit typing results.']);
    exit();
}

 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit();
}

 
$input_raw = file_get_contents('php://input');
$data = json_decode($input_raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload received.']);
    exit();
}

 
$csrf_token = $data['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid or expired CSRF token.']);
    exit();
}

 
$user_id = getCurrentUserId();

 
$allowed_modes = ['words', 'time', 'quote', 'code', 'invaders', 'blocks', 'meteors', 'rush'];
$game_modes = ['invaders', 'blocks', 'meteors', 'rush'];
$raw_mode = strtolower(trim($data['mode'] ?? 'words'));
$mode = in_array($raw_mode, $allowed_modes, true) ? $raw_mode : 'words';
$is_game = in_array($mode, $game_modes, true);

$mode_value       = max(1, min((int)($data['mode_value'] ?? 0), 3600));
$has_punctuation  = !empty($data['has_punctuation']) ? 1 : 0;
$has_numbers      = !empty($data['has_numbers']) ? 1 : 0;
$duration_seconds = max(1, min((int)($data['duration_seconds'] ?? 0), 3600));
$total_keystrokes = max(0, min((int)($data['total_keystrokes'] ?? 0), 20000));
$error_count      = max(0, min((int)($data['error_count'] ?? 0), 20000));

 
if ($mode === 'words') {
    $mode_value = max(5, min($mode_value, 500));
} elseif ($mode === 'time') {
    $mode_value = max(5, min($mode_value, 600));
} elseif ($mode === 'invaders' || $mode === 'blocks' || $mode === 'meteors' || $mode === 'rush') {
    $mode_value = max(1, min($mode_value, 3600));
}

// Arcade score: server-clamped to keystroke math so scripts cannot farm it.
$game_score = null;
if ($is_game) {
    $game_score = max(0, min((int)($data['game_score'] ?? 0), 99999, $total_keystrokes * 5));
}

 
if ($total_keystrokes === 0 || $duration_seconds === 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Unprocessable test metrics. Keystrokes or duration cannot be zero.']);
    exit();
}

if ($error_count > $total_keystrokes) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Unprocessable test metrics. Error count exceeds keystrokes.']);
    exit();
}

 
$now_ts = time();
if (isset($_SESSION['last_result_save']) && ($now_ts - (int)$_SESSION['last_result_save']) < 3) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many submissions. Please wait a moment.']);
    exit();
}

 
$chars_per_sec = $total_keystrokes / max(1, $duration_seconds);
if ($chars_per_sec > 40) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Unprocessable test metrics. Typing speed is not plausible.']);
    exit();
}

 
$correct_chars_server = max(0, $total_keystrokes - $error_count);
$server_raw = (int)round(($total_keystrokes / 5.0) / ($duration_seconds / 60.0));
$server_wpm = (int)calculateNetWpm($total_keystrokes, $error_count, (float)$duration_seconds);
$server_accuracy = (float)calculateAccuracy($correct_chars_server, $total_keystrokes);

$server_raw = max(0, min($server_raw, 400));
$server_wpm = max(0, min($server_wpm, 350));

 
$wpm         = $server_wpm;
$raw_wpm     = $server_raw;
$accuracy    = $server_accuracy;

$db = Database::getConnection();

// Optional curated-text linkage (validated against FK).
$text_id = (int)($data['text_id'] ?? 0);
if ($text_id <= 0) {
    $text_id = null;
} else {
    $stmt_t = $db->prepare("SELECT 1 FROM typing_texts WHERE id = :id LIMIT 1");
    $stmt_t->execute([':id' => $text_id]);
    if (!$stmt_t->fetchColumn()) $text_id = null;
}

try {
    $db->beginTransaction();

    $correct_chars = max(0, $total_keystrokes - $error_count);

     
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
        'wpm'              => $wpm,
        'raw_wpm'          => $raw_wpm,
        'game_score'       => $game_score,
        'accuracy'         => $accuracy,
        'duration_seconds' => $duration_seconds,
        'total_chars'      => $total_keystrokes,
        'correct_chars'    => $correct_chars,
        'incorrect_chars'  => $error_count,
        'corrected_errors' => 0
    ]);

    $result_id = (int)$db->lastInsertId();

     
    $stmt_pb = $db->prepare("
        SELECT MAX(wpm) as highest_wpm 
        FROM typing_sessions 
        WHERE user_id = :user_id AND id != :result_id
    ");
    $stmt_pb->execute([
        'user_id'   => $user_id,
        'result_id' => $result_id
    ]);
    $previous_best = (int)($stmt_pb->fetch(PDO::FETCH_ASSOC)['highest_wpm'] ?? 0);

    $is_new_pb = ($wpm > $previous_best);

    $db->commit();

    $_SESSION['last_result_save'] = time();

    // Achievement pipeline: server-side grants, never blocking the save.
    $new_achievements = [];
    try {
        require_once __DIR__ . '/../includes/achievements.php';
        $new_achievements = tf_check_achievements($db, (int)$user_id);
    } catch (Throwable $e) { error_log('Achievements hook: ' . $e->getMessage()); }

    echo json_encode([
        'success'    => true,
        'message'    => 'Test result recorded successfully.',
        'result_id'  => $result_id,
        'is_new_pb'  => $is_new_pb,
        'game_score' => $game_score,
        'new_achievements' => $new_achievements,
        'metrics'    => [
            'wpm'      => $wpm,
            'raw_wpm'  => $raw_wpm,
            'accuracy' => $accuracy
        ]
    ]);
    exit();

} catch (Exception $e) {
    $db->rollBack();
    error_log("Result Persistence Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save test result due to a database error.']);
    exit();
}