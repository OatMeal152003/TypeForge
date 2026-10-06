<?php
// TypeForge REST v1 — LeaderboardService.
// Extracted from leaderboard.php so page + API share one query path.

class V1_LeaderboardService {
    public static function get(PDO $db, array $filters): array {
        $metric = $filters['metric'] ?? 'net_wpm';
        if (!in_array($metric, ['net_wpm', 'raw_wpm', 'accuracy'], true)) $metric = 'net_wpm';
        $mode = $filters['mode'] ?? 'all';
        if (!in_array($mode, ['all', 'words', 'time', 'quote', 'code', 'custom'], true)) $mode = 'all';
        $length = isset($filters['length']) && $filters['length'] !== '' ? (int)$filters['length'] : null;
        $punctuation = array_key_exists('punctuation', $filters) && $filters['punctuation'] !== '' && $filters['punctuation'] !== null ? (int)(bool)$filters['punctuation'] : null;
        $numbers = array_key_exists('numbers', $filters) && $filters['numbers'] !== '' && $filters['numbers'] !== null ? (int)(bool)$filters['numbers'] : null;
        $timeframe = $filters['timeframe'] ?? 'all_time';
        if (!in_array($timeframe, ['all_time', 'monthly', 'daily'], true)) $timeframe = 'all_time';

        $where = ['1=1'];
        $params = [];
        if ($mode !== 'all') { $where[] = 'tr.mode = :mode'; $params[':mode'] = $mode; }
        if ($length !== null && $length > 0) { $where[] = 'tr.mode_value = :length'; $params[':length'] = $length; }
        if ($punctuation !== null) { $where[] = 'tr.has_punctuation = :punct'; $params[':punct'] = $punctuation; }
        if ($numbers !== null) { $where[] = 'tr.has_numbers = :num'; $params[':num'] = $numbers; }
        if ($timeframe === 'monthly') { $where[] = 'tr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'; }
        elseif ($timeframe === 'daily') { $where[] = 'tr.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)'; }
        $where_sql = implode(' AND ', $where);

        switch ($metric) {
            case 'raw_wpm':
                $score_select = 'MAX(tr.raw_wpm) AS primary_score';
                $order_sql = 'primary_score DESC, MAX(tr.wpm) DESC, AVG(tr.accuracy) DESC';
                $metric_col = 'raw_wpm';
                break;
            case 'accuracy':
                $score_select = 'MAX(tr.accuracy) AS primary_score';
                $order_sql = 'primary_score DESC, MAX(tr.wpm) DESC, MAX(tr.raw_wpm) DESC';
                $metric_col = 'accuracy';
                break;
            default:
                $metric = 'net_wpm';
                $score_select = 'MAX(tr.wpm) AS primary_score';
                $order_sql = 'primary_score DESC, MAX(tr.raw_wpm) DESC, AVG(tr.accuracy) DESC';
                $metric_col = 'wpm';
                break;
        }

        $stmt = $db->prepare("
            SELECT u.id AS user_id, u.username, u.full_name,
                {$score_select},
                MAX(tr.wpm) AS top_wpm, MAX(tr.raw_wpm) AS top_raw_wpm,
                MAX(tr.accuracy) AS peak_accuracy, AVG(tr.accuracy) AS avg_accuracy,
                COUNT(tr.id) AS tests_completed, MAX(tr.created_at) AS last_active
            FROM typing_sessions tr
            JOIN users u ON tr.user_id = u.id
            WHERE {$where_sql}
            GROUP BY u.id, u.username, u.full_name
            ORDER BY {$order_sql}
            LIMIT 50
        ");
        $stmt->execute($params);
        $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['metric' => $metric, 'metric_col' => $metric_col, 'rankings' => $rankings,
                'filters' => ['mode' => $mode, 'length' => $length, 'punctuation' => $punctuation, 'numbers' => $numbers, 'timeframe' => $timeframe]];
    }
}
