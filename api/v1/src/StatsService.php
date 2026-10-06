<?php
// TypeForge REST v1 — StatsService.
// Powers dashboard.php / statistics.php / history.php via API for deployable clients.

class V1_StatsService {
    public static function summary(PDO $db, int $user_id): array {
        $stmt = $db->prepare("
            SELECT COUNT(id) AS total_tests, COALESCE(MAX(wpm),0) AS best_wpm,
                   COALESCE(ROUND(AVG(wpm),1),0) AS avg_wpm,
                   COALESCE(ROUND(AVG(accuracy),1),0) AS avg_accuracy,
                   COALESCE(SUM(duration_seconds),0) AS total_time_seconds
            FROM typing_sessions WHERE user_id = :uid
        ");
        $stmt->execute(['uid' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public static function recent(PDO $db, int $user_id, int $limit = 5): array {
        $limit = max(1, min($limit, 50));
        $stmt = $db->prepare("
            SELECT id, mode, wpm, raw_wpm, accuracy, duration_seconds, created_at
            FROM typing_sessions WHERE user_id = :uid ORDER BY created_at DESC LIMIT {$limit}
        ");
        $stmt->execute(['uid' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function history(PDO $db, int $user_id, int $limit = 50): array {
        $limit = max(1, min($limit, 100));
        $stmt = $db->prepare("
            SELECT wpm, raw_wpm, accuracy, created_at FROM typing_sessions
            WHERE user_id = :uid ORDER BY created_at ASC LIMIT {$limit}
        ");
        $stmt->execute(['uid' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function paged(PDO $db, int $user_id, int $page = 1, int $limit = 15): array {
        $page = max(1, $page);
        $limit = max(1, min($limit, 50));
        $offset = ($page - 1) * $limit;
        $stmt_count = $db->prepare("SELECT COUNT(id) FROM typing_sessions WHERE user_id = :uid");
        $stmt_count->execute(['uid' => $user_id]);
        $total = (int)$stmt_count->fetchColumn();
        $stmt = $db->prepare("
            SELECT id, mode, wpm, raw_wpm, accuracy, duration_seconds,
                   total_chars AS total_keystrokes, incorrect_chars AS error_count, created_at
            FROM typing_sessions WHERE user_id = :uid
            ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute(['uid' => $user_id]);
        return ['items' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'page' => $page, 'limit' => $limit,
                'total' => $total, 'total_pages' => max(1, (int)ceil($total / $limit))];
    }

    public static function getOne(PDO $db, int $user_id, int $id): ?array {
        $stmt = $db->prepare("SELECT * FROM typing_sessions WHERE id = :id AND user_id = :uid LIMIT 1");
        $stmt->execute([':id' => $id, ':uid' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
