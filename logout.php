<?php

require_once __DIR__ . '/includes/functions.php';

 
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) {
    header('Location: dashboard.php?logout=invalid_token');
    exit();
}

$_SESSION = [];

// Revoke remember-me token.
if (!empty($_COOKIE['tf_remember'])) {
    try {
        require_once __DIR__ . '/config/database.php';
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM login_tokens WHERE token_hash = :h");
        $stmt->execute([':h' => hash('sha256', (string)$_COOKIE['tf_remember'])]);
    } catch (Exception $e) {
    }
    setcookie('tf_remember', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
}

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header('Location: index.php?logged_out=1');
exit();