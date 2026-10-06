<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requirePlayer();

$user_id = getCurrentUserId();
$db = Database::getConnection();

 
$stmt_stats = $db->prepare("
    SELECT 
        COUNT(id) AS total_tests,
        COALESCE(MAX(wpm), 0) AS best_wpm,
        COALESCE(ROUND(AVG(wpm), 1), 0) AS avg_wpm,
        COALESCE(ROUND(AVG(accuracy), 1), 0) AS avg_accuracy,
        COALESCE(SUM(duration_seconds), 0) AS total_time_seconds
    FROM typing_sessions
    WHERE user_id = :user_id
");
$stmt_stats->execute(['user_id' => $user_id]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

 
$stmt_recent = $db->prepare("
    SELECT id, mode, wpm, raw_wpm, accuracy, duration_seconds, created_at
    FROM typing_sessions
    WHERE user_id = :user_id
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt_recent->execute(['user_id' => $user_id]);
$recent_tests = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/achievements.php';
$shelf = tf_user_achievements($db, (int)$user_id);

$page_title = 'Dashboard — TypeForge';
require_once __DIR__ . '/includes/header.php';
?>

<div class="flex min-h-screen bg-dark-900 text-gray-200">
    
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    
    <main class="flex-1 p-6 md:p-10 space-y-8 overflow-y-auto">
        
        
        <div class="tf-reveal flex flex-col md:flex-row md:items-center justify-between gap-4 glass-panel p-6 rounded-2xl border border-dark-700">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">
                    <i class="fa-solid fa-rocket text-emerald-400 mr-1"></i>
                    Welcome back, <span class="text-emerald-400"><?= sanitize($_SESSION['user_name'] ?? 'Typist') ?></span>!
                </h1>
                <p class="text-xs text-gray-400 mt-1">Ready to test your speed and improve your accuracy today?</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
            <a href="typing-test.php" class="tf-btn inline-flex items-center justify-center gap-2 px-5 py-3 text-sm font-semibold text-dark-900 bg-emerald-400 rounded-xl hover:bg-emerald-300 transition shadow-lg shadow-emerald-500/20">
                <i class="fa-solid fa-play text-xs"></i> Start Typing Test
            </a>
            <a href="games.php" class="inline-flex items-center justify-center gap-2 px-5 py-3 text-sm font-semibold text-gray-200 bg-dark-800 border border-dark-600 rounded-xl hover:bg-dark-700 hover:text-white transition">
                <i class="fa-solid fa-gamepad text-xs text-emerald-400"></i> Arcade
            </a>
            </div>
        </div>

        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <div class="tf-card tf-reveal glass-panel p-5 rounded-2xl border border-dark-700 flex items-center gap-4" style="--d:.02s">
                <div class="tf-icon w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 text-xl shrink-0">
                    <i class="fa-solid fa-bolt"></i>
                </div>
                <div>
                    <span class="text-[11px] font-mono text-gray-400 uppercase tracking-wider">Best Speed</span>
                    <div class="text-2xl font-bold text-white font-mono"><span data-count="<?= (int)$stats['best_wpm'] ?>"><?= (int)$stats['best_wpm'] ?></span> <span class="text-xs text-gray-500 font-sans font-normal">WPM</span></div>
                </div>
            </div>

            
            <div class="tf-card tf-reveal glass-panel p-5 rounded-2xl border border-dark-700 flex items-center gap-4" style="--d:.08s">
                <div class="tf-icon w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-xl shrink-0">
                    <i class="fa-solid fa-gauge-high"></i>
                </div>
                <div>
                    <span class="text-[11px] font-mono text-gray-400 uppercase tracking-wider">Average Speed</span>
                    <div class="text-2xl font-bold text-white font-mono"><span data-count="<?= $stats['avg_wpm'] ?>" data-decimals="1"><?= $stats['avg_wpm'] ?></span> <span class="text-xs text-gray-500 font-sans font-normal">WPM</span></div>
                </div>
            </div>

            
            <div class="tf-card tf-reveal glass-panel p-5 rounded-2xl border border-dark-700 flex items-center gap-4" style="--d:.14s">
                <div class="tf-icon w-12 h-12 rounded-xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 text-xl shrink-0">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
                <div>
                    <span class="text-[11px] font-mono text-gray-400 uppercase tracking-wider">Avg Accuracy</span>
                    <div class="text-2xl font-bold text-white font-mono"><span data-count="<?= $stats['avg_accuracy'] ?>" data-decimals="1"><?= $stats['avg_accuracy'] ?></span><span class="text-xs text-gray-500">%</span></div>
                </div>
            </div>

            
            <div class="tf-card tf-reveal glass-panel p-5 rounded-2xl border border-dark-700 flex items-center gap-4" style="--d:.2s">
                <div class="tf-icon w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-400 text-xl shrink-0">
                    <i class="fa-solid fa-keyboard"></i>
                </div>
                <div>
                    <span class="text-[11px] font-mono text-gray-400 uppercase tracking-wider">Tests Completed</span>
                    <div class="text-2xl font-bold text-white font-mono" data-count="<?= (int)$stats['total_tests'] ?>"><?= number_format($stats['total_tests']) ?></div>
                </div>
            </div>
        </div>

        
        <div class="tf-reveal glass-panel rounded-2xl border border-dark-700 overflow-hidden" style="--d:.05s">
            <div class="p-5 border-b border-dark-700 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white"><i class="fa-solid fa-trophy text-amber-400 mr-2"></i>Achievements</h3>
                    <p class="text-xs text-gray-400"><?= count($shelf['items']) ?> badges · <?= (int)$shelf['points'] ?> points earned</p>
                </div>
            </div>
            <div class="p-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <?php foreach ($shelf['items'] as $ach): ?>
                <div class="shelf-card<?= $ach['earned'] ? ' shelf-earned' : '' ?> rounded-xl border p-4 text-center transition <?= $ach['earned'] ? 'bg-emerald-500/10 border-emerald-500/40' : 'bg-dark-800/60 border-dark-700 opacity-90' ?>">
                    <div class="mx-auto w-12 h-12 rounded-xl flex items-center justify-center text-xl mb-2 <?= $ach['earned'] ? 'bg-emerald-500/15 border border-emerald-500/40 text-emerald-400' : 'bg-dark-700 border border-dark-600 text-gray-500' ?>">
                        <i class="fa-solid <?= sanitize($ach['icon']) ?>"></i>
                    </div>
                    <p class="text-xs font-bold <?= $ach['earned'] ? 'text-white' : 'text-gray-400' ?>"><?= sanitize($ach['title']) ?></p>
                    <p class="text-[10px] text-gray-500 mt-0.5"><?= sanitize($ach['description']) ?></p>
                    <?php if ($ach['earned']): ?>
                        <p class="text-[10px] font-mono text-emerald-400 mt-1.5">+<?= (int)$ach['points'] ?> pts<?= $ach['unlocked_at'] ? ' · ' . date('M j', strtotime($ach['unlocked_at'])) : '' ?></p>
                    <?php else: ?>
                        <div class="mt-2 h-1.5 rounded-full bg-dark-700 overflow-hidden"><div class="h-full rounded-full bg-emerald-500/70" style="width: <?= (int)round($ach['progress'] * 100) ?>%"></div></div>
                        <p class="text-[10px] font-mono text-gray-500 mt-1"><?= sanitize($ach['progress_label']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="tf-reveal glass-panel rounded-2xl border border-dark-700 overflow-hidden" style="--d:.1s">
            <div class="p-5 border-b border-dark-700 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white">Recent Tests</h3>
                    <p class="text-xs text-gray-400">Your latest 5 typing practice sessions</p>
                </div>
                <a href="history.php" class="text-xs text-emerald-400 font-semibold hover:underline flex items-center gap-1">
                    View All <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="pf-table w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-dark-700 bg-dark-800/50 text-gray-400 uppercase font-mono">
                            <th class="py-3 px-4">Mode</th>
                            <th class="py-3 px-4">Net WPM</th>
                            <th class="py-3 px-4">Raw WPM</th>
                            <th class="py-3 px-4">Accuracy</th>
                            <th class="py-3 px-4">Duration</th>
                            <th class="py-3 px-4 text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-700/50">
                        <?php if (empty($recent_tests)): ?>
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-500">
                                    No typing tests recorded yet. <a href="typing-test.php" class="text-emerald-400 underline">Take your first test!</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_tests as $test): ?>
                                <tr class="hover:bg-dark-800/30 transition">
                                    <td class="py-3 px-4 font-mono text-gray-300">
                                        <span class="px-2 py-0.5 rounded bg-dark-700 border border-dark-600 font-bold uppercase text-[10px]">
                                            <?= sanitize($test['mode']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-mono font-bold text-emerald-400 text-sm">
                                        <?= (int)$test['wpm'] ?>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-gray-400">
                                        <?= (int)$test['raw_wpm'] ?>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-gray-300">
                                        <?= $test['accuracy'] ?>%
                                    </td>
                                    <td class="py-3 px-4 font-mono text-gray-400">
                                        <?= (int)$test['duration_seconds'] ?>s
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-500">
                                        <?= date('M j, Y g:i A', strtotime($test['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>