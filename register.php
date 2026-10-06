<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$success = '';
$full_name = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($token)) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $full_name = trim((string)($_POST['full_name'] ?? ''));
        $username  = strtolower(trim((string)($_POST['username'] ?? '')));
        $email     = strtolower(trim((string)($_POST['email'] ?? '')));
        $password  = (string)($_POST['password'] ?? '');
        $confirm   = (string)($_POST['confirm_password'] ?? '');

        if (empty($full_name) || strlen($full_name) < 2) {
            $errors[] = 'Full Name must be at least 2 characters long.';
        }

        if (empty($username) || !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $errors[] = 'Username must be 3-20 characters long and contain only letters, numbers, and underscores.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $db = Database::getConnection();

            $stmt = $db->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
            $stmt->execute(['username' => $username, 'email' => $email]);

            if ($stmt->fetch()) {
                $errors[] = 'Username or Email is already registered.';
            } else {
                try {
                    $db->beginTransaction();

                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $db->prepare("INSERT INTO users (full_name, username, email, password_hash, role, status)
                        VALUES (:full_name, :username, :email, :password_hash, 'user', 'active')");
                    $stmt->execute([
                        'full_name'     => $full_name,
                        'username'      => $username,
                        'email'         => $email,
                        'password_hash' => $password_hash,
                    ]);

                    $user_id = (int)$db->lastInsertId();

                    $stmt_settings = $db->prepare("INSERT INTO user_settings (user_id) VALUES (:user_id)");
                    $stmt_settings->execute(['user_id' => $user_id]);

                    $db->commit();

                     
                    session_regenerate_id(true);

                    $_SESSION['user_id']       = $user_id;
                    $_SESSION['user_username'] = $username;
                    $_SESSION['user_email']    = $email;
                    $_SESSION['user_name']     = $full_name;
                    $_SESSION['user_role']     = 'user';
                    $_SESSION['last_activity'] = time();

                    require_once __DIR__ . '/includes/auth-transition.php';
                    renderAuthTransition('dashboard.php?registered=1', 'Creating your account', 'Setting up your dashboard...');
                } catch (Exception $e) {
                    $db->rollBack();
                    error_log('Registration Exception: ' . $e->getMessage());
                    $errors[] = 'An error occurred during account creation. Please try again.';
                }
            }
        }
    }
}

$csrf_token = generateCsrfToken();
$page_title = 'Create Account — TypeForge';
$hide_navbar = true;
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-full auth-full-register">
    <div class="auth-shade min-h-screen w-full flex items-center justify-center lg:justify-end px-4 sm:px-6 lg:pr-[450px] lg:pl-6 py-12">
        <div class="w-full max-w-[400px] auth-enter-down auth-card">
        <div class="text-center mb-6">
            <div class="mx-auto w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-4">
                <i class="fa-solid fa-user-plus text-xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Create your account</h2>
            <p class="mt-2 text-xs text-gray-400">Join TypeForge and start tracking your typing progress.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-xl text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= sanitize(implode(' ', $errors)) ?></span>
            </div>
        <?php endif; ?>

        <form class="mt-6 space-y-4" action="register.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token) ?>">

            <div>
                <label for="full_name" class="block text-xs font-medium text-gray-300 mb-1">Full Name</label>
                <input id="full_name" name="full_name" type="text" required value="<?= sanitize($full_name) ?>"
                       class="auth-pill w-full px-5 py-3 bg-dark-800 border border-dark-600 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-emerald-500 transition"
                       placeholder="Jane Doe">
            </div>

            <div>
                <label for="username" class="block text-xs font-medium text-gray-300 mb-1">Username</label>
                <input id="username" name="username" type="text" required value="<?= sanitize($username) ?>"
                       class="auth-pill w-full px-5 py-3 bg-dark-800 border border-dark-600 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-emerald-500 transition"
                       placeholder="jane_doe">
            </div>

            <div>
                <label for="email" class="block text-xs font-medium text-gray-300 mb-1">Email</label>
                <input id="email" name="email" type="email" required value="<?= sanitize($email) ?>"
                       class="auth-pill w-full px-5 py-3 bg-dark-800 border border-dark-600 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-emerald-500 transition"
                       placeholder="name@example.com">
            </div>

            <div>
                <label for="password" class="block text-xs font-medium text-gray-300 mb-1">Password</label>
                <div class="relative">
                    <input id="password" name="password" type="password" required
                           class="auth-pill w-full px-5 py-3 pr-12 bg-dark-800 border border-dark-600 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-emerald-500 transition"
                           placeholder="••••••••">
                    <button type="button" id="toggle-password" title="Show password" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-emerald-400 transition">
                        <i class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
            </div>

            <div>
                <label for="confirm_password" class="block text-xs font-medium text-gray-300 mb-1">Confirm Password</label>
                <div class="relative">
                    <input id="confirm_password" name="confirm_password" type="password" required
                           class="auth-pill w-full px-5 py-3 pr-12 bg-dark-800 border border-dark-600 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-emerald-500 transition"
                           placeholder="••••••••">
                    <button type="button" id="toggle-confirm" title="Show password" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-emerald-400 transition">
                        <i class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="auth-pill auth-btn-shadow w-full mt-2 py-3 px-4 text-sm font-semibold text-dark-900 bg-emerald-400 hover:bg-emerald-300 transition flex items-center justify-center gap-2">
                Create Account <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
        </form>

        <p class="text-center text-xs text-gray-400 mt-6">
            Already have an account?
            <a href="login.php" class="text-emerald-400 font-semibold hover:underline">Sign in</a>
        </p>
        </div>
    </div>
</div>

<script>
function bindToggle(btnId, inputId) {
    document.getElementById(btnId).addEventListener('click', function () {
        const input = document.getElementById(inputId);
        const icon = this.querySelector('i');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.className = show ? 'fa-solid fa-eye-slash text-xs' : 'fa-solid fa-eye text-xs';
        this.title = show ? 'Hide password' : 'Show password';
    });
}
bindToggle('toggle-password', 'password');
bindToggle('toggle-confirm', 'confirm_password');
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>