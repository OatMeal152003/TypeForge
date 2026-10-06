<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$username_or_email = '';

 
$raw_redirect = trim($_GET['redirect'] ?? 'dashboard.php');
$redirect_url = 'dashboard.php';
if (!empty($raw_redirect)) {
     
    $parsed = parse_url($raw_redirect);
    if (empty($parsed['scheme']) && empty($parsed['host']) && !str_starts_with($raw_redirect, '//')) {
        $clean_path = ltrim($raw_redirect, '/');
         
        $allowed_pages = ['dashboard.php', 'typing-test.php', 'leaderboard.php', 'history.php', 'settings.php', 'statistics.php', 'admin/dashboard.php', 'admin/users.php'];
        $base_file = explode('?', $clean_path)[0];
        // Accept both "admin/dashboard.php" and "TypeForge/admin/dashboard.php" (subfolder deploy).
        $rel = preg_replace('#^(?:[^/]+/)*(admin/.+|[^/]+\.php)$#', '$1', $base_file);
        if (in_array($base_file, $allowed_pages, true) || in_array($rel, $allowed_pages, true)) {
            $redirect_url = $clean_path;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username_or_email = trim($_POST['username_or_email'] ?? '');
        $password          = $_POST['password'] ?? '';

        if (empty($username_or_email) || empty($password)) {
            $error = 'Please enter both your login credential and password.';
        } else {
            $db = Database::getConnection();

            // Brute-force throttle: 5 failures per IP+credential in 15 min → lockout.
            $throttle_ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $throttle_key = strtolower(trim($username_or_email));
            $stmt_th = $db->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip = :ip AND username = :u LIMIT 1");
            $stmt_th->execute([':ip' => $throttle_ip, ':u' => substr($throttle_key, 0, 150)]);
            $throttled = false;
            if ($row_th = $stmt_th->fetch(PDO::FETCH_ASSOC)) {
                if ((int)$row_th['attempts'] >= 5 && (time() - strtotime((string)$row_th['last_attempt'])) < 900) {
                    $throttled = true;
                }
            }
            if ($throttled) {
                $error = 'Too many failed attempts. Try again in 15 minutes.';
            } else {

            $login_value = strtolower(trim($username_or_email));

            $stmt = $db->prepare("
                SELECT id, full_name, username, email, password_hash, role, status 
                FROM users 
                WHERE (LOWER(username) = LOWER(:username_or_email) OR LOWER(email) = LOWER(:email))
                LIMIT 1
            ");
            $stmt->execute([
                ':username_or_email' => $login_value,
                ':email' => $login_value
            ]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] !== 'active') {
                    $error = 'Your account is suspended or inactive. Contact support.';
                } else {
                     
                    session_regenerate_id(true);

                     
                    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                        try {
                            $new_hash = password_hash($password, PASSWORD_DEFAULT);
                            $stmt_rehash = $db->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
                            $stmt_rehash->execute([':h' => $new_hash, ':id' => $user['id']]);
                        } catch (Exception $e) {
                             
                        }
                    }

                    $_SESSION['user_id']       = (int)$user['id'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_email']    = $user['email'];
                    $_SESSION['user_name']     = $user['full_name'];
                    $_SESSION['user_role']     = $user['role'];
                    $_SESSION['last_activity'] = time();

                    // Remember me: 30-day secure token (rotated on each autologin).
                    if (!empty($_POST['remember_me'])) {
                        $remember_raw = bin2hex(random_bytes(32));
                        $stmt_tok = $db->prepare("INSERT INTO login_tokens (user_id, token_hash, expires_at) VALUES (:uid, :h, DATE_ADD(NOW(), INTERVAL 30 DAY))");
                        $stmt_tok->execute([':uid' => (int)$user['id'], ':h' => hash('sha256', $remember_raw)]);
                        $rm_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
                        setcookie('tf_remember', $remember_raw, ['expires' => time() + 30 * 86400, 'path' => '/', 'secure' => $rm_secure, 'httponly' => true, 'samesite' => 'Lax']);
                    }

                    // Admins manage the system: land in the console, never the player area.
                    if ($user['role'] === 'admin' && strpos($redirect_url, 'admin/') === false) {
                        $redirect_url = 'admin/dashboard.php';
                    }

                    // Success: clear throttle counter.
                    $stmt_clr = $db->prepare("DELETE FROM login_attempts WHERE ip = :ip AND username = :u");
                    $stmt_clr->execute([':ip' => $throttle_ip, ':u' => substr($throttle_key, 0, 150)]);

                    require_once __DIR__ . '/includes/auth-transition.php';
                    renderAuthTransition($redirect_url, 'Signing you in', 'Loading your dashboard...');
                }
            } else {
                // Failure: record attempt (upsert).
                $stmt_up = $db->prepare("INSERT INTO login_attempts (ip, username, attempts) VALUES (:ip, :u, 1) ON DUPLICATE KEY UPDATE attempts = IF(last_attempt < DATE_SUB(NOW(), INTERVAL 15 MINUTE), 1, attempts + 1)");
                $stmt_up->execute([':ip' => $throttle_ip, ':u' => substr($throttle_key, 0, 150)]);
                $error = 'Invalid credentials provided.';
            }
            } // end throttle-else
        }
    }
}

$csrf_token = generateCsrfToken();
$page_title = 'Sign In — TypeForge';
$hide_navbar = true;
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-full auth-full-login">
    <div class="auth-shade min-h-screen w-full flex items-center justify-center lg:justify-start px-4 sm:px-6 lg:pl-[450px] lg:pr-6 py-12">
        <div class="w-full max-w-[400px] auth-enter-up auth-card">
        <div class="text-center mb-6">
            <div class="mx-auto w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-4">
                <i class="fa-solid fa-right-to-bracket text-xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Welcome back</h2>
            <p class="mt-2 text-xs text-gray-400">Enter your credentials to access your TypeForge dashboard.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-xl text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= sanitize($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['expired'])): ?>
            <div class="bg-amber-500/10 border border-amber-500/30 text-amber-400 p-4 rounded-xl text-xs flex items-center gap-2">
                <i class="fa-solid fa-clock font-normal"></i>
                <span>Session expired due to inactivity. Please sign in again.</span>
            </div>
        <?php endif; ?>

        <form class="mt-6 space-y-4" action="login.php?redirect=<?= urlencode($redirect_url) ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token) ?>">

            <div>
                <label for="username_or_email" class="block text-xs font-medium text-gray-300 mb-1">Username or Email</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-xs">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input id="username_or_email" name="username_or_email" type="text" required value="<?= sanitize($username_or_email) ?>"
                           class="auth-pill w-full pl-11 pr-4 py-3 bg-dark-800 border border-dark-600 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-emerald-500 transition"
                           placeholder="username or email">
                </div>
            </div>

            <div>
                <label for="password" class="block text-xs font-medium text-gray-300 mb-1">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-xs">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input id="password" name="password" type="password" required
                           class="auth-pill w-full pl-11 pr-11 py-3 bg-dark-800 border border-dark-600 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-emerald-500 transition"
                           placeholder="••••••••">
                    <button type="button" id="toggle-password" title="Show password" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-emerald-400 transition">
                        <i class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 text-gray-400 cursor-pointer select-none">
                    <input type="checkbox" name="remember_me" value="1" class="rounded border-dark-600 text-emerald-400 focus:ring-0">
                    Remember me
                </label>
                <a href="forgot-password.php" class="text-emerald-400 font-semibold hover:underline">Forgot password?</a>
            </div>

            <button type="submit" class="auth-pill auth-btn-shadow w-full mt-2 py-3 px-4 text-sm font-semibold text-dark-900 bg-emerald-400 hover:bg-emerald-300 transition flex items-center justify-center gap-2">
                Sign In <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
        </form>

        <p class="text-center text-xs text-gray-400 mt-6">
            Don't have an account?
            <a href="register.php" class="text-emerald-400 font-semibold hover:underline">Register now</a>
        </p>
        </div>
    </div>
</div>

<script>
document.getElementById('toggle-password').addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon = this.querySelector('i');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    icon.className = show ? 'fa-solid fa-eye-slash text-xs' : 'fa-solid fa-eye text-xs';
    this.title = show ? 'Hide password' : 'Show password';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>