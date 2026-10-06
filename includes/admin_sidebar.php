<?php
// TypeForge Admin Sidebar — dedicated management navigation.
// Distinct from the player sidebar: amber shield identity, admin-only sections,
// proper FontAwesome icons per item. Used by admin/*.php with $base_prefix = '../'.
$current_page = basename($_SERVER['PHP_SELF'] ?? '');
$base_prefix = $base_prefix ?? '../';
// Prefix for admin-console links when included from root pages (set $admin_prefix = 'admin/').
// Inside admin/*.php it defaults to '' (relative links).
$admin_prefix = $admin_prefix ?? '';

if (empty($_SESSION['csrf_token']) && function_exists('generateCsrfToken')) {
    generateCsrfToken();
}
$logout_csrf = $_SESSION['csrf_token'] ?? '';

function admin_link(string $href, string $icon, string $label, bool $active, string $badge = ''): void {
    $cls = $active
        ? 'bg-amber-400/10 text-amber-300 border border-amber-500/40'
        : 'text-gray-400 hover:bg-dark-700 hover:text-white border border-transparent';
    echo '<a href="' . $href . '" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition ' . $cls . '">'
        . '<i class="fa-solid ' . $icon . ' text-base w-5 text-center"></i>'
        . '<span class="flex-1">' . $label . '</span>'
        . ($badge !== '' ? '<span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-dark-600 text-gray-400">' . $badge . '</span>' : '')
        . '</a>';
}
?>
<aside class="admin-nav w-64 bg-dark-800 border-r border-amber-500/20 h-screen sticky top-0 overflow-y-auto flex flex-col justify-between p-4 hidden md:flex shrink-0">
    <div class="space-y-6">
        <div class="brand-row px-3 py-2 border-b border-dark-700/60 pb-4 flex items-center justify-between gap-2">
            <a href="<?= $admin_prefix ?>dashboard.php" class="flex items-center gap-3 group min-w-0">
                <div class="w-9 h-9 rounded-lg bg-amber-400/10 border border-amber-500/40 flex items-center justify-center text-amber-300 group-hover:bg-amber-400 group-hover:text-dark-900 transition-all duration-300">
                    <i class="fa-solid fa-shield-halved text-lg"></i>
                </div>
                <span>
                    <span class="block text-lg font-bold tracking-tight text-white font-mono leading-none">Admin</span>
                    <span class="block text-[10px] font-mono text-amber-400/80 uppercase tracking-widest mt-0.5">Console</span>
                </span>
            </a>
            <button id="sidebar-hamburger" title="Collapse sidebar" class="shrink-0 w-8 h-8 rounded-lg text-gray-400 hover:text-white hover:bg-dark-700 transition flex items-center justify-center">
                <i class="fa-solid fa-bars text-sm"></i>
            </button>
        </div>

        <div>
            <span class="nav-section px-3 text-[10px] font-bold uppercase tracking-wider text-gray-500 font-mono">Overview</span>
            <nav class="mt-2 space-y-1">
                <?php admin_link($admin_prefix . 'dashboard.php', 'fa-gauge-high', 'Console', $current_page === 'dashboard.php'); ?>
                <?php admin_link($admin_prefix . 'users.php', 'fa-users-gear', 'Users', $current_page === 'users.php'); ?>
            </nav>
        </div>

        <div>
            <span class="nav-section px-3 text-[10px] font-bold uppercase tracking-wider text-gray-500 font-mono">Content</span>
            <nav class="admin-nav mt-2 space-y-1">
                <?php admin_link($admin_prefix . 'texts.php', 'fa-file-lines', 'Typing Texts', $current_page === 'texts.php'); ?>
                <?php admin_link($admin_prefix . 'achievements.php', 'fa-medal', 'Achievements', $current_page === 'achievements.php'); ?>
            </nav>
        </div>

        <div>
            <span class="nav-section px-3 text-[10px] font-bold uppercase tracking-wider text-gray-500 font-mono">System</span>
            <nav class="mt-2 space-y-1">
                <?php admin_link($base_prefix . 'leaderboard.php', 'fa-trophy', 'Public Leaderboard', $current_page === 'leaderboard.php'); ?>
                <?php admin_link($base_prefix . 'settings.php', 'fa-user-gear', 'My Account', $current_page === 'settings.php'); ?>
                <?php admin_link($base_prefix . 'index.php', 'fa-globe', 'View Site', false); ?>
            </nav>
        </div>
    </div>

    <div class="pt-4 border-t border-dark-700">
        <div class="side-foot-row flex items-center justify-between gap-2">
            <div class="user-meta flex items-center gap-3 overflow-hidden min-w-0">
                <div class="w-8 h-8 rounded-full bg-amber-400/15 border border-amber-500/40 text-amber-300 flex items-center justify-center text-xs font-bold font-mono">
                    <?= strtoupper(substr($_SESSION['user_username'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="truncate">
                    <p class="text-xs font-bold text-white truncate"><?= sanitize($_SESSION['user_name'] ?? 'Admin') ?></p>
                    <p class="text-[10px] text-amber-400/80 font-mono truncate">ADMINISTRATOR</p>
                </div>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <?php require __DIR__ . '/theme-toggle.php'; ?>
                <form method="POST" action="<?= $base_prefix ?>logout.php" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= sanitize($logout_csrf) ?>">
                    <button type="submit" title="Sign Out" class="text-gray-400 hover:text-red-400 p-1.5 transition">
                        <i class="fa-solid fa-right-from-bracket text-sm"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>

<nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-dark-800/95 backdrop-blur border-t border-amber-500/20 flex items-center justify-around py-2">
    <a href="<?= $admin_prefix ?>dashboard.php" class="flex flex-col items-center gap-1 px-3 py-1.5 text-[10px] font-mono <?= $current_page === 'dashboard.php' ? 'text-amber-300' : 'text-gray-400' ?>">
        <i class="fa-solid fa-shield-halved text-base"></i>Console
    </a>
    <a href="<?= $admin_prefix ?>users.php" class="flex flex-col items-center gap-1 px-3 py-1.5 text-[10px] font-mono <?= $current_page === 'users.php' ? 'text-amber-300' : 'text-gray-400' ?>">
        <i class="fa-solid fa-users-gear text-base"></i>Users
    </a>
    <a href="<?= $base_prefix ?>leaderboard.php" class="flex flex-col items-center gap-1 px-3 py-1.5 text-[10px] font-mono text-gray-400">
        <i class="fa-solid fa-trophy text-base"></i>Ranks
    </a>
    <a href="<?= $base_prefix ?>settings.php" class="flex flex-col items-center gap-1 px-3 py-1.5 text-[10px] font-mono text-gray-400">
        <i class="fa-solid fa-user-gear text-base"></i>Account
    </a>
</nav>
<script src="<?= $base_prefix ?>assets/js/admin-interactions.js" defer></script>
