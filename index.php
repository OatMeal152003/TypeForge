<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'TypeForge — Master Your Keyboard';

 
$db = Database::getConnection();

$total_sessions = $db->query("SELECT COUNT(*) FROM typing_sessions")->fetchColumn() ?: 0;
$avg_wpm        = round((float)($db->query("SELECT AVG(wpm) FROM typing_sessions")->fetchColumn() ?: 0), 1);
$avg_accuracy   = round((float)($db->query("SELECT AVG(accuracy) FROM typing_sessions")->fetchColumn() ?: 0), 1);
$total_users    = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn() ?: 0;

require_once __DIR__ . '/includes/header.php';
?>

<div id="intro-loader" role="status" aria-label="Loading TypeForge">
    <div class="intro-glow"></div>
    <div class="intro-card">
        <div class="intro-logo"><img src="images/logo_TypeForge.png" alt="TypeForge logo" style="width:100%;height:100%;object-fit:contain;border-radius:.9rem"></div>
        <div class="text-xl font-bold tracking-tight text-white font-mono">Type<span class="text-emerald-400">Forge</span></div>
        <p class="intro-type"><span class="intro-type-text"></span><span class="intro-caret"></span></p>
        <div class="intro-track"><span class="intro-bar-fill"></span></div>
        <div class="text-xs font-mono text-gray-400"><span class="intro-pct">0%</span></div>
    </div>
</div>

<section class="relative pt-20 pb-16 md:pt-28 md:pb-24 overflow-hidden">
    
    <div class="hero-shader" aria-hidden="true">
        <canvas id="hero-gl"></canvas>
        <div class="hero-blob hero-blob-1"></div>
        <div class="hero-blob hero-blob-2"></div>
        <div class="hero-blob hero-blob-3"></div>
        <div class="hero-grid"></div>
        <div class="hero-grain"></div>
        <div class="hero-sheen"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-mono bg-dark-800 text-emerald-400 border border-dark-600 mb-6 shadow-xl">
            <i class="fa-solid fa-code text-xs"></i> Built for Programmers & Power Users
        </span>
        <h1 class="tf-chroma text-4xl md:text-6xl font-extrabold tracking-tight text-white mb-6 leading-tight">
            Master Your Keyboard.<br>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-200">Unlock Your Speed.</span>
        </h1>
        <p class="max-w-2xl mx-auto text-base md:text-lg text-gray-400 mb-8 leading-relaxed">
            Elevate your typing velocity, code faster, and track muscle memory progress with real-time telemetry analytics and global leaderboards.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
            <a href="typing-test.php" class="tf-btn w-full sm:w-auto px-8 py-3.5 text-base font-semibold text-dark-900 bg-emerald-400 rounded-xl hover:bg-emerald-300 transition shadow-lg shadow-emerald-500/20 flex items-center justify-center gap-2">
                <i class="fa-solid fa-keyboard"></i> Start Practice
            </a>
            <a href="leaderboard.php" class="tf-btn w-full sm:w-auto px-8 py-3.5 text-base font-semibold text-gray-300 bg-dark-800 border border-dark-600 rounded-xl hover:bg-dark-700 hover:text-white transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-trophy text-amber-400"></i> View Leaderboard
            </a>
        </div>

        
        <div id="code-demo" class="max-w-3xl mx-auto glass-panel rounded-xl p-6 text-left shadow-2xl font-mono text-sm border border-dark-600 relative">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-dark-700 text-xs text-gray-400">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-red-500/80"></span>
                    <span class="w-3 h-3 rounded-full bg-yellow-500/80"></span>
                    <span class="w-3 h-3 rounded-full bg-green-500/80"></span>
                    <span class="ml-2 text-gray-400">test-mode: code / language: php</span>
                </div>
                <div class="flex items-center gap-4 text-emerald-400 font-semibold">
                    <span>118 WPM</span>
                    <span>99.4% ACC</span>
                </div>
            </div>
            <div class="text-gray-400 leading-relaxed">
                <span class="text-emerald-400">function</span> <span class="text-blue-400">calculateNetWpm</span>($chars, $errors, $time) {<br>
                &nbsp;&nbsp;<span class="text-emerald-400">return</span> max(0, (($chars / 5) - $errors) / ($time / 60));<br>
                }<span class="inline-block w-2 h-4 bg-emerald-400 ml-1 animate-caret align-middle"></span><br>
                <span class="text-emerald-400">&gt;</span> <span id="demo-typed" class="text-teal-200"></span><span class="inline-block w-2 h-4 bg-teal-300 ml-1 animate-caret align-middle"></span>
            </div>
        </div>
    </div>
</section>

<section class="py-12 bg-dark-800/50 border-y border-dark-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            <div class="p-4">
                <div class="text-3xl md:text-4xl font-extrabold text-white font-mono mb-1" data-count="<?= (int)$total_sessions ?>"><?= number_format($total_sessions) ?></div>
                <div class="text-xs font-medium text-gray-400 uppercase tracking-wider">Tests Completed</div>
            </div>
            <div class="p-4">
                <div class="text-3xl md:text-4xl font-extrabold text-emerald-400 font-mono mb-1" data-count="<?= $avg_wpm ?>" data-decimals="1"><?= $avg_wpm ?></div>
                <div class="text-xs font-medium text-gray-400 uppercase tracking-wider">Average WPM</div>
            </div>
            <div class="p-4">
                <div class="text-3xl md:text-4xl font-extrabold text-teal-300 font-mono mb-1" data-count="<?= $avg_accuracy ?>" data-decimals="1" data-suffix="%"><?= $avg_accuracy ?>%</div>
                <div class="text-xs font-medium text-gray-400 uppercase tracking-wider">Average Accuracy</div>
            </div>
            <div class="p-4">
                <div class="text-3xl md:text-4xl font-extrabold text-white font-mono mb-1" data-count="<?= (int)$total_users ?>"><?= number_format($total_users) ?></div>
                <div class="text-xs font-medium text-gray-400 uppercase tracking-wider">Active Typists</div>
            </div>
        </div>
    </div>
</section>

<section id="features" class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 tf-reveal">
            <h2 class="text-xs font-semibold text-emerald-400 uppercase tracking-widest mb-2">Engineered Performance</h2>
            <p class="text-3xl font-bold text-white tracking-tight">Everything you need to break speed barriers</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="tf-card tf-reveal bg-dark-800 border border-dark-700 rounded-xl p-6" style="--d:.05s">
                <div class="tf-icon w-12 h-12 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl mb-4">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Real-Time Telemetry</h3>
                <p class="text-sm text-gray-400 leading-relaxed">
                    Track gross speed, net WPM, character errors, and accuracy calculated continuously on every keystroke.
                </p>
            </div>

            <div class="tf-card tf-reveal bg-dark-800 border border-dark-700 rounded-xl p-6" style="--d:.15s">
                <div class="tf-icon w-12 h-12 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl mb-4">
                    <i class="fa-solid fa-code"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Code Syntax Practice</h3>
                <p class="text-sm text-gray-400 leading-relaxed">
                    Practice with authentic code snippets across PHP, JavaScript, HTML, CSS, SQL, and Python.
                </p>
            </div>

            <div class="tf-card tf-reveal bg-dark-800 border border-dark-700 rounded-xl p-6" style="--d:.25s">
                <div class="tf-icon w-12 h-12 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl mb-4">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Visual Analytics</h3>
                <p class="text-sm text-gray-400 leading-relaxed">
                    Inspect typing consistency and historical progression through integrated Chart.js visualizations.
                </p>
            </div>
        </div>
    </div>
</section>

<section id="how-it-works" class="py-20 bg-dark-800/30 border-t border-dark-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 tf-reveal">
            <h2 class="text-xs font-semibold text-emerald-400 uppercase tracking-widest mb-2">Simple Workflow</h2>
            <p class="text-3xl font-bold text-white tracking-tight">Four steps to typing mastery</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="tf-step tf-reveal bg-dark-800/80 p-6 rounded-xl border border-dark-700 relative" style="--d:.05s">
                <span class="text-4xl font-extrabold text-dark-600 font-mono mb-4 block">01</span>
                <h4 class="font-bold text-white mb-2">Select Mode</h4>
                <p class="text-xs text-gray-400">Choose between timed tests, word counts, quotes, or custom code blocks.</p>
            </div>
            <div class="tf-step tf-reveal bg-dark-800/80 p-6 rounded-xl border border-dark-700 relative" style="--d:.12s">
                <span class="text-4xl font-extrabold text-dark-600 font-mono mb-4 block">02</span>
                <h4 class="font-bold text-white mb-2">Type Fast</h4>
                <p class="text-xs text-gray-400">Type with dynamic character highlighting and active caret feedback.</p>
            </div>
            <div class="tf-step tf-reveal bg-dark-800/80 p-6 rounded-xl border border-dark-700 relative" style="--d:.12s">
                <span class="text-4xl font-extrabold text-dark-600 font-mono mb-4 block">03</span>
                <h4 class="font-bold text-white mb-2">Analyze Telemetry</h4>
                <p class="text-xs text-gray-400">Review your WPM velocity, character accuracy, and mistake breakdown.</p>
            </div>
            <div class="tf-step tf-reveal bg-dark-800/80 p-6 rounded-xl border border-dark-700 relative" style="--d:.12s">
                <span class="text-4xl font-extrabold text-dark-600 font-mono mb-4 block">04</span>
                <h4 class="font-bold text-white mb-2">Climb Ranks</h4>
                <p class="text-xs text-gray-400">Unlock achievement badges and claim your spot on the global leaderboard.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="tf-reveal bg-gradient-to-r from-emerald-900/40 via-dark-800 to-dark-800 border border-emerald-500/30 rounded-2xl p-10 text-center relative overflow-hidden">
            <h2 class="text-3xl font-extrabold text-white mb-4">Ready to accelerate your typing speed?</h2>
            <p class="text-gray-400 max-w-xl mx-auto mb-8 text-sm">Join TypeForge today to track your progress, unlock achievements, and compete with typists worldwide.</p>
            <a href="register.php" class="tf-btn inline-flex items-center gap-2 px-8 py-3.5 text-sm font-semibold text-dark-900 bg-emerald-400 rounded-xl hover:bg-emerald-300 transition shadow-lg shadow-emerald-500/20">
                Create Free Account <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>
    </div>
</section>

<script src="assets/js/intro-loader.js" defer></script>
<script src="assets/js/landing-interactions.js" defer></script>
<script src="assets/js/hero-webgl.js" defer></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>