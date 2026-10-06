<?php
// TypeForge REST v1 — SettingsService.
// Split of api/update-settings.php action-switch into resource methods.

class V1_SettingsService {
    public static function me(PDO $db, int $user_id): array {
        $stmt = $db->prepare("SELECT full_name, username, email, profile_picture AS avatar_url, created_at FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $stmt2 = $db->prepare("SELECT * FROM user_settings WHERE user_id = :uid LIMIT 1");
        $stmt2->execute([':uid' => $user_id]);
        $settings = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];
        return ['user' => $user, 'settings' => $settings];
    }

    public static function updateProfile(PDO $db, int $user_id, array $data, ?array $file = null): void {
        $full_name  = trim((string)($data['full_name'] ?? ''));
        $username   = trim((string)($data['username'] ?? ''));

        if ($full_name === '' || strlen($full_name) < 2) {
            throw new InvalidArgumentException('Full Name must be at least 2 characters long.', 422);
        }
        if ($username === '' || !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            throw new InvalidArgumentException('Username must be 3-20 characters and contain only letters, numbers, and underscores.', 422);
        }

        $stmt_cur = $db->prepare("SELECT profile_picture FROM users WHERE id = :user_id LIMIT 1");
        $stmt_cur->execute([':user_id' => $user_id]);
        $avatar_url = (string)(($stmt_cur->fetch(PDO::FETCH_ASSOC)['profile_picture'] ?? ''));

        // Device upload wins; avatar_url kept only for API compat.
        if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            require_once __DIR__ . '/AvatarUpload.php';
            $avatar_url = V1_AvatarUpload::store($file, $avatar_url);
        } elseif (!empty($data['avatar_url'])) {
            $candidate = trim((string)$data['avatar_url']);
            if (!filter_var($candidate, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $candidate)) {
                throw new InvalidArgumentException('Avatar URL must be a valid HTTP or HTTPS address.', 422);
            }
            $avatar_url = $candidate;
        }
        $stmt_check = $db->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(:username) AND id != :user_id");
        $stmt_check->execute([':username' => $username, ':user_id' => $user_id]);
        if ($stmt_check->fetch()) {
            throw new InvalidArgumentException('Username is already taken by another account.', 409);
        }
        $stmt = $db->prepare("UPDATE users SET full_name = :full_name, username = :username, profile_picture = :avatar_url WHERE id = :user_id");
        $stmt->execute([':full_name' => $full_name, ':username' => $username, ':avatar_url' => $avatar_url, ':user_id' => $user_id]);
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_username'] = $username;
    }

    public static function updatePreferences(PDO $db, int $user_id, array $data): void {
        // Trimmed to real user_settings columns (theme, sound_enabled).
        $allowed_themes = ['dark', 'light', 'matrix', 'cyberpunk', 'monokai', 'dracula', 'nord'];
        $theme = strtolower(trim((string)($data['theme'] ?? 'dark')));
        if (!in_array($theme, $allowed_themes, true)) $theme = 'dark';
        $sound_enabled = !empty($data['sound_enabled']) ? 1 : 0;

        $stmt = $db->prepare("
            INSERT INTO user_settings (user_id, theme, sound_enabled)
            VALUES (:user_id, :theme, :sound_enabled)
            ON DUPLICATE KEY UPDATE
                theme = VALUES(theme),
                sound_enabled = VALUES(sound_enabled)
        ");
        $stmt->execute([
            ':user_id' => $user_id, ':theme' => $theme, ':sound_enabled' => $sound_enabled,
        ]);
    }

    public static function changePassword(PDO $db, int $user_id, array $data): void {
        $current_password = (string)($data['current_password'] ?? '');
        $new_password = (string)($data['new_password'] ?? '');
        if (strlen($new_password) < 8) {
            throw new InvalidArgumentException('New password must be at least 8 characters long.', 422);
        }
        $stmt_user = $db->prepare("SELECT password_hash FROM users WHERE id = :user_id");
        $stmt_user->execute([':user_id' => $user_id]);
        $row = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify($current_password, $row['password_hash'])) {
            throw new InvalidArgumentException('Current password is incorrect.', 400);
        }
        $stmt_pass = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :user_id");
        $stmt_pass->execute([':hash' => password_hash($new_password, PASSWORD_DEFAULT), ':user_id' => $user_id]);
        // Password changed: revoke all remember-me tokens.
        $db->prepare("DELETE FROM login_tokens WHERE user_id = :uid")->execute([':uid' => $user_id]);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
