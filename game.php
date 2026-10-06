<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requirePlayer();

$games = [
    'invaders' => ['name' => 'Pixel Invaders', 'icon' => 'fa-rocket', 'color' => 'emerald', 'desc' => 'Pixel aliens descend. Type to fire your cannon. 3 lives.', 'how' => 'Type the lowest invader to shoot. Full clears pop it — combos multiply. Nothing may cross the red line.', 'canvas' => 'game-invaders.js'],
    'blocks' => ['name' => 'Block Drop', 'icon' => 'fa-cubes', 'color' => 'cyan', 'desc' => 'Tetris-typing: type the piece word to smash it mid-fall.', 'how' => 'Complete the shown word to smash the falling piece. Stacked pieces lock — clear lines, survive the well.', 'canvas' => 'game-blocks.js'],
    'meteors' => ['name' => 'Meteor Defense', 'icon' => 'fa-meteor', 'color' => 'red', 'desc' => 'Burning rocks streak at your city. Type to intercept.', 'how' => 'Finish a meteor word to blast it mid-air. Longer words score more — combos multiply. 3 city hits and it is over.', 'canvas' => 'game-meteors.js'],
    'rush' => ['name' => 'Neon Rush', 'icon' => 'fa-flag-checkered', 'color' => 'amber', 'desc' => 'Outtype three ghosts over 40 words. Your WPM is your engine.', 'how' => 'Sparky (35), Bolt (55), Vortex (75). Type clean to take the lead — wrong keys stall you. Placement plus speed sets your score.', 'canvas' => 'game-rush.js'],
];
$g = strtolower(trim($_GET['g'] ?? 'invaders'));
if (!isset($games[$g])) { header('Location: games.php'); exit(); }
$game = $games[$g];

$db = Database::getConnection();
$csrf_token = generateCsrfToken();
$best = 0;
try {
    $stmt = $db->prepare("SELECT COALESCE(MAX(game_score), 0) FROM typing_sessions WHERE user_id = :u AND mode = :m");
    $stmt->execute([':u' => getCurrentUserId(), ':m' => $g]);
    $best = (int)$stmt->fetchColumn();
} catch (Throwable $e) { $best = 0; }

$page_title = $game['name'] . ' — TypeForge Arcade';
require_once __DIR__ . '/includes/header.php';
?>

<script>
    window.GAME = <?= json_encode($g) ?>;
    window.CSRF_TOKEN = <?= json_encode($csrf_token) ?>;
</script>

<div class="flex min-h-screen bg-dark-900 text-gray-200">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 p-6 md:p-10 flex flex-col items-center space-y-6 overflow-y-auto">
        <div class="w-full max-w-4xl flex items-center justify-between">
            <a href="games.php" class="text-xs text-gray-400 hover:text-white transition flex items-center gap-1.5"><i class="fa-solid fa-arrow-left"></i> Arcade</a>
            <div class="text-xs font-mono text-gray-400">Personal best: <span class="text-emerald-400 font-bold"><?= $best ?></span></div>
        </div>

        <div class="text-center">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-<?= $game['color'] ?>-500/10 border border-<?= $game['color'] ?>-500/30 flex items-center justify-center text-<?= $game['color'] ?>-400 text-2xl mb-3">
                <i class="fa-solid <?= $game['icon'] ?>"></i>
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight"><?= sanitize($game['name']) ?></h1>
            <p class="text-xs text-gray-400 mt-1"><?= sanitize($game['desc']) ?></p>
        </div>

        <div id="game-hud" class="zen-dim glass-panel px-6 py-3 rounded-2xl border border-dark-700 flex flex-wrap items-center justify-center gap-8 font-mono"></div>

        <div id="game-arena" class="w-full max-w-4xl glass-panel p-8 rounded-2xl border border-dark-700 relative min-h-[380px] shadow-2xl overflow-hidden">
            <input type="text" id="game-input" class="absolute inset-0 w-full h-full opacity-0 cursor-text z-10" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore onpaste="return false;" ondrop="return false;" oncopy="return false;">
            <?php if (!empty($game['canvas'])): ?>
            <canvas id="game-canvas" class="pixel-canvas"></canvas>
            <div class="pixel-scanlines" aria-hidden="true"></div>
            <?php endif; ?>
            <div id="game-start" class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-4 bg-dark-900/80 backdrop-blur-sm p-8 text-center">
                <p class="text-sm text-gray-300 max-w-md"><?= sanitize($game['how']) ?></p>
                <button id="game-start-btn" class="px-8 py-3.5 text-base font-bold text-dark-900 bg-emerald-400 rounded-xl hover:bg-emerald-300 transition shadow-lg shadow-emerald-500/20 flex items-center gap-2">
                    <i class="fa-solid fa-play"></i> Start game
                </button>
                <p class="text-[11px] text-gray-500 font-mono">Scores save to leaderboards · achievements fire automatically</p>
            </div>
        </div>

        <p class="zen-dim text-xs text-gray-500 font-mono">Click the arena to refocus · results bank automatically on finish</p>
    </main>
</div>

<script src="assets/js/pixel-engine.js" defer></script>
<script src="assets/js/<?= sanitize($game['canvas']) ?>" defer></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
