<?php
declare(strict_types=1);
// TypeForge achievement pipeline — server-side only, never trust client metrics.
// Evaluated from persisted typing_sessions after every accepted save.

// Lifetime player stats backing every criteria type.
function tf_achievement_stats(PDO $db, int $user_id): array {
    $stmt = $db->prepare("SELECT COUNT(*) AS c, COALESCE(MAX(wpm), 0) AS bw, COALESCE(MAX(accuracy), 0) AS ba, COALESCE(MAX(CASE WHEN mode = 'invaders' THEN game_score ELSE 0 END), 0) AS bi, COALESCE(MAX(CASE WHEN mode = 'blocks' THEN game_score ELSE 0 END), 0) AS bb FROM typing_sessions WHERE user_id = :u");
    $stmt->execute([':u' => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Current daily streak: consecutive test days ending today or yesterday.
    $streak = 0;
    try {
        $stmt_d = $db->prepare("SELECT DISTINCT DATE(created_at) AS d FROM typing_sessions WHERE user_id = :u ORDER BY d DESC LIMIT 400");
        $stmt_d->execute([':u' => $user_id]);
        $days = $stmt_d->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $cursor = new DateTime('today');
        foreach ($days as $day) {
            if ($day === $cursor->format('Y-m-d')) {
                $streak++;
                $cursor->modify('-1 day');
            } elseif ($day < $cursor->format('Y-m-d')) {
                break;
            }
        }
    } catch (Throwable $e) { $streak = 0; }

    return [
        'tests' => (int)($row['c'] ?? 0),
        'best_wpm' => (float)($row['bw'] ?? 0),
        'best_acc' => (float)($row['ba'] ?? 0),
        'best_inv' => (float)($row['bi'] ?? 0),
        'best_bl' => (float)($row['bb'] ?? 0),
        'streak' => $streak,
    ];
}

// Progress 0.0-1.0 toward an achievement row. Unknown criteria fail closed at 0.
function tf_achievement_progress(array $stats, array $ach): float {
    $target = (float)($ach['criteria_value'] ?? 0);
    if ($target <= 0) return 0.0;
    $have = match ($ach['criteria_type'] ?? '') {
        'tests_count' => (float)$stats['tests'],
        'wpm_reach' => (float)$stats['best_wpm'],
        'accuracy_reach' => (float)$stats['best_acc'],
        'streak_days' => (float)$stats['streak'],
        'invaders_score' => (float)($stats['best_inv'] ?? 0),
        'blocks_score' => (float)($stats['best_bl'] ?? 0),
        default => 0.0,
    };
    return max(0.0, min(1.0, $have / $target));
}

function tf_achievement_label(array $stats, array $ach): string {
    $target = (float)($ach['criteria_value'] ?? 0);
    return match ($ach['criteria_type'] ?? '') {
        'tests_count' => min($stats['tests'], (int)$target) . ' / ' . (int)$target . ' tests',
        'wpm_reach' => min($stats['best_wpm'], $target) . ' / ' . $target . ' WPM',
        'accuracy_reach' => min($stats['best_acc'], $target) . ' / ' . $target . '%',
        'streak_days' => min($stats['streak'], (int)$target) . ' / ' . (int)$target . ' day streak',
        'invaders_score' => min($stats['best_inv'] ?? 0, $target) . ' / ' . $target . ' invader pts',
        'blocks_score' => min($stats['best_bl'] ?? 0, $target) . ' / ' . $target . ' block pts',
        default => 'Locked',
    };
}

// Grants everything newly earned. Returns full rows of fresh unlocks only.
// Safe to call after every save: granted rows are skipped, races use INSERT IGNORE.
function tf_check_achievements(PDO $db, int $user_id): array {
    try {
        $defs = $db->query("SELECT id, code, title, description, icon, criteria_type, criteria_value, points FROM achievements WHERE is_active = 1 ORDER BY points ASC")->fetchAll(PDO::FETCH_ASSOC);
        if (!$defs) return [];
        $stmt_g = $db->prepare("SELECT achievement_id FROM user_achievements WHERE user_id = :u");
        $stmt_g->execute([':u' => $user_id]);
        $owned = array_flip(array_map('intval', $stmt_g->fetchAll(PDO::FETCH_COLUMN) ?: []));
        $stats = tf_achievement_stats($db, $user_id);
        $stmt_ins = $db->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (:u, :a)");
        $fresh = [];
        foreach ($defs as $ach) {
            $aid = (int)$ach['id'];
            if (isset($owned[$aid])) continue;
            if (tf_achievement_progress($stats, $ach) >= 1.0) {
                $stmt_ins->execute([':u' => $user_id, ':a' => $aid]);
                if ($stmt_ins->rowCount() > 0) $fresh[] = $ach;
            }
        }
        return $fresh;
    } catch (Throwable $e) {
        error_log('Achievement pipeline exception: ' . $e->getMessage());
        return [];
    }
}

// Full shelf for display: every achievement + earned flag, date, progress.
function tf_user_achievements(PDO $db, int $user_id): array {
    $defs = $db->query("SELECT id, code, title, description, icon, criteria_type, criteria_value, points, is_active FROM achievements WHERE is_active = 1 ORDER BY points ASC")->fetchAll(PDO::FETCH_ASSOC);
    if (!$defs) return [];
    $stmt_g = $db->prepare("SELECT achievement_id, unlocked_at FROM user_achievements WHERE user_id = :u");
    $stmt_g->execute([':u' => $user_id]);
    $grants = [];
    foreach ($stmt_g->fetchAll(PDO::FETCH_ASSOC) as $g) { $grants[(int)$g['achievement_id']] = $g['unlocked_at']; }
    $stats = tf_achievement_stats($db, $user_id);
    $total_points = 0;
    foreach ($defs as &$ach) {
        $aid = (int)$ach['id'];
        $ach['earned'] = isset($grants[$aid]);
        $ach['unlocked_at'] = $grants[$aid] ?? null;
        $ach['progress'] = $ach['earned'] ? 1.0 : tf_achievement_progress($stats, $ach);
        $ach['progress_label'] = $ach['earned'] ? 'Unlocked' : tf_achievement_label($stats, $ach);
        if ($ach['earned']) $total_points += (int)$ach['points'];
    }
    unset($ach);
    return ['items' => $defs, 'points' => $total_points, 'stats' => $stats];
}
