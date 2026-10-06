<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
if (!class_exists('Database')) {
    $db_file = __DIR__ . '/../config/database.php';
    if (file_exists($db_file)) {
        require_once $db_file;
    }
}

$is_authenticated = isLoggedIn();
$current_user = $is_authenticated ? getCurrentUser() : null;
$page_title = $page_title ?? 'TypeForge — Master Your Keyboard';

 
$user_theme = 'dark';
$user_caret = 'line';
$user_smooth = true;
$user_sound = true;

if ($is_authenticated) {
    try {
        // Only real user_settings columns (theme, sound_enabled); caret/smooth use defaults.
        $db = Database::getConnection();
        $stmt_pref = $db->prepare("SELECT theme, sound_enabled FROM user_settings WHERE user_id = :uid LIMIT 1");
        $stmt_pref->execute(['uid' => getCurrentUserId()]);
        $pref = $stmt_pref->fetch(PDO::FETCH_ASSOC);
        if ($pref) {
            $user_theme = $pref['theme'] ?? 'dark';
            $user_sound = !empty($pref['sound_enabled']);
        }
    } catch (Exception $e) {
         
    }
}

 
 
 
$current_script = basename($_SERVER['PHP_SELF'] ?? '');
if (!isset($hide_navbar)) {
    $hide_navbar = $is_authenticated || ($current_script !== 'index.php');
}
$base_prefix = $base_prefix ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="dark scroll-smooth" data-theme="<?= sanitize($user_theme) ?>" data-caret="<?= sanitize($user_caret) ?>" data-smooth="<?= $user_smooth ? '1' : '0' ?>" data-sound="<?= $user_sound ? '1' : '0' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($page_title) ?></title>
    <script>try{var __tf=localStorage.getItem('tf_theme');if(__tf&&/^(light|dark|matrix|cyberpunk|monokai|dracula|nord)$/.test(__tf)){document.documentElement.setAttribute('data-theme',__tf);}}catch(e){}</script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            950: '#06090e',
                            900: '#0d1117',
                            800: '#161b22',
                            700: '#21262d',
                            600: '#30363d'
                        },
                        emerald: {
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669'
                        }
                    },
                    fontFamily: {
                        mono: ['JetBrains Mono', 'Fira Code', 'monospace'],
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="<?= $base_prefix ?>assets/css/style.css?v=20">
    <script src="<?= $base_prefix ?>assets/js/page-transition.js" defer></script>
    <script src="<?= $base_prefix ?>assets/js/theme-toggle.js" defer></script>
    <script src="<?= $base_prefix ?>assets/js/sidebar-toggle.js" defer></script>
</head>
<body class="bg-dark-900 text-gray-200 font-sans antialiased selection:bg-emerald-500 selection:text-dark-900 min-h-screen flex flex-col">

    <div id="page-curtain" aria-hidden="true">
        <div class="pt-mark">Type<span>Forge</span></div>
        <div class="pt-bar"><span></span></div>
    </div>

    <?php if (!$hide_navbar): ?>
    
    <nav class="sticky top-0 z-50 bg-dark-900/80 backdrop-blur-md border-b border-dark-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            
            <a href="<?= $base_prefix ?>index.php" class="flex items-center gap-3 group">
                <img src="<?= $base_prefix ?>images/logo_TypeForge.png" alt="TypeForge logo" class="w-9 h-9 rounded-lg object-contain bg-white/5 border border-emerald-500/30 group-hover:border-emerald-500 transition-all duration-300">
                <span class="text-xl font-bold tracking-tight text-white font-mono">Type<span class="text-emerald-400">Forge</span></span>
            </a>

            
            <div class="hidden md:flex items-center gap-8">
                <a href="<?= $base_prefix ?>index.php#features" class="text-sm font-medium text-gray-400 hover:text-emerald-400 transition">Features</a>
                <a href="<?= $base_prefix ?>index.php#how-it-works" class="text-sm font-medium text-gray-400 hover:text-emerald-400 transition">How It Works</a>
            </div>

            
            <div class="flex items-center gap-4">
                <?php require __DIR__ . '/theme-toggle.php'; ?>
                <?php if ($is_authenticated): ?>
                    <a href="<?= $base_prefix ?>dashboard.php" class="px-4 py-2 text-sm font-semibold text-dark-900 bg-emerald-400 rounded-lg hover:bg-emerald-300 transition shadow-lg shadow-emerald-500/10">
                        Dashboard <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
                    </a>
                <?php else: ?>
                    <a href="<?= $base_prefix ?>login.php" class="text-sm font-medium text-gray-300 hover:text-white transition">Sign In</a>
                    <a href="<?= $base_prefix ?>register.php" class="px-4 py-2 text-sm font-semibold text-dark-900 bg-emerald-400 rounded-lg hover:bg-emerald-300 transition shadow-lg shadow-emerald-500/20">
                        Get Started
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <?php if ($hide_navbar && !$is_authenticated): ?>
        <?php $tf_toggle_floating = true; require __DIR__ . '/theme-toggle.php'; ?>
    <?php endif; ?>
