<?php

$current_page = basename($_SERVER['PHP_SELF']);
$base_prefix = $base_prefix ?? '';
$user_role = $_SESSION['user_role'] ?? 'user';
$is_dashboard = ($current_page === 'dashboard.php');
 
if (empty($_SESSION['csrf_token']) && function_exists('generateCsrfToken')) {
    generateCsrfToken();
}
$logout_csrf = $_SESSION['csrf_token'] ?? '';
?>
<aside class="w-64 bg-dark-800 border-r border-dark-700 h-screen sticky top-0 overflow-y-auto flex flex-col justify-between p-4 hidden md:flex shrink-0">
    <div class="space-y-6">
        
        <div class="brand-row px-3 py-2 border-b border-dark-700/60 pb-4 flex items-center justify-between gap-2">
            <a href="<?= $base_prefix ?>dashboard.php" class="flex items-center gap-3 group min-w-0">
                <img src="<?= $base_prefix ?>images/logo_TypeForge.png" alt="TypeForge logo" class="w-9 h-9 rounded-lg object-contain bg-white/5 border border-emerald-500/30 group-hover:border-emerald-500 transition-all duration-300 shrink-0">
                <span class="text-xl font-bold tracking-tight text-white font-mono">Type<span class="text-emerald-400">Forge</span></span>
            </a>
            <button id="sidebar-hamburger" title="Collapse sidebar" class="shrink-0 w-8 h-8 rounded-lg text-gray-400 hover:text-white hover:bg-dark-700 transition flex items-center justify-center">
                <i class="fa-solid fa-bars text-sm"></i>
            </button>
        </div>

        
        <div>
            <span class="nav-section px-3 text-[10px] font-bold uppercase tracking-wider text-gray-500 font-mono">Main Menu</span>
            <nav class="mt-2 space-y-1">
                <a href="<?= $base_prefix ?>dashboard.php" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $current_page === 'dashboard.php' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'text-gray-400 hover:bg-dark-700 hover:text-white' ?>">
                    <i class="fa-solid fa-gauge-high text-base w-5"></i>
                    <span>Dashboard</span>
                </a>

                <a href="<?= $base_prefix ?>typing-test.php" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $current_page === 'typing-test.php' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'text-gray-400 hover:bg-dark-700 hover:text-white' ?>">
                    <i class="fa-solid fa-keyboard text-base w-5"></i>
                    <span>Typing Test</span>
                </a>

                <a href="<?= $base_prefix ?>games.php" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= in_array($current_page, ['games.php', 'game.php'], true) ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'text-gray-400 hover:bg-dark-700 hover:text-white' ?>">
                    <i class="fa-solid fa-gamepad text-base w-5"></i>
                    <span>Arcade</span>
                </a>

                <a href="<?= $base_prefix ?>history.php" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $current_page === 'history.php' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'text-gray-400 hover:bg-dark-700 hover:text-white' ?>">
                    <i class="fa-solid fa-clock-rotate-left text-base w-5"></i>
                    <span>Typing History</span>
                </a>

                <a href="<?= $base_prefix ?>statistics.php" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $current_page === 'statistics.php' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'text-gray-400 hover:bg-dark-700 hover:text-white' ?>">
                    <i class="fa-solid fa-chart-line text-base w-5"></i>
                    <span>Statistics</span>
                </a>

                <a href="<?= $base_prefix ?>leaderboard.php" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $current_page === 'leaderboard.php' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'text-gray-400 hover:bg-dark-700 hover:text-white' ?>">
                    <i class="fa-solid fa-trophy text-base w-5"></i>
                    <span>Leaderboard</span>
                </a>
            </nav>
        </div>

        
        <div>
            <span class="nav-section px-3 text-[10px] font-bold uppercase tracking-wider text-gray-500 font-mono">Account</span>
            <nav class="mt-2 space-y-1">
                <a href="<?= $base_prefix ?>settings.php" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $current_page === 'settings.php' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'text-gray-400 hover:bg-dark-700 hover:text-white' ?>">
                    <i class="fa-solid fa-user-gear text-base w-5"></i>
                    <span>Profile Settings</span>
                </a>
            </nav>
        </div>

        
        <?php if ($user_role === 'admin' && is_dir(__DIR__ . '/../admin')): ?>
            <div>
                <span class="nav-section px-3 text-[10px] font-bold uppercase tracking-wider text-amber-500 font-mono">Admin Portal</span>
                <nav class="mt-2 space-y-1">
                    <a href="<?= $base_prefix ?>admin/dashboard.php" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition text-amber-400 hover:bg-dark-700">
                        <i class="fa-solid fa-shield-halved text-base w-5"></i>
                        <span>Admin Console</span>
                    </a>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    
    <div class="pt-4 border-t border-dark-700">
        <div class="side-foot-row flex items-center justify-between gap-2">
            <div class="user-meta flex items-center gap-3 overflow-hidden min-w-0">
                <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold font-mono">
                    <?= strtoupper(substr($_SESSION['user_username'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="truncate">
                    <p class="text-xs font-bold text-white truncate"><?= sanitize($_SESSION['user_name'] ?? 'User') ?></p>
                    <p class="text-[10px] text-gray-500 truncate">@<?= sanitize($_SESSION['user_username'] ?? 'user') ?></p>
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

<nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-dark-800/95 backdrop-blur border-t border-dark-700 flex items-center justify-around py-2">
    <a href="<?= $base_prefix ?>dashboard.php" class="flex flex-col items-center gap-1 px-3 py-1.5 text-[10px] font-mono <?= $current_page === 'dashboard.php' ? 'text-emerald-400' : 'text-gray-400' ?>">
        <i class="fa-solid fa-gauge-high text-base"></i>Dashboard
    </a>
    <a href="<?= $base_prefix ?>typing-test.php" class="flex flex-col items-center gap-1 px-3 py-1.5 text-[10px] font-mono <?= $current_page === 'typing-test.php' ? 'text-emerald-400' : 'text-gray-400' ?>">
        <i class="fa-solid fa-keyboard text-base"></i>Test
    </a>
    <a href="<?= $base_prefix ?>games.php" class="flex flex-col items-center gap-1 px-3 py-1.5 text-[10px] font-mono <?= in_array($current_page, ['games.php', 'game.php'], true) ? 'text-emerald-400' : 'text-gray-400' ?>">
        <i class="fa-solid fa-gamepad text-base"></i>Arcade
    </a>
    <a href="<?= $base_prefix ?>history.php" class="flex flex-col items-center gap-1 px-3 py-1.5 text-[10px] font-mono <?= $current_page === 'history.php' ? 'text-emerald-400' : 'text-gray-400' ?>">
        <i class="fa-solid fa-clock-rotate-left text-base"></i>History
    </a>
    <a href="<?= $base_prefix ?>leaderboard.php" class="flex flex-col items-center gap-1 px-3 py-1.5 text-[10px] font-mono <?= $current_page === 'leaderboard.php' ? 'text-emerald-400' : 'text-gray-400' ?>">
        <i class="fa-solid fa-trophy text-base"></i>Ranks
    </a>
    <a href="<?= $base_prefix ?>settings.php" class="flex flex-col items-center gap-1 px-3 py-1.5 text-[10px] font-mono <?= $current_page === 'settings.php' ? 'text-emerald-400' : 'text-gray-400' ?>">
        <i class="fa-solid fa-user-gear text-base"></i>Settings
    </a>
</nav>
<script src="<?= $base_prefix ?>assets/js/admin-interactions.js" defer></script>