<?php

require_once __DIR__ . '/functions.php';
if (!class_exists('Database')) {
    $db_file = __DIR__ . '/../config/database.php';
    if (file_exists($db_file)) {
        require_once $db_file;
    }
}

function tf_base(): string {
    // Project-root URL base (e.g. "/TypeForge" on XAMPP, "" when deployed at domain root).
    $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $pos = strpos($script, '/admin/');
    if ($pos !== false) return rtrim(substr($script, 0, $pos), '/');
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    return ($dir === '' || $dir === '.') ? '' : $dir;
}

function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: ' . tf_base() . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }

     
    try {
        if (class_exists('Database')) {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT status FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => (int)$_SESSION['user_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || ($row['status'] ?? '') !== 'active') {
                session_unset();
                session_destroy();
                header('Location: ' . tf_base() . '/login.php?expired=1');
                exit();
            }
        }
    } catch (Exception $e) {
         
    }
    
     
    $maxInactivity = 3600;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $maxInactivity)) {
        session_unset();
        session_destroy();
        header('Location: ' . tf_base() . '/login.php?expired=1');
        exit();
    }
    $_SESSION['last_activity'] = time();
}

function requireLogin(): void {
    requireAuth();
}

function requirePlayer(): void {
    // Player-only area: admins manage the system and cannot play.
    requireAuth();
    if (($_SESSION['user_role'] ?? 'user') === 'admin') {
        header('Location: ' . tf_base() . '/admin/dashboard.php?msg=' . urlencode('Admins manage the system — player area is for user accounts.'));
        exit();
    }
}

function requireAdmin(): void {
    requireAuth();
    if ($_SESSION['user_role'] !== 'admin') {
        http_response_code(403);
        die('Access Denied: Administrator privileges required.');
    }
}

function isLoggedIn(): bool {
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) return true;
    return tryRememberLogin();
}

// Silent autologin via remember-me cookie (once per request).
function tryRememberLogin(): bool {
    static $tried = false;
    if ($tried) return false;
    $tried = true;
    if (empty($_COOKIE['tf_remember']) || !class_exists('Database')) return false;
    try {
        $db = Database::getConnection();
        $hash = hash('sha256', (string)$_COOKIE['tf_remember']);
        $stmt = $db->prepare("SELECT t.user_id, u.username, u.email, u.full_name, u.role, u.status FROM login_tokens t JOIN users u ON u.id = t.user_id WHERE t.token_hash = :h AND t.expires_at > NOW() LIMIT 1");
        $stmt->execute([':h' => $hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || ($row['status'] ?? '') !== 'active') return false;

        session_regenerate_id(true);
        $_SESSION['user_id']       = (int)$row['user_id'];
        $_SESSION['user_username'] = $row['username'];
        $_SESSION['user_email']    = $row['email'];
        $_SESSION['user_name']     = $row['full_name'];
        $_SESSION['user_role']     = $row['role'];
        $_SESSION['last_activity'] = time();

        // Rotate: single-use tokens.
        $db->prepare("DELETE FROM login_tokens WHERE token_hash = :h")->execute([':h' => $hash]);
        $raw = bin2hex(random_bytes(32));
        $stmt_new = $db->prepare("INSERT INTO login_tokens (user_id, token_hash, expires_at) VALUES (:uid, :h, DATE_ADD(NOW(), INTERVAL 30 DAY))");
        $stmt_new->execute([':uid' => (int)$row['user_id'], ':h' => hash('sha256', $raw)]);
        $rm_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        setcookie('tf_remember', $raw, ['expires' => time() + 30 * 86400, 'path' => '/', 'secure' => $rm_secure, 'httponly' => true, 'samesite' => 'Lax']);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;

    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['user_username'] ?? '',
        'email'    => $_SESSION['user_email'] ?? '',
        'name'     => $_SESSION['user_name'] ?? '',
        'role'     => $_SESSION['user_role'] ?? 'user'
    ];
}

function getCurrentUserId(): ?int {
    if (!isLoggedIn()) {
        return null;
    }

    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}