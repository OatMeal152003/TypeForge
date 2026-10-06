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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit();
}

$input_raw = file_get_contents('php://input');
$data = json_decode($input_raw, true);
// Multipart device-upload posts populate $_POST instead of JSON.
if (!$data && !empty($_POST)) $data = $_POST;

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
$db = Database::getConnection();
$raw_action = strtolower(trim($data['action'] ?? 'update_preferences'));
$allowed_actions = ['update_profile', 'update_preferences', 'change_password'];
$action = in_array($raw_action, $allowed_actions, true) ? $raw_action : 'invalid';

try {
    if ($action === 'update_profile') {
        require_once __DIR__ . '/v1/src/SettingsService.php';
        try {
            V1_SettingsService::updateProfile($db, $user_id, $data, $_FILES['avatar'] ?? null);
        } catch (InvalidArgumentException $e) {
            http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 422);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        }

        $_SESSION['username'] = $_SESSION['user_username'];

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
        exit();

    } elseif ($action === 'update_preferences') {
        $allowed_themes = ['dark', 'light', 'matrix', 'cyberpunk', 'monokai', 'dracula', 'nord'];

        $theme = strtolower(trim($data['theme'] ?? 'dark'));
        if (!in_array($theme, $allowed_themes, true)) {
            $theme = 'dark';
        }

         
        $sound_enabled = !empty($data['sound_enabled']) ? 1 : 0;

         
        $stmt = $db->prepare("
            INSERT INTO user_settings (user_id, theme, sound_enabled)
            VALUES (:user_id, :theme, :sound_enabled)
            ON DUPLICATE KEY UPDATE 
                theme = VALUES(theme),
                sound_enabled = VALUES(sound_enabled)
        ");

        $stmt->execute([
            ':user_id'       => $user_id,
            ':theme'         => $theme,
            ':sound_enabled' => $sound_enabled
        ]);

        echo json_encode(['success' => true, 'message' => 'Preferences updated successfully.']);
        exit();

    } elseif ($action === 'change_password') {
        $current_password = $data['current_password'] ?? '';
        $new_password     = $data['new_password'] ?? '';

        if (strlen($new_password) < 8) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'New password must be at least 8 characters long.']);
            exit();
        }

         
        $stmt_user = $db->prepare("SELECT password_hash FROM users WHERE id = :user_id");
        $stmt_user->execute([':user_id' => $user_id]);
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if (!$user_data || !password_verify($current_password, $user_data['password_hash'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Current password is incorrect.']);
            exit();
        }

        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt_pass = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :user_id");
        $stmt_pass->execute([':hash' => $new_hash, ':user_id' => $user_id]);
        $db->prepare("DELETE FROM login_tokens WHERE user_id = :uid")->execute([':uid' => $user_id]);

         
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
        exit();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action requested.']);
        exit();
    }

} catch (Exception $e) {
    error_log("Settings Update Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error occurred while saving settings.']);
    exit();
}