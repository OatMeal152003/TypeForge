<?php
// TypeForge Admin — moderation + curation actions. POST + CSRF + requireAdmin only.
// Every operation is written to admin_audit_log.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed.');
}
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $back = (string)($_POST['back'] ?? 'users.php');
    if (!in_array($back, ['users.php', 'texts.php', 'achievements.php'], true)) $back = 'users.php';
    header('Location: ' . $back . '?err=' . urlencode('Invalid CSRF token.'));
    exit();
}

$op = (string)($_POST['op'] ?? '');
$me = getCurrentUserId();
$db = Database::getConnection();

function audit(PDO $db, int $admin_id, string $action, string $target_type, int $target_id, string $detail = ''): void {
    $stmt = $db->prepare("INSERT INTO admin_audit_log (admin_id, action, target_type, target_id, detail) VALUES (:a, :op, :t, :id, :d)");
    $stmt->execute([':a' => $admin_id, ':op' => $action, ':t' => $target_type, ':id' => $target_id, ':d' => substr($detail, 0, 255)]);
}

// --- Achievement ops (add / activate / deactivate / revoke grant; never delete) ---
if (in_array($op, ['ach_add', 'ach_activate', 'ach_deactivate', 'ach_revoke'], true)) {
    if ($op === 'ach_add') {
        $code = strtoupper(trim((string)($_POST['code'] ?? '')));
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $icon = strtolower(trim((string)($_POST['icon'] ?? 'fa-medal')));
        $criteria_type = strtolower(trim((string)($_POST['criteria_type'] ?? '')));
        $criteria_value = (float)($_POST['criteria_value'] ?? 0);
        $points = max(1, min((int)($_POST['points'] ?? 10), 1000));
        if (!preg_match('/^[A-Z0-9_]{3,30}$/', $code)) {
            header('Location: achievements.php?err=' . urlencode('Code must be 3-30 chars: A-Z, 0-9, underscores.'));
            exit();
        }
        if ($title === '' || $description === '') {
            header('Location: achievements.php?err=' . urlencode('Title and description are required.'));
            exit();
        }
        if (!preg_match('/^fa-[a-z0-9-]+$/', $icon)) $icon = 'fa-medal';
        if (!in_array($criteria_type, ['tests_count', 'wpm_reach', 'accuracy_reach', 'streak_days', 'invaders_score', 'blocks_score'], true)) {
            header('Location: achievements.php?err=' . urlencode('Invalid criteria type.'));
            exit();
        }
        if ($criteria_value <= 0 || $criteria_value > 100000) {
            header('Location: achievements.php?err=' . urlencode('Criteria value must be between 1 and 100000.'));
            exit();
        }
        try {
            $stmt = $db->prepare("INSERT INTO achievements (code, title, description, icon, criteria_type, criteria_value, points, is_active) VALUES (:c, :t, :d, :i, :ct, :cv, :p, 1)");
            $stmt->execute([':c' => $code, ':t' => substr($title, 0, 100), ':d' => substr($description, 0, 255), ':i' => $icon, ':ct' => $criteria_type, ':cv' => $criteria_value, ':p' => $points]);
        } catch (PDOException $e) {
            header('Location: achievements.php?err=' . urlencode('Code already exists.'));
            exit();
        }
        $new_id = (int)$db->lastInsertId();
        audit($db, (int)$me, 'ach_add', 'achievement', $new_id, "$code: $title");
        header('Location: achievements.php?msg=' . urlencode("Achievement $code added and live."));
        exit();
    }
    if ($op === 'ach_revoke') {
        $ach_id = (int)($_POST['achievement_id'] ?? 0);
        $grant_user = (int)($_POST['grant_user_id'] ?? 0);
        if ($ach_id <= 0 || $grant_user <= 0) {
            header('Location: achievements.php?err=' . urlencode('Invalid achievement or user.'));
            exit();
        }
        $stmt = $db->prepare("DELETE FROM user_achievements WHERE user_id = :u AND achievement_id = :a LIMIT 1");
        $stmt->execute([':u' => $grant_user, ':a' => $ach_id]);
        audit($db, (int)$me, 'ach_revoke', 'achievement', $ach_id, "user #$grant_user");
        header('Location: achievements.php?msg=' . urlencode('Grant revoked; the pipeline may re-award it on the next qualifying save.'));
        exit();
    }
    $ach_id = (int)($_POST['achievement_id'] ?? 0);
    if ($ach_id <= 0) {
        header('Location: achievements.php?err=' . urlencode('Invalid achievement id.'));
        exit();
    }
    $active = $op === 'ach_activate' ? 1 : 0;
    $stmt = $db->prepare("UPDATE achievements SET is_active = :a WHERE id = :id");
    $stmt->execute([':a' => $active, ':id' => $ach_id]);
    audit($db, (int)$me, $op, 'achievement', $ach_id, $active ? 'activated' : 'deactivated');
    header('Location: achievements.php?msg=' . urlencode("Achievement #$ach_id " . ($active ? 'activated.' : 'deactivated. Earned grants are preserved.')));
    exit();
}

// --- Typing text curation ops ---
if (in_array($op, ['text_activate', 'text_deactivate', 'text_add'], true)) {
    if ($op === 'text_add') {
        $title = trim((string)($_POST['title'] ?? ''));
        $category = strtolower(trim((string)($_POST['category'] ?? '')));
        $difficulty = strtolower(trim((string)($_POST['difficulty'] ?? 'intermediate')));
        $content = trim((string)($_POST['content'] ?? ''));
        if ($title === '' || $content === '') {
            header('Location: texts.php?err=' . urlencode('Title and content are required.'));
            exit();
        }
        if (!in_array($category, ['words', 'quote', 'code', 'custom'], true)) $category = 'custom';
        if (!in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true)) $difficulty = 'intermediate';
        if (strlen($content) > 5000) {
            header('Location: texts.php?err=' . urlencode('Content too long (max 5000 chars).'));
            exit();
        }
        $words = max(1, count(preg_split('/\s+/', $content)));
        $stmt = $db->prepare("INSERT INTO typing_texts (category, difficulty, title, content, length_words, is_active) VALUES (:c, :d, :t, :x, :w, 1)");
        $stmt->execute([':c' => $category, ':d' => $difficulty, ':t' => substr($title, 0, 150), ':x' => $content, ':w' => $words]);
        $new_id = (int)$db->lastInsertId();
        audit($db, (int)$me, 'text_add', 'text', $new_id, "$category/$difficulty: $title");
        header('Location: texts.php?msg=' . urlencode("Text #$new_id added to rotation."));
        exit();
    }
    $text_id = (int)($_POST['text_id'] ?? 0);
    if ($text_id <= 0) {
        header('Location: texts.php?err=' . urlencode('Invalid text id.'));
        exit();
    }
    $active = $op === 'text_activate' ? 1 : 0;
    $stmt = $db->prepare("UPDATE typing_texts SET is_active = :a WHERE id = :id");
    $stmt->execute([':a' => $active, ':id' => $text_id]);
    audit($db, (int)$me, $op, 'text', $text_id, $active ? 'activated' : 'deactivated');
    header('Location: texts.php?msg=' . urlencode("Text #$text_id " . ($active ? 'activated.' : 'deactivated.')));
    exit();
}

// --- User moderation ops ---
$target = (int)($_POST['user_id'] ?? 0);

if ($target <= 0 || $target === (int)$me) {
    header('Location: users.php?err=' . urlencode('Refused: cannot moderate your own account.'));
    exit();
}
if (!in_array($op, ['suspend', 'activate', 'ban', 'promote', 'demote'], true)) {
    header('Location: users.php?err=' . urlencode('Unknown operation.'));
    exit();
}

$map = [
    'suspend' => "UPDATE users SET status='suspended' WHERE id=:id",
    'activate' => "UPDATE users SET status='active' WHERE id=:id",
    'ban' => "UPDATE users SET status='banned' WHERE id=:id",
    'promote' => "UPDATE users SET role='admin' WHERE id=:id",
    'demote' => "UPDATE users SET role='user' WHERE id=:id",
];

// Guard: never demote the last active admin.
if ($op === 'demote') {
    $admins = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active'")->fetchColumn();
    $stmt_t = $db->prepare("SELECT role FROM users WHERE id=:id");
    $stmt_t->execute([':id' => $target]);
    if ($stmt_t->fetchColumn() === 'admin' && $admins <= 1) {
        header('Location: users.php?err=' . urlencode('Refused: cannot demote the last active admin.'));
        exit();
    }
}

$stmt = $db->prepare($map[$op]);
$stmt->execute([':id' => $target]);
audit($db, (int)$me, $op, 'user', $target);

header('Location: users.php?msg=' . urlencode("User #$target: $op applied."));
exit();
