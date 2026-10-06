<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$user_id = getCurrentUserId();
$db = Database::getConnection();

 
$stmt_user = $db->prepare("SELECT full_name, username, email, profile_picture AS avatar_url, created_at FROM users WHERE id = :id");
$stmt_user->execute([':id' => $user_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC) ?: [];

$csrf_token = generateCsrfToken();
$page_title = 'Settings — TypeForge';
require_once __DIR__ . '/includes/header.php';

$default_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name'] ?: $user['username'] ?: 'User') . '&background=10b981&color=0d1117&bold=true';
$display_avatar = !empty($user['avatar_url']) ? $user['avatar_url'] : $default_avatar;
?>

<div class="flex min-h-screen bg-dark-900 text-gray-200">
    
    <?php
    // Role-aware sidebar: admins keep the admin console navigation everywhere.
    if (($_SESSION['user_role'] ?? 'user') === 'admin') {
        $base_prefix = '';
        $admin_prefix = 'admin/';
        require_once __DIR__ . '/includes/admin_sidebar.php';
    } else {
        require_once __DIR__ . '/includes/sidebar.php';
    }
    ?>

    
    <main class="flex-1 p-6 md:p-10 space-y-8 overflow-y-auto">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Account Settings</h1>
            <p class="text-xs text-gray-400 mt-1">Manage your profile details and security settings</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="space-y-6">
                <div class="glass-panel p-6 rounded-2xl border border-dark-700 flex flex-col items-center text-center">
                    <div class="w-24 h-24 rounded-full bg-dark-800 border-2 border-emerald-400/50 p-1 mb-4 relative overflow-hidden">
                        <img id="avatar-preview" 
                             src="<?= sanitize($display_avatar) ?>" 
                             alt="Avatar Preview" 
                             class="w-full h-full rounded-full object-cover"
                             onerror="this.onerror=null;this.src='<?= sanitize($default_avatar) ?>'">
                    </div>
                    <h2 id="preview-fullname" class="text-base font-bold text-white"><?= sanitize($user['full_name'] ?: $user['username']) ?></h2>
                    <p id="preview-username" class="text-xs text-emerald-400 font-mono mt-0.5">@<?= sanitize($user['username']) ?></p>
                    <p class="text-xs text-gray-400 font-mono mt-1"><?= sanitize($user['email']) ?></p>
                    <span class="mt-4 px-3 py-1 rounded-full bg-emerald-400/10 text-emerald-400 border border-emerald-400/20 text-[10px] font-mono">
                        Member since <?= !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : 'Recently' ?>
                    </span>
                </div>
            </div>

            
            <div class="lg:col-span-2 space-y-8">
                
                <div class="glass-panel p-6 rounded-2xl border border-dark-700 space-y-6">
                    <div class="flex items-center gap-3 border-b border-dark-700 pb-4">
                        <i class="fa-solid fa-user-gear text-cyan-400"></i>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider font-mono">Profile Information</h2>
                    </div>

                    <form id="profile-form" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="full_name" class="block text-xs font-mono text-gray-400 mb-1.5">Full Name</label>
                                <input type="text" id="full_name" name="full_name" required value="<?= sanitize($user['full_name'] ?? '') ?>" 
                                       class="w-full bg-dark-800 border border-dark-700 rounded-xl px-3 py-2.5 text-xs text-gray-200 focus:outline-none focus:border-cyan-400 transition"
                                       placeholder="Jane Doe">
                            </div>

                            <div>
                                <label for="username" class="block text-xs font-mono text-gray-400 mb-1.5">Username</label>
                                <input type="text" id="username" name="username" required value="<?= sanitize($user['username'] ?? '') ?>" 
                                       class="w-full bg-dark-800 border border-dark-700 rounded-xl px-3 py-2.5 text-xs text-gray-200 focus:outline-none focus:border-cyan-400 transition"
                                       placeholder="jane_doe">
                            </div>
                        </div>

                        <div>
                            <label for="avatar_file" class="block text-xs font-mono text-gray-400 mb-1.5">Profile Photo (From Your Device)</label>
                            <input type="file" id="avatar_file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif"
                                   class="w-full bg-dark-800 border border-dark-700 rounded-xl px-3 py-2.5 text-xs text-gray-200 focus:outline-none focus:border-cyan-400 transition file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-cyan-400 file:text-dark-900 file:text-xs file:font-bold hover:file:bg-cyan-300">
                            <p class="text-[10px] text-gray-500 mt-1">JPG, PNG, WEBP or GIF · max 2 MB. Leave empty to keep your current photo.</p>
                        </div>

                        <button type="submit" id="btn-save-profile" class="px-5 py-2.5 bg-cyan-400 text-dark-900 text-xs font-bold rounded-xl hover:bg-cyan-300 transition shadow-lg shadow-cyan-500/10">
                            Save Profile Changes
                        </button>
                    </form>
                </div>

                
                <div class="glass-panel p-6 rounded-2xl border border-dark-700 space-y-6">
                    <div class="flex items-center gap-3 border-b border-dark-700 pb-4">
                        <i class="fa-solid fa-shield-halved text-red-400"></i>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider font-mono">Change Password</h2>
                    </div>

                    <form id="password-form" class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-xs font-mono text-gray-400 mb-1.5">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required class="w-full bg-dark-800 border border-dark-700 rounded-xl px-3 py-2.5 text-xs text-gray-200 focus:outline-none focus:border-red-400 transition" placeholder="••••••••">
                        </div>

                        <div>
                            <label for="new_password" class="block text-xs font-mono text-gray-400 mb-1.5">New Password (Min 8 Characters)</label>
                            <input type="password" id="new_password" name="new_password" required minlength="8" class="w-full bg-dark-800 border border-dark-700 rounded-xl px-3 py-2.5 text-xs text-gray-200 focus:outline-none focus:border-red-400 transition" placeholder="••••••••">
                        </div>

                        <button type="submit" id="btn-save-password" class="px-5 py-2.5 bg-red-400 text-dark-900 text-xs font-bold rounded-xl hover:bg-red-300 transition shadow-lg shadow-red-500/10">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const CSRF_TOKEN = '<?= sanitize($csrf_token) ?>';
const DEFAULT_AVATAR = '<?= sanitize($default_avatar) ?>';

// REST v1 preferred, legacy api/update-settings.php fallback.
async function v1post(v1url, legacyPayload, v1Payload, method) {
    try {
        const r1 = await fetch(v1url, {method: method || 'PATCH', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(v1Payload)});
        const d1 = await r1.json();
        if (d1 && d1.success) return d1;
    } catch (e) { /* fall through to legacy */ }
    const r2 = await fetch('api/update-settings.php', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(legacyPayload)});
    return await r2.json();
}

const avatarInput = document.getElementById('avatar_file');
const avatarPreview = document.getElementById('avatar-preview');
const fullNameInput = document.getElementById('full_name');
const usernameInput = document.getElementById('username');

if (avatarInput && avatarPreview) {
    avatarInput.addEventListener('change', () => {
        const file = avatarInput.files && avatarInput.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({icon: 'error', title: 'Too large', text: 'Image must be 2 MB or less.', confirmButtonColor: '#f43f5e', customClass: { popup: 'rounded-2xl bg-dark-800 text-white border border-dark-700' }});
                avatarInput.value = '';
                return;
            }
            avatarPreview.src = URL.createObjectURL(file);
        }
    });
}

document.getElementById('profile-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-save-profile');
    btn.disabled = true;

    const fd = new FormData();
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('action', 'update_profile');
    fd.append('full_name', fullNameInput.value.trim());
    fd.append('username', usernameInput.value.trim());
    if (avatarInput && avatarInput.files && avatarInput.files[0]) {
        fd.append('avatar', avatarInput.files[0]);
    }

    // REST v1 multipart preferred, legacy multipart fallback.
    async function postProfile(url) {
        const res = await fetch(url, {method: 'POST', body: fd});
        return await res.json();
    }

    try {
        let data = await postProfile('api/v1/index.php?r=me&_method=PATCH');
        if (!data.success) data = await postProfile('api/update-settings.php');
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Profile Updated',
                text: data.message,
                confirmButtonColor: '#06b6d4',
                customClass: { popup: 'rounded-2xl bg-dark-800 text-white border border-dark-700' }
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: data.error || 'Failed to update profile.',
                confirmButtonColor: '#f43f5e',
                customClass: { popup: 'rounded-2xl bg-dark-800 text-white border border-dark-700' }
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Could not connect to the server.',
            confirmButtonColor: '#f43f5e',
            customClass: { popup: 'rounded-2xl bg-dark-800 text-white border border-dark-700' }
        });
    } finally {
        btn.disabled = false;
    }
});

document.getElementById('password-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-save-password');
    btn.disabled = true;

    const payload = {
        csrf_token: CSRF_TOKEN,
        action: 'change_password',
        current_password: document.getElementById('current_password').value,
        new_password: document.getElementById('new_password').value
    };
    const v1payload = {csrf_token: CSRF_TOKEN, current_password: payload.current_password, new_password: payload.new_password};

    try {
        const data = await v1post('api/v1/index.php?r=me/password', payload, v1payload, 'PUT');
        if (data.success) {
            e.target.reset();
            Swal.fire({
                icon: 'success',
                title: 'Password Changed',
                text: data.message,
                confirmButtonColor: '#10b981',
                customClass: { popup: 'rounded-2xl bg-dark-800 text-white border border-dark-700' }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Password Update Failed',
                text: data.error || 'Failed to change password.',
                confirmButtonColor: '#f43f5e',
                customClass: { popup: 'rounded-2xl bg-dark-800 text-white border border-dark-700' }
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Could not connect to the server.',
            confirmButtonColor: '#f43f5e',
            customClass: { popup: 'rounded-2xl bg-dark-800 text-white border border-dark-700' }
        });
    } finally {
        btn.disabled = false;
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>