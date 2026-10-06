<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$db = Database::getConnection();
$current_user_id = getCurrentUserId();

$metric = sanitize($_GET['metric'] ?? 'net_wpm');
if (!in_array($metric, ['net_wpm', 'raw_wpm', 'accuracy'], true)) $metric = 'net_wpm';

$mode = sanitize($_GET['mode'] ?? 'all');
$game_modes = [
    'invaders' => ['label' => 'Pixel Invaders', 'unit' => 'pts'],
    'blocks' => ['label' => 'Block Drop', 'unit' => 'pts'],
    'meteors' => ['label' => 'Meteor Defense', 'unit' => 'pts'],
    'rush' => ['label' => 'Neon Rush', 'unit' => 'pts'],
];
if (!in_array($mode, array_merge(['all', 'words', 'time', 'quote', 'custom'], array_keys($game_modes)), true)) $mode = 'all';
$is_game = isset($game_modes[$mode]);

$length = (isset($_GET['length']) && $_GET['length'] !== '') ? (int)$_GET['length'] : null;

$punctuation_raw = $_GET['punctuation'] ?? '';
$punctuation = ($punctuation_raw === '1') ? 1 : (($punctuation_raw === '0') ? 0 : null);

$numbers_raw = $_GET['numbers'] ?? '';
$numbers = ($numbers_raw === '1') ? 1 : (($numbers_raw === '0') ? 0 : null);

$timeframe = sanitize($_GET['timeframe'] ?? 'all_time');
if (!in_array($timeframe, ['all_time', 'monthly', 'daily'], true)) $timeframe = 'all_time';

$where_clauses = ['1=1'];
$params = [];

if ($mode !== 'all') {
    $where_clauses[] = 'tr.mode = :mode';
    $params[':mode'] = $mode;
}
if ($length !== null && $length > 0) {
    $where_clauses[] = 'tr.mode_value = :length';
    $params[':length'] = $length;
}
if ($punctuation !== null) {
    $where_clauses[] = 'tr.has_punctuation = :punct';
    $params[':punct'] = $punctuation;
}
if ($numbers !== null) {
    $where_clauses[] = 'tr.has_numbers = :num';
    $params[':num'] = $numbers;
}
if ($timeframe === 'monthly') {
    $where_clauses[] = 'tr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
} elseif ($timeframe === 'daily') {
    $where_clauses[] = 'tr.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)';
}
$where_sql = implode(' AND ', $where_clauses);

switch ($metric) {
    case 'raw_wpm':
        $score_select   = 'MAX(tr.raw_wpm) AS primary_score';
        $order_sql      = 'primary_score DESC, MAX(tr.wpm) DESC, AVG(tr.accuracy) DESC';
        $metric_label   = 'Raw Speed';
        $metric_unit    = 'WPM';
        $metric_col     = 'raw_wpm';
        $th_net_class   = '';
        $th_raw_class   = 'text-amber-400 bg-amber-500/5';
        $th_acc_class   = '';
        $th_game_class  = '';
        break;
    case 'accuracy':
        $score_select   = 'MAX(tr.accuracy) AS primary_score';
        $order_sql      = 'primary_score DESC, MAX(tr.wpm) DESC, MAX(tr.raw_wpm) DESC';
        $metric_label   = 'Overall Accuracy';
        $metric_unit    = '%';
        $metric_col     = 'accuracy';
        $th_net_class   = '';
        $th_raw_class   = '';
        $th_acc_class   = 'text-cyan-400 bg-cyan-500/5';
        $th_game_class  = '';
        break;
    default:
        $metric = 'net_wpm';
        $score_select   = 'MAX(tr.wpm) AS primary_score';
        $order_sql      = 'primary_score DESC, MAX(tr.raw_wpm) DESC, AVG(tr.accuracy) DESC';
        $metric_label   = 'Net Speed';
        $metric_unit    = 'WPM';
        $metric_col     = 'wpm';
        $th_net_class   = 'text-emerald-400 bg-emerald-500/5';
        $th_raw_class   = '';
        $th_acc_class   = '';
        $th_game_class  = '';
        break;
}

// Arcade game modes rank by game-native score, not typing metrics.
if ($is_game) {
    $score_select   = 'MAX(tr.game_score) AS primary_score';
    $order_sql      = 'primary_score DESC, MAX(tr.wpm) DESC, AVG(tr.accuracy) DESC';
    $metric_label   = $game_modes[$mode]['label'] . ' Score';
    $metric_unit    = $game_modes[$mode]['unit'];
    $metric_col     = 'game_score';
    $th_net_class   = '';
    $th_raw_class   = '';
    $th_acc_class   = '';
    $th_game_class  = 'text-amber-400 bg-amber-500/5';
}

$stmt_lb = $db->prepare("
    SELECT
        u.id AS user_id,
        u.username,
        u.full_name,
        u.profile_picture AS avatar_url,
        {$score_select},
        MAX(tr.game_score) AS top_score,
        MAX(tr.wpm)       AS top_wpm,
        MAX(tr.raw_wpm)   AS top_raw_wpm,
        MAX(tr.accuracy)  AS peak_accuracy,
        AVG(tr.accuracy)  AS avg_accuracy,
        COUNT(tr.id)      AS tests_completed,
        MAX(tr.created_at) AS last_active
    FROM typing_sessions tr
    JOIN users u ON tr.user_id = u.id
    WHERE {$where_sql}
    GROUP BY u.id, u.username, u.full_name, u.profile_picture
    ORDER BY {$order_sql}
    LIMIT 50
");
$stmt_lb->execute($params);
$rankings = $stmt_lb->fetchAll(PDO::FETCH_ASSOC);

$user_rank_data = null;
if ($current_user_id) {
    $u_where = array_merge($where_clauses, ['tr.user_id = :curr_uid']);
    $u_params = array_merge($params, [':curr_uid' => $current_user_id]);
    $stmt_ub = $db->prepare("SELECT MAX(tr.{$metric_col}) AS best FROM typing_sessions tr WHERE " . implode(' AND ', $u_where));
    $stmt_ub->execute($u_params);
    $user_best = (float)$stmt_ub->fetchColumn();

    if ($user_best > 0) {
        $h_where = array_merge($where_clauses, ["tr.{$metric_col} > :ubest"]);
        $h_params = array_merge($params, [':ubest' => $user_best]);
        $stmt_rank = $db->prepare("SELECT COUNT(DISTINCT tr.user_id)+1 FROM typing_sessions tr WHERE " . implode(' AND ', $h_where));
        $stmt_rank->execute($h_params);
        $user_rank = (int)$stmt_rank->fetchColumn();

        $stmt_total = $db->prepare("SELECT COUNT(DISTINCT tr.user_id) FROM typing_sessions tr WHERE {$where_sql}");
        $stmt_total->execute($params);
        $total = max(1, (int)$stmt_total->fetchColumn());
        $percentile = max(1, round(($user_rank / $total) * 100, 1));

        $user_rank_data = ['rank' => $user_rank, 'best' => $user_best, 'pct' => $percentile, 'total' => $total];
    }
}

function lb_url(array $overrides = []): string {
    $base = [
        'metric'      => $GLOBALS['metric'],
        'mode'        => $GLOBALS['mode'],
        'timeframe'   => $GLOBALS['timeframe'],
        'length'      => $GLOBALS['length'],
        'punctuation' => $GLOBALS['punctuation'],
        'numbers'     => $GLOBALS['numbers'],
    ];
    $merged = array_merge($base, $overrides);
    $filtered = array_filter($merged, fn($v) => $v !== null && $v !== '');
    return 'leaderboard.php?' . http_build_query($filtered);
}

$page_title = 'Leaderboards — ' . $metric_label . ' | TypeForge';
require_once __DIR__ . '/includes/header.php';
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

    <main class="flex-1 p-6 md:p-10 space-y-6 overflow-y-auto">

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-4 border-b border-dark-700/60">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
                    <i class="fa-solid fa-trophy text-amber-400"></i> Global Leaderboards
                </h1>
                <p class="text-xs text-gray-400 mt-1">Compare typists by speed, raw keystroke rate, or precision accuracy — filterable by mode, length, and modifiers.</p>
            </div>

            <div class="flex items-center bg-dark-800 p-1.5 rounded-2xl border border-dark-700 font-mono text-xs shrink-0">
                <?php if ($is_game): ?>
                <span class="flex items-center gap-1.5 px-4 py-2 rounded-xl font-bold bg-amber-400 text-dark-900 shadow-md">
                    <i class="fa-solid fa-gamepad text-[11px]"></i> <?= sanitize($metric_label) ?>
                </span>
                <?php else: ?>
                <a href="<?= lb_url(['metric' => 'net_wpm', 'length' => null]) ?>"
                   class="flex items-center gap-1.5 px-4 py-2 rounded-xl transition font-medium <?= $metric === 'net_wpm' ? 'bg-emerald-400 text-dark-900 font-bold shadow-md' : 'text-gray-400 hover:text-white' ?>">
                    <i class="fa-solid fa-bolt text-[11px]"></i> Net WPM
                </a>
                <a href="<?= lb_url(['metric' => 'raw_wpm', 'length' => null]) ?>"
                   class="flex items-center gap-1.5 px-4 py-2 rounded-xl transition font-medium <?= $metric === 'raw_wpm' ? 'bg-amber-400 text-dark-900 font-bold shadow-md' : 'text-gray-400 hover:text-white' ?>">
                    <i class="fa-solid fa-gauge-simple-high text-[11px]"></i> Raw Speed
                </a>
                <a href="<?= lb_url(['metric' => 'accuracy', 'length' => null]) ?>"
                   class="flex items-center gap-1.5 px-4 py-2 rounded-xl transition font-medium <?= $metric === 'accuracy' ? 'bg-cyan-400 text-dark-900 font-bold shadow-md' : 'text-gray-400 hover:text-white' ?>">
                    <i class="fa-solid fa-bullseye text-[11px]"></i> Accuracy
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="glass-panel p-4 rounded-2xl border border-dark-700 flex flex-wrap items-center justify-between gap-4 font-mono text-xs">

            <div class="flex flex-wrap items-center gap-3">

                <div class="flex items-center bg-dark-900 p-1 rounded-xl border border-dark-700">
                    <span class="px-2 text-[10px] text-gray-500 uppercase font-bold tracking-wider">mode</span>
                    <a href="<?= lb_url(['mode' => 'all', 'length' => null]) ?>"
                       class="px-2.5 py-1 rounded-lg transition <?= $mode === 'all' ? 'bg-dark-600 text-white font-bold' : 'text-gray-400 hover:text-white' ?>">All</a>
                    <a href="<?= lb_url(['mode' => 'words', 'length' => ($mode !== 'words' ? 25 : $length)]) ?>"
                       class="px-2.5 py-1 rounded-lg transition <?= $mode === 'words' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 font-bold' : 'text-gray-400 hover:text-white' ?>">Words</a>
                    <a href="<?= lb_url(['mode' => 'time', 'length' => ($mode !== 'time' ? 30 : $length)]) ?>"
                       class="px-2.5 py-1 rounded-lg transition <?= $mode === 'time'  ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 font-bold' : 'text-gray-400 hover:text-white' ?>">Time</a>
                    <a href="<?= lb_url(['mode' => 'quote', 'length' => null]) ?>"
                       class="px-2.5 py-1 rounded-lg transition <?= $mode === 'quote' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 font-bold' : 'text-gray-400 hover:text-white' ?>">Quote</a>
                    <a href="<?= lb_url(['mode' => 'custom', 'length' => null]) ?>"
                       class="px-2.5 py-1 rounded-lg transition <?= $mode === 'custom'? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 font-bold' : 'text-gray-400 hover:text-white' ?>">Custom</a>
                </div>

                <div class="flex items-center bg-dark-900 p-1 rounded-xl border border-amber-500/30">
                    <span class="px-2 text-[10px] text-amber-400/80 uppercase font-bold tracking-wider"><i class="fa-solid fa-gamepad"></i> arcade</span>
                    <?php foreach (['invaders' => 'Invaders', 'blocks' => 'Blocks', 'meteors' => 'Meteors', 'rush' => 'Rush'] as $gm => $glabel): ?>
                    <a href="<?= lb_url(['mode' => $gm, 'length' => null, 'punctuation' => null, 'numbers' => null]) ?>"
                       class="px-2.5 py-1 rounded-lg transition <?= $mode === $gm ? 'bg-amber-400 text-dark-900 font-bold' : 'text-gray-400 hover:text-white' ?>"><?= $glabel ?></a>
                    <?php endforeach; ?>
                </div>

                <?php if ($mode === 'words'): ?>
                <div class="flex items-center bg-dark-900 p-1 rounded-xl border border-dark-700">
                    <span class="px-2 text-[10px] text-gray-500 uppercase font-bold tracking-wider">words</span>
                    <a href="<?= lb_url(['length' => null]) ?>"   class="px-2.5 py-1 rounded-lg transition <?= $length === null ? 'bg-dark-600 text-white font-bold' : 'text-gray-400 hover:text-white' ?>">Any</a>
                    <a href="<?= lb_url(['length' => 15]) ?>"     class="px-2.5 py-1 rounded-lg transition <?= $length === 15   ? 'bg-emerald-400 text-dark-900 font-bold' : 'text-gray-400 hover:text-white' ?>">15</a>
                    <a href="<?= lb_url(['length' => 25]) ?>"     class="px-2.5 py-1 rounded-lg transition <?= $length === 25   ? 'bg-emerald-400 text-dark-900 font-bold' : 'text-gray-400 hover:text-white' ?>">25</a>
                    <a href="<?= lb_url(['length' => 50]) ?>"     class="px-2.5 py-1 rounded-lg transition <?= $length === 50   ? 'bg-emerald-400 text-dark-900 font-bold' : 'text-gray-400 hover:text-white' ?>">50</a>
                    <a href="<?= lb_url(['length' => 100]) ?>"    class="px-2.5 py-1 rounded-lg transition <?= $length === 100  ? 'bg-emerald-400 text-dark-900 font-bold' : 'text-gray-400 hover:text-white' ?>">100</a>
                </div>
                <?php elseif ($mode === 'time'): ?>

                <div class="flex items-center bg-dark-900 p-1 rounded-xl border border-dark-700">
                    <span class="px-2 text-[10px] text-gray-500 uppercase font-bold tracking-wider">sec</span>
                    <a href="<?= lb_url(['length' => null]) ?>"   class="px-2.5 py-1 rounded-lg transition <?= $length === null ? 'bg-dark-600 text-white font-bold' : 'text-gray-400 hover:text-white' ?>">Any</a>
                    <a href="<?= lb_url(['length' => 15]) ?>"     class="px-2.5 py-1 rounded-lg transition <?= $length === 15   ? 'bg-emerald-400 text-dark-900 font-bold' : 'text-gray-400 hover:text-white' ?>">15s</a>
                    <a href="<?= lb_url(['length' => 30]) ?>"     class="px-2.5 py-1 rounded-lg transition <?= $length === 30   ? 'bg-emerald-400 text-dark-900 font-bold' : 'text-gray-400 hover:text-white' ?>">30s</a>
                    <a href="<?= lb_url(['length' => 60]) ?>"     class="px-2.5 py-1 rounded-lg transition <?= $length === 60   ? 'bg-emerald-400 text-dark-900 font-bold' : 'text-gray-400 hover:text-white' ?>">60s</a>
                </div>
                <?php endif; ?>

                <?php if (in_array($mode, ['all', 'words', 'time'], true)): ?>
                <a href="<?= lb_url(['punctuation' => ($punctuation === 1 ? null : 1)]) ?>"
                   class="px-2.5 py-1.5 rounded-xl border transition flex items-center gap-1.5 <?= $punctuation === 1 ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-400 font-bold' : 'border-dark-700 bg-dark-900 text-gray-400 hover:text-white' ?>">
                    <i class="fa-solid fa-at text-[10px]"></i> punctuation
                </a>
                <a href="<?= lb_url(['numbers' => ($numbers === 1 ? null : 1)]) ?>"
                   class="px-2.5 py-1.5 rounded-xl border transition flex items-center gap-1.5 <?= $numbers === 1 ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-400 font-bold' : 'border-dark-700 bg-dark-900 text-gray-400 hover:text-white' ?>">
                    <i class="fa-solid fa-hashtag text-[10px]"></i> numbers
                </a>
                <?php endif; ?>
            </div>

            <div class="flex items-center bg-dark-900 p-1 rounded-xl border border-dark-700">
                <span class="px-2 text-[10px] text-gray-500 uppercase font-bold tracking-wider">when</span>
                <a href="<?= lb_url(['timeframe' => 'all_time']) ?>" class="px-3 py-1 rounded-lg transition <?= $timeframe === 'all_time' ? 'bg-emerald-400 text-dark-900 font-bold' : 'text-gray-400 hover:text-white' ?>">All-Time</a>
                <a href="<?= lb_url(['timeframe' => 'monthly']) ?>"  class="px-3 py-1 rounded-lg transition <?= $timeframe === 'monthly'  ? 'bg-emerald-400 text-dark-900 font-bold' : 'text-gray-400 hover:text-white' ?>">30 Days</a>
                <a href="<?= lb_url(['timeframe' => 'daily']) ?>"    class="px-3 py-1 rounded-lg transition <?= $timeframe === 'daily'    ? 'bg-emerald-400 text-dark-900 font-bold' : 'text-gray-400 hover:text-white' ?>">24 Hours</a>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 font-mono text-[11px] text-gray-500">
            <span>Showing:</span>
            <span class="px-2 py-0.5 rounded-md border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 uppercase font-bold"><?= sanitize($metric_label) ?></span>
            <span class="px-2 py-0.5 rounded-md border border-dark-700 bg-dark-800 text-gray-300">Mode: <strong class="text-white capitalize"><?= $mode ?></strong></span>
            <?php if ($length !== null): ?>
                <span class="px-2 py-0.5 rounded-md border border-dark-700 bg-dark-800 text-gray-300">Length: <strong class="text-white"><?= $length ?><?= $mode === 'time' ? 's' : 'w' ?></strong></span>
            <?php endif; ?>
            <?php if ($punctuation === 1): ?><span class="px-2 py-0.5 rounded-md border border-emerald-500/30 bg-emerald-500/10 text-emerald-400">+ punctuation</span><?php endif; ?>
            <?php if ($numbers === 1): ?><span class="px-2 py-0.5 rounded-md border border-emerald-500/30 bg-emerald-500/10 text-emerald-400">+ numbers</span><?php endif; ?>
            <span class="px-2 py-0.5 rounded-md border border-dark-700 bg-dark-800 text-gray-400 capitalize"><?= str_replace('_', ' ', $timeframe) ?></span>
            <a href="leaderboard.php?metric=<?= $metric ?>" class="ml-1 text-gray-600 hover:text-red-400 transition underline">reset</a>
        </div>

        <?php if ($user_rank_data): ?>
        <div class="glass-panel p-5 rounded-2xl border border-emerald-500/30 bg-emerald-500/5 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-400/10 border border-emerald-400/30 flex items-center justify-center text-emerald-400 font-mono font-bold text-lg">#<?= $user_rank_data['rank'] ?></div>
                <div>
                    <h3 class="text-sm font-semibold text-white">Your Standing</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Top <span class="text-emerald-400 font-bold"><?= $user_rank_data['pct'] ?>%</span> of <?= number_format($user_rank_data['total']) ?> typists in this category.</p>
                </div>
            </div>
            <div class="text-right font-mono">
                <div class="text-[10px] text-gray-500 uppercase">Your Best (<?= $metric_label ?>)</div>
                <div class="text-emerald-400 font-bold text-xl">
                    <?php if ($is_game): ?>
                        <?= number_format((int)$user_rank_data['best']) ?> <span class="text-xs"><?= sanitize($metric_unit) ?></span>
                    <?php elseif ($metric === 'accuracy'): ?>
                        <?= number_format($user_rank_data['best'], 1) ?>%
                    <?php else: ?>
                        <?= (int)$user_rank_data['best'] ?> WPM
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="glass-panel rounded-2xl border border-dark-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-dark-700 bg-dark-800/60 text-gray-400 uppercase font-mono tracking-wider">
                            <th class="py-4 px-4 text-center w-14">Rank</th>
                            <th class="py-4 px-4">Typist</th>
                            <?php if ($is_game): ?>
                            <th class="py-4 px-4 font-bold <?= $th_game_class ?>">Score <span class="font-normal opacity-70">(<?= sanitize($metric_unit) ?>)</span></th>
                            <?php endif; ?>
                            <th class="py-4 px-4 font-bold <?= $th_net_class ?>">Net WPM</th>
                            <th class="py-4 px-4 font-bold <?= $th_raw_class ?>">Raw WPM</th>
                            <th class="py-4 px-4 font-bold <?= $th_acc_class ?>">Accuracy</th>
                            <th class="py-4 px-4">Tests</th>
                            <th class="py-4 px-4 text-right">Last Active</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-700/50 font-mono">
                        <?php if (empty($rankings)): ?>
                            <tr>
                                <td colspan="<?= $is_game ? 8 : 7 ?>" class="py-16 text-center text-gray-500 font-sans">
                                    <i class="fa-solid fa-keyboard text-3xl mb-3 block opacity-30"></i>
                                    No results found for this filter combination.<br>
                                    <?php if ($is_game): ?>
                                    <a href="game.php?g=<?= $mode ?>" class="mt-2 inline-block text-amber-400 hover:underline text-xs">Be the first — play <?= sanitize($game_modes[$mode]['label']) ?></a><br>
                                    <?php endif; ?>
                                    <a href="leaderboard.php?metric=<?= $metric ?>" class="mt-2 inline-block text-emerald-400 hover:underline text-xs">Clear filters</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rankings as $idx => $row):
                                $rank = $idx + 1;
                                $is_me = ($row['user_id'] == $current_user_id);
                                $avatar = !empty($row['avatar_url'])
                                    ? $row['avatar_url']
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($row['full_name'] ?: $row['username']) . '&background=161b22&color=10b981&bold=true';
                            ?>
                            <tr class="hover:bg-dark-800/40 transition <?= $is_me ? 'bg-emerald-400/5 border-l-2 border-l-emerald-400' : '' ?>">

                                <td class="py-3.5 px-4 text-center font-bold">
                                    <?php if ($rank === 1): ?>
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-400/20 text-amber-300 text-xs shadow-sm"><i class="fa-solid fa-crown"></i></span>
                                    <?php elseif ($rank === 2): ?>
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-400/20 text-slate-300 text-xs font-bold">2</span>
                                    <?php elseif ($rank === 3): ?>
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-700/20 text-amber-500 text-xs font-bold">3</span>
                                    <?php else: ?>
                                        <span class="text-gray-500 text-xs"><?= $rank ?></span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3.5 px-4 font-sans">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full overflow-hidden border border-dark-600 bg-dark-700 shrink-0">
                                            <img src="<?= sanitize($avatar) ?>" alt="" class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <span class="font-semibold <?= $is_me ? 'text-emerald-400' : 'text-gray-200' ?>">
                                                <?= sanitize($row['username']) ?>
                                            </span>
                                            <?php if ($is_me): ?>
                                                <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-400/20 text-emerald-400 font-mono">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <?php if ($is_game): ?>
                                <td class="py-3.5 px-4 text-amber-400 font-bold text-sm bg-amber-500/5">
                                    <?= number_format((int)($row['top_score'] ?? 0)) ?>
                                </td>
                                <?php endif; ?>

                                <td class="py-3.5 px-4 <?= ($metric === 'net_wpm') ? 'text-emerald-400 font-bold text-sm bg-emerald-500/5' : 'text-gray-300' ?>">
                                    <?= (int)$row['top_wpm'] ?>
                                </td>

                                <td class="py-3.5 px-4 <?= ($metric === 'raw_wpm') ? 'text-amber-400 font-bold text-sm bg-amber-500/5' : 'text-gray-400' ?>">
                                    <?= (int)$row['top_raw_wpm'] ?>
                                </td>

                                <td class="py-3.5 px-4 <?= ($metric === 'accuracy') ? 'bg-cyan-500/5' : '' ?>">
                                    <span class="font-bold <?= ($metric === 'accuracy') ? 'text-cyan-400 text-sm' : 'text-cyan-400' ?>">
                                        <?= number_format((float)$row['peak_accuracy'], 1) ?>%
                                    </span>
                                    <span class="text-gray-500 text-[10px] ml-1">(<?= number_format((float)$row['avg_accuracy'], 1) ?>% avg)</span>
                                </td>

                                <td class="py-3.5 px-4 text-gray-400"><?= number_format((int)$row['tests_completed']) ?></td>

                                <td class="py-3.5 px-4 text-right text-gray-500 font-sans text-xs">
                                    <?= date('M j, Y', strtotime($row['last_active'])) ?>
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