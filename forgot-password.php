<?php
// TypeForge — password reset via single-use token link.
// Local/XAMPP build: the reset link is shown on screen after requesting.
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$db = Database::getConnection();
$error = '';
$info = '';
$reset_link = '';
$token = trim((string)($_GET['token'] ?? ''));
$step = 'request';

if ($token !== '') {
    $stmt = $db->prepare("SELECT r.id, r.user_id, u.email FROM password_resets r JOIN users u ON u.id = r.user_id WHERE r.token_hash = :h AND r.used = 0 AND r.expires_at > NOW() AND u.status = 'active' LIMIT 1");
    $stmt->execute([':h' => hash('sha256', $token)]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reset) {
        $error = 'This reset link is invalid or expired. Request a new one below.';
        $token = '';
    } else {
        $step = 'reset';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } elseif ($step === 'reset') {
        $pw1 = (string)($_POST['new_password'] ?? '');
        $pw2 = (string)($_POST['confirm_password'] ?? '');
        if (strlen($pw1) < 8) {
            $error = 'New password must be at least 8 characters long.';
        } elseif ($pw1 !== $pw2) {
            $error = 'Passwords do not match.';
        } else {
            $db->beginTransaction();
            $db->prepare("UPDATE users SET password_hash = :h WHERE id = :id")->execute([':h' => password_hash($pw1, PASSWORD_DEFAULT), ':id' => $reset['user_id']]);
            $db->prepare("UPDATE password_resets SET used = 1 WHERE id = :id")->execute([':id' => $reset['id']]);
            $db->prepare("DELETE FROM login_tokens WHERE user_id = :uid")->execute([':uid' => $reset['user_id']]);
            $db->prepare("DELETE FROM login_attempts WHERE username = :u")->execute([':u' => strtolower((string)$reset['email'])]);
            $db->commit();
            $info = 'Password reset successfully. You can now sign in.';
            $step = 'done';
        }
    } else {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(:e) AND status = 'active' LIMIT 1");
            $stmt->execute([':e' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $raw = bin2hex(random_bytes(32));
                $db->prepare("UPDATE password_resets SET used = 1 WHERE user_id = :uid AND used = 0")->execute([':uid' => $user['id']]);
                $db->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:uid, :h, DATE_ADD(NOW(), INTERVAL 1 HOUR))")->execute([':uid' => $user['id'], ':h' => hash('sha256', $raw)]);
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $reset_link = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/') . '/forgot-password.php?token=' . $raw;
            }
            $info = 'If an account exists for that email, a reset link was generated below (valid 1 hour).';
        }
    }
}

$csrf_token = generateCsrfToken();
$page_title = 'Reset Password — TypeForge';
$hide_navbar = true;
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-full auth-full-login">
    <div class="auth-shade min-h-screen w-full flex items-center justify-center px-4 sm:px-6 py-12">
        <div class="w-full max-w-[400px] auth-enter-up">
            <div class="text-center mb-6">
                <div class="mx-auto w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-4">
                    <i class="fa-solid fa-key text-xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-white tracking-tight">Reset password</h2>
                <p class="mt-2 text-xs text-gray-400"><?= $step === 'reset' ? 'Choose a new password below.' : 'Enter your account email to get a reset link.' ?></p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-xl text-xs flex items-center gap-2 mb-4">
                    <i class="fa-solid fa-circle-exclamation"></i><span><?= sanitize($error) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($info): ?>
                <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-xl text-xs mb-4"><?= sanitize($info) ?></div>
            <?php endif; ?>
            <?php if ($reset_link): ?>
                <div class="bg-dark-800 border border-dark-600 p-4 rounded-xl text-xs mb-4 break-all">
                    <p class="text-gray-400 mb-1 font-mono">Your reset link (local dev — normally emailed):</p>
                    <a href="<?= sanitize($reset_link) ?>" class="text-emerald-400 font-mono hover:underline"><?= sanitize($reset_link) ?></a>
                </div>
            <?php endif; ?>

            <?php if ($step === 'request' && !$reset_link): ?>
            <form class="space-y-4" method="POST">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token) ?>">
                <div>
                    <label for="email" class="block text-xs font-medium text-gray-300 mb-1">Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-500 text-xs"><i class="fa-solid fa-envelope"></i></span>
                        <input id="email" name="email" type="email" required
                               class="auth-pill w-full pl-11 pr-4 py-3 bg-dark-800 border border-dark-600 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-emerald-500 transition"
                               placeholder="name@example.com">
                    </div>
                </div>
                <button type="submit" class="auth-pill auth-btn-shadow w-full py-3 px-4 text-sm font-semibold text-dark-900 bg-emerald-400 hover:bg-emerald-300 transition flex items-center justify-center gap-2">
                    Send reset link <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>
            <?php elseif ($step === 'reset'): ?>
            <form class="space-y-4" method="POST" action="forgot-password.php?token=<?= sanitize($token) ?>">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token) ?>">
                <div>
                    <label for="new_password" class="block text-xs font-medium text-gray-300 mb-1">New Password (Min 8 Characters)</label>
                    <input id="new_password" name="new_password" type="password" required minlength="8"
                           class="auth-pill w-full px-5 py-3 bg-dark-800 border border-dark-600 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-emerald-500 transition"
                           placeholder="••••••••">
                </div>
                <div>
                    <label for="confirm_password" class="block text-xs font-medium text-gray-300 mb-1">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" required minlength="8"
                           class="auth-pill w-full px-5 py-3 bg-dark-800 border border-dark-600 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-emerald-500 transition"
                           placeholder="••••••••">
                </div>
                <button type="submit" class="auth-pill auth-btn-shadow w-full py-3 px-4 text-sm font-semibold text-dark-900 bg-emerald-400 hover:bg-emerald-300 transition flex items-center justify-center gap-2">
                    Set new password <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>
            <?php endif; ?>

            <p class="text-center text-xs text-gray-400 mt-6">
                <a href="login.php" class="text-emerald-400 font-semibold hover:underline">Back to sign in</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
