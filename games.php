<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requirePlayer();

$games = [
    'invaders' => ['name' => 'Pixel Invaders', 'icon' => 'fa-rocket', 'color' => 'emerald', 'desc' => 'Procedural pixel aliens descend. Type to fire the cannon.', 'unit' => 'pts'],
    'blocks' => ['name' => 'Block Drop', 'icon' => 'fa-cubes', 'color' => 'cyan', 'desc' => 'Tetris-typing hybrid. Smash falling pieces with words.', 'unit' => 'pts'],
    'meteors' => ['name' => 'Meteor Defense', 'icon' => 'fa-meteor', 'color' => 'red', 'desc' => 'Burning rocks streak at your city. Type to intercept — nothing may land.', 'unit' => 'pts'],
    'rush' => ['name' => 'Neon Rush', 'icon' => 'fa-flag-checkered', 'color' => 'amber', 'desc' => 'Outtype three ghosts over 40 words. Your WPM is your engine.', 'unit' => 'pts'],
];

$db = Database::getConnection();
$best = [];
try {
    $stmt = $db->prepare("SELECT mode, COALESCE(MAX(game_score), 0) AS b FROM typing_sessions WHERE user_id = :u AND mode IN ('invaders','blocks','meteors','rush') GROUP BY mode");
    $stmt->execute([':u' => getCurrentUserId()]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { $best[$r['mode']] = (int)$r['b']; }
} catch (Throwable $e) { $best = []; }

$page_title = 'Arcade — TypeForge';
require_once __DIR__ . '/includes/header.php';
?>

<div class="flex min-h-screen bg-dark-900 text-gray-200">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 p-6 md:p-10 space-y-8 overflow-y-auto">
        <div class="tf-reveal text-center max-w-2xl mx-auto">
            <p class="text-[11px] font-mono text-emerald-400 uppercase tracking-widest">TypeForge Arcade</p>
            <h1 class="text-3xl font-extrabold text-white tracking-tight mt-1">Pick your battle</h1>
            <p class="text-xs text-gray-400 mt-2">Every run banks honest WPM + accuracy and ranks on the Games leaderboards. Achievements fire automatically.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 max-w-4xl mx-auto">
            <?php $d = 0; foreach ($games as $key => $gm): $d += 0.07; ?>
            <div class="tf-card tf-reveal glass-panel p-6 rounded-2xl border border-dark-700 flex flex-col gap-4" style="--d:<?= $d ?>s">
                <div class="flex items-center gap-4">
                    <div class="tf-icon w-14 h-14 rounded-2xl bg-<?= $gm['color'] ?>-500/10 border border-<?= $gm['color'] ?>-500/30 flex items-center justify-center text-<?= $gm['color'] ?>-400 text-2xl shrink-0">
                        <i class="fa-solid <?= $gm['icon'] ?>"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-extrabold text-white"><?= sanitize($gm['name']) ?></h2>
                        <p class="text-[11px] font-mono text-gray-500">Best: <span class="text-emerald-400 font-bold"><?= $best[$key] ?? 0 ?></span> <?= sanitize($gm['unit']) ?></p>
                    </div>
                </div>
                <p class="text-xs text-gray-400 leading-relaxed flex-1"><?= sanitize($gm['desc']) ?></p>
                <div class="flex gap-2">
                    <a href="game.php?g=<?= $key ?>" class="tf-btn flex-1 text-center px-5 py-3 text-sm font-bold text-dark-900 bg-emerald-400 rounded-xl hover:bg-emerald-300 transition">Play</a>
                    <a href="leaderboard.php?mode=<?= $key ?>" class="px-5 py-3 text-sm font-bold text-gray-300 bg-dark-800 border border-dark-600 rounded-xl hover:bg-dark-700 hover:text-white transition">Ranks</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
