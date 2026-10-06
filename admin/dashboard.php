<?php
// TypeForge Admin Console — overview. Admin-only via requireAdmin().
$base_prefix = '../';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$db = Database::getConnection();
$csrf_token = generateCsrfToken();
$flash = sanitize($_GET['msg'] ?? '');

// Platform KPIs
$users_row = $db->query("SELECT COUNT(*) AS total, SUM(status='active') AS active, SUM(status='suspended') AS suspended, SUM(status='banned') AS banned, SUM(role='admin') AS admins, SUM(created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS new7 FROM users")->fetch(PDO::FETCH_ASSOC);
$sess_row = $db->query("SELECT COUNT(*) AS total, SUM(DATE(created_at)=CURDATE()) AS today, SUM(created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS week7, COALESCE(ROUND(AVG(wpm),1),0) AS avg_wpm, COALESCE(ROUND(AVG(accuracy),1),0) AS avg_acc, COALESCE(MAX(wpm),0) AS max_wpm, COALESCE(SUM(duration_seconds),0) AS total_secs FROM typing_sessions")->fetch(PDO::FETCH_ASSOC);
$texts_count = (int)$db->query("SELECT COUNT(*) FROM typing_texts")->fetchColumn();
$ach_count = (int)$db->query("SELECT COUNT(*) FROM achievements")->fetchColumn();

// 20-week daily activity for contribution heatmaps
$tests_daily = $db->query("SELECT DATE(created_at) AS d, COUNT(*) AS c FROM typing_sessions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 140 DAY) GROUP BY d ORDER BY d")->fetchAll(PDO::FETCH_ASSOC);
$users_daily = $db->query("SELECT DATE(created_at) AS d, COUNT(*) AS c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 140 DAY) GROUP BY d ORDER BY d")->fetchAll(PDO::FETCH_ASSOC);
$map_s = [];
foreach ($tests_daily as $r) { $map_s[$r['d']] = (int)$r['c']; }
$map_u = [];
foreach ($users_daily as $r) { $map_u[$r['d']] = (int)$r['c']; }

function heat_level(int $c, int $max): int {
    if ($c <= 0 || $max <= 0) return 0;
    $q = $max / 4;
    if ($c <= $q) return 1;
    if ($c <= $q * 2) return 2;
    if ($c <= $q * 3) return 3;
    return 4;
}

// Columns run Monday-to-Sunday; last column is the current (partial) week.
$heat_end = new DateTime('today');
$heat_start = (clone $heat_end)->modify('-139 days');
$heat_grid_start = (clone $heat_start)->modify('-' . (((int)$heat_start->format('N')) - 1) . ' days');
$heat_today = $heat_end->format('Y-m-d');
$heat_s_weeks = [];
$heat_u_weeks = [];
$heat_max_s = 0;
$heat_max_u = 0;
$heat_tot_s = 0;
$heat_tot_u = 0;
$heat_best_s = '';
$heat_best_u = '';
$heat_col = 0;
while (true) {
    $heat_w0 = (clone $heat_grid_start)->modify('+' . ($heat_col * 7) . ' days');
    if ($heat_w0 > $heat_end) break;
    $heat_ws = [];
    $heat_wu = [];
    for ($heat_i = 0; $heat_i < 7; $heat_i++) {
        $heat_d = (clone $heat_w0)->modify('+' . $heat_i . ' days');
        $heat_ds = $heat_d->format('Y-m-d');
        $heat_future = $heat_ds > $heat_today;
        $heat_cs = $heat_future ? 0 : ($map_s[$heat_ds] ?? 0);
        $heat_cu = $heat_future ? 0 : ($map_u[$heat_ds] ?? 0);
        if (!$heat_future) {
            $heat_tot_s += $heat_cs;
            $heat_tot_u += $heat_cu;
            if ($heat_cs > $heat_max_s) { $heat_max_s = $heat_cs; $heat_best_s = $heat_d->format('M j'); }
            if ($heat_cu > $heat_max_u) { $heat_max_u = $heat_cu; $heat_best_u = $heat_d->format('M j'); }
        }
        $heat_ws[] = ['d' => $heat_ds, 'c' => $heat_cs, 'future' => $heat_future, 'label' => $heat_d->format('M j, Y'), 'month' => $heat_d->format('M')];
        $heat_wu[] = ['d' => $heat_ds, 'c' => $heat_cu, 'future' => $heat_future, 'label' => $heat_d->format('M j, Y'), 'month' => $heat_d->format('M')];
    }
    $heat_s_weeks[] = $heat_ws;
    $heat_u_weeks[] = $heat_wu;
    $heat_col++;
}
// Month label spans across week columns (label sits where the month changes).
$heat_ncols = count($heat_s_weeks);
$heat_months = [];
$heat_prev = '';
$heat_span_start = 0;
for ($heat_c = 0; $heat_c < $heat_ncols; $heat_c++) {
    $heat_m = $heat_s_weeks[$heat_c][0]['month'];
    if ($heat_m !== $heat_prev) {
        if ($heat_prev !== '') $heat_months[] = ['col' => $heat_span_start + 1, 'span' => $heat_c - $heat_span_start, 'label' => $heat_prev];
        $heat_prev = $heat_m;
        $heat_span_start = $heat_c;
    }
}
if ($heat_prev !== '') $heat_months[] = ['col' => $heat_span_start + 1, 'span' => $heat_ncols - $heat_span_start, 'label' => $heat_prev];
$heat_sum_s = $heat_tot_s . ' sessions in the last 20 weeks' . ($heat_best_s !== '' && $heat_max_s > 0 ? ', busiest ' . $heat_best_s : '');
$heat_sum_u = $heat_tot_u . ' new users in the last 20 weeks' . ($heat_best_u !== '' && $heat_max_u > 0 ? ', busiest ' . $heat_best_u : '');

// Top typists + recent tables
$top = $db->query("SELECT u.username, u.status, MAX(t.wpm) AS best, ROUND(AVG(t.accuracy),1) AS acc, COUNT(t.id) AS tests FROM users u JOIN typing_sessions t ON t.user_id=u.id GROUP BY u.id, u.username, u.status ORDER BY best DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$recent_users = $db->query("SELECT id, username, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$recent_sessions = $db->query("SELECT t.id, t.wpm, t.accuracy, t.mode, t.created_at, u.username FROM typing_sessions t JOIN users u ON u.id=t.user_id ORDER BY t.created_at DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

// Audit trail (last 8 admin operations) + achievement grant review
$audit_feed = [];
try {
    $audit_feed = $db->query("SELECT l.action, l.target_type, l.target_id, l.detail, l.created_at, u.username AS admin_name FROM admin_audit_log l LEFT JOIN users u ON u.id = l.admin_id ORDER BY l.created_at DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* table missing on old installs */ }
$ach_review = $db->query("SELECT a.code, a.title, a.criteria_type, a.criteria_value, a.points, COUNT(ua.id) AS grants FROM achievements a LEFT JOIN user_achievements ua ON ua.achievement_id = a.id GROUP BY a.id ORDER BY a.points DESC")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Admin Console — TypeForge';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen bg-dark-900 text-gray-200">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="flex-1 p-6 md:p-10 space-y-8 overflow-y-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-mono text-amber-400 uppercase tracking-widest flex items-center gap-2"><i class="fa-solid fa-shield-halved"></i> Admin only</p>
                <h1 class="text-2xl font-bold text-white tracking-tight mt-1">Admin Console</h1>
                <p class="text-xs text-gray-400 mt-1">Platform health, user statistics, and moderation — <?= (int)$users_row['total'] ?> users · <?= (int)$sess_row['total'] ?> sessions · <?= $texts_count ?> texts · <?= $ach_count ?> achievements</p>
            </div>
            <div class="flex gap-2">
                <a href="users.php" class="ad-btn px-4 py-2.5 text-xs font-bold text-dark-900 bg-amber-400 rounded-xl hover:bg-amber-300 transition"><i class="fa-solid fa-users-gear mr-1"></i> Manage Users</a>
                <a href="../leaderboard.php" class="ad-btn px-4 py-2.5 text-xs font-bold text-gray-300 bg-dark-800 border border-dark-600 rounded-xl hover:bg-dark-700 transition">Public Leaderboard</a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-xl text-xs"><?= $flash ?></div>
        <?php endif; ?>

        <!-- KPI cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="ad-card tf-reveal glass-panel p-5 rounded-2xl border border-dark-700" style="--d:.02s">
                <span class="text-[11px] font-mono text-gray-400 uppercase">Total Users</span>
                <div class="text-3xl font-bold text-white font-mono mt-1" data-count="<?= (int)$users_row['total'] ?>"><?= number_format((int)$users_row['total']) ?></div>
                <p class="text-[11px] text-gray-500 mt-1"><?= (int)$users_row['active'] ?> active · <?= (int)$users_row['new7'] ?> new / 7d · <?= (int)$users_row['admins'] ?> admins</p>
            </div>
            <div class="ad-card tf-reveal glass-panel p-5 rounded-2xl border border-dark-700" style="--d:.08s">
                <span class="text-[11px] font-mono text-gray-400 uppercase">Total Sessions</span>
                <div class="text-3xl font-bold text-emerald-400 font-mono mt-1" data-count="<?= (int)$sess_row['total'] ?>"><?= number_format((int)$sess_row['total']) ?></div>
                <p class="text-[11px] text-gray-500 mt-1"><?= (int)$sess_row['today'] ?> today · <?= (int)$sess_row['week7'] ?> / 7d</p>
            </div>
            <div class="ad-card tf-reveal glass-panel p-5 rounded-2xl border border-dark-700" style="--d:.14s">
                <span class="text-[11px] font-mono text-gray-400 uppercase">Platform Avg</span>
                <div class="text-3xl font-bold text-cyan-400 font-mono mt-1"><span data-count="<?= $sess_row['avg_wpm'] ?>" data-decimals="1"><?= $sess_row['avg_wpm'] ?></span> <span class="text-xs text-gray-500">WPM</span></div>
                <p class="text-[11px] text-gray-500 mt-1"><?= $sess_row['avg_acc'] ?>% accuracy · best <?= (int)$sess_row['max_wpm'] ?> WPM</p>
            </div>
            <div class="ad-card tf-reveal glass-panel p-5 rounded-2xl border border-dark-700" style="--d:.2s">
                <span class="text-[11px] font-mono text-gray-400 uppercase">Moderation Queue</span>
                <div class="text-3xl font-bold text-amber-400 font-mono mt-1" data-count="<?= (int)$users_row['suspended'] + (int)$users_row['banned'] ?>"><?= (int)$users_row['suspended'] + (int)$users_row['banned'] ?></div>
                <p class="text-[11px] text-gray-500 mt-1"><?= (int)$users_row['suspended'] ?> suspended · <?= (int)$users_row['banned'] ?> banned</p>
            </div>
        </div>

        <!-- Contribution heatmaps -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="ad-card tf-reveal glass-panel p-6 rounded-2xl border border-dark-700" style="--d:.05s">
                <h3 class="text-sm font-bold text-white">Sessions (20w)</h3>
                <p class="text-[11px] text-gray-500 mb-3">Volume trend for capacity + engagement review</p>
                <div class="gh-wrap">
                    <div class="gh-scroll">
                        <div class="gh-months" aria-hidden="true" style="grid-template-columns: repeat(<?= $heat_ncols + 1 ?>, minmax(0, 1fr));">
                            <span></span><?php foreach ($heat_months as $heat_mo): ?><span style="grid-column: <?= (int)$heat_mo['col'] + 1 ?> / span <?= (int)$heat_mo['span'] ?>;"><?= sanitize($heat_mo['label']) ?></span><?php endforeach; ?>
                        </div>
                        <div class="gh-heat" role="img" aria-label="<?= sanitize($heat_sum_s) ?>">
                            <?php $heat_day_names = ['', 'Mon', '', 'Wed', '', 'Fri', '']; foreach ($heat_day_names as $heat_dn): ?><span class="gh-day" aria-hidden="true"><?= $heat_dn ?></span><?php endforeach; ?>
                            <?php foreach ($heat_s_weeks as $heat_week): ?><?php foreach ($heat_week as $heat_cell): ?><span class="gh-cell gh-e<?= $heat_cell['future'] ? 'x' : heat_level($heat_cell['c'], $heat_max_s) ?>" title="<?= (int)$heat_cell['c'] ?> sessions · <?= sanitize($heat_cell['label']) ?>" aria-hidden="true"></span><?php endforeach; ?><?php endforeach; ?>
                        </div>
                        <div class="gh-legend" aria-hidden="true"><span>Less</span><span class="gh-cell gh-e0"></span><span class="gh-cell gh-e1"></span><span class="gh-cell gh-e2"></span><span class="gh-cell gh-e3"></span><span class="gh-cell gh-e4"></span><span>More</span></div>
                    </div>
                </div>
            </div>
            <div class="ad-card tf-reveal glass-panel p-6 rounded-2xl border border-dark-700" style="--d:.12s">
                <h3 class="text-sm font-bold text-white">New users (20w)</h3>
                <p class="text-[11px] text-gray-500 mb-3">Growth trend for onboarding review</p>
                <div class="gh-wrap">
                    <div class="gh-scroll">
                        <div class="gh-months" aria-hidden="true" style="grid-template-columns: repeat(<?= $heat_ncols + 1 ?>, minmax(0, 1fr));">
                            <span></span><?php foreach ($heat_months as $heat_mo): ?><span style="grid-column: <?= (int)$heat_mo['col'] + 1 ?> / span <?= (int)$heat_mo['span'] ?>;"><?= sanitize($heat_mo['label']) ?></span><?php endforeach; ?>
                        </div>
                        <div class="gh-heat" role="img" aria-label="<?= sanitize($heat_sum_u) ?>">
                            <?php $heat_day_names = ['', 'Mon', '', 'Wed', '', 'Fri', '']; foreach ($heat_day_names as $heat_dn): ?><span class="gh-day" aria-hidden="true"><?= $heat_dn ?></span><?php endforeach; ?>
                            <?php foreach ($heat_u_weeks as $heat_week): ?><?php foreach ($heat_week as $heat_cell): ?><span class="gh-cell gh-a<?= $heat_cell['future'] ? 'x' : heat_level($heat_cell['c'], $heat_max_u) ?>" title="<?= (int)$heat_cell['c'] ?> new users · <?= sanitize($heat_cell['label']) ?>" aria-hidden="true"></span><?php endforeach; ?><?php endforeach; ?>
                        </div>
                        <div class="gh-legend" aria-hidden="true"><span>Less</span><span class="gh-cell gh-a0"></span><span class="gh-cell gh-a1"></span><span class="gh-cell gh-a2"></span><span class="gh-cell gh-a3"></span><span class="gh-cell gh-a4"></span><span>More</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top + recent -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="ad-card tf-reveal glass-panel rounded-2xl border border-dark-700 overflow-hidden" style="--d:.05s">
                <div class="p-5 border-b border-dark-700 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-white">Top 10 by best WPM</h3>
                    <a href="users.php" class="text-[11px] text-amber-400 font-bold hover:underline">Moderate →</a>
                </div>
                <table class="ad-table w-full text-left text-xs">
                    <thead><tr class="border-b border-dark-700 bg-dark-800/50 text-gray-500 uppercase font-mono"><th class="py-2.5 px-4">#</th><th class="py-2.5 px-4">User</th><th class="py-2.5 px-4">Best</th><th class="py-2.5 px-4">Acc</th><th class="py-2.5 px-4 text-right">Tests</th></tr></thead>
                    <tbody class="divide-y divide-dark-700/50 font-mono">
                        <?php foreach ($top as $i => $r): ?>
                        <tr class="hover:bg-dark-800/40<?= $i === 0 ? ' ad-gold' : '' ?>">
                            <td class="py-2.5 px-4 text-gray-500"><?= $i + 1 ?></td>
                            <td class="py-2.5 px-4 text-gray-200"><?= sanitize($r['username']) ?> <span class="text-[10px] text-gray-500">(<?= sanitize($r['status']) ?>)</span></td>
                            <td class="py-2.5 px-4 text-emerald-400 font-bold"><?= (int)$r['best'] ?></td>
                            <td class="py-2.5 px-4 text-cyan-400"><?= $r['acc'] ?>%</td>
                            <td class="py-2.5 px-4 text-right text-gray-400"><?= number_format((int)$r['tests']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($top)): ?><tr><td colspan="5" class="py-6 text-center text-gray-500">No sessions yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="ad-card tf-reveal glass-panel rounded-2xl border border-dark-700 overflow-hidden" style="--d:.12s">
                <div class="p-5 border-b border-dark-700"><h3 class="text-sm font-bold text-white">Latest sessions</h3></div>
                <table class="ad-table w-full text-left text-xs">
                    <thead><tr class="border-b border-dark-700 bg-dark-800/50 text-gray-500 uppercase font-mono"><th class="py-2.5 px-4">User</th><th class="py-2.5 px-4">Mode</th><th class="py-2.5 px-4">WPM</th><th class="py-2.5 px-4 text-right">When</th></tr></thead>
                    <tbody class="divide-y divide-dark-700/50 font-mono">
                        <?php foreach ($recent_sessions as $r): ?>
                        <tr class="hover:bg-dark-800/40">
                            <td class="py-2.5 px-4 text-gray-200"><?= sanitize($r['username']) ?></td>
                            <td class="py-2.5 px-4 text-gray-400"><?= sanitize($r['mode']) ?></td>
                            <td class="py-2.5 px-4 text-emerald-400 font-bold"><?= (int)$r['wpm'] ?> <span class="text-gray-500 font-normal"><?= $r['accuracy'] ?>%</span></td>
                            <td class="py-2.5 px-4 text-right text-gray-500"><?= date('M j H:i', strtotime($r['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_sessions)): ?><tr><td colspan="4" class="py-6 text-center text-gray-500">No sessions yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Audit trail + achievement review -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="ad-card tf-reveal glass-panel rounded-2xl border border-dark-700 overflow-hidden" style="--d:.05s">
                <div class="p-5 border-b border-dark-700 flex items-center justify-between">
                    <div><h3 class="text-sm font-bold text-white"><i class="fa-solid fa-clipboard-list mr-2 text-amber-400"></i>Audit Trail</h3>
                    <p class="text-[11px] text-gray-500">Last 8 admin operations — who did what</p></div>
                </div>
                <table class="ad-table w-full text-left text-xs">
                    <tbody class="divide-y divide-dark-700/50 font-mono">
                        <?php foreach ($audit_feed as $a): ?>
                        <tr class="hover:bg-dark-800/40">
                            <td class="py-2.5 px-4 text-amber-300"><?= sanitize($a['admin_name'] ?: '?') ?></td>
                            <td class="py-2.5 px-4 text-gray-200"><?= sanitize($a['action']) ?> <span class="text-gray-500"><?= sanitize($a['target_type']) ?>#<?= (int)$a['target_id'] ?></span></td>
                            <td class="py-2.5 px-4 text-right text-gray-500"><?= date('M j H:i', strtotime($a['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($audit_feed)): ?><tr><td class="py-6 text-center text-gray-500">No admin actions logged yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="ad-card tf-reveal glass-panel rounded-2xl border border-dark-700 overflow-hidden" style="--d:.12s">
                <div class="p-5 border-b border-dark-700">
                    <h3 class="text-sm font-bold text-white"><i class="fa-solid fa-medal mr-2 text-amber-400"></i>Achievements Review</h3>
                    <p class="text-[11px] text-gray-500"><?= count($ach_review) ?> defined · grants show whether the pipeline fires</p>
                </div>
                <table class="ad-table w-full text-left text-xs">
                    <tbody class="divide-y divide-dark-700/50 font-mono">
                        <?php foreach ($ach_review as $a): ?>
                        <tr class="hover:bg-dark-800/40">
                            <td class="py-2.5 px-4 text-gray-200"><?= sanitize($a['title']) ?> <span class="text-gray-500"><?= sanitize($a['code']) ?></span></td>
                            <td class="py-2.5 px-4 text-gray-500"><?= sanitize($a['criteria_type']) ?> <?= $a['criteria_value'] ?></td>
                            <td class="py-2.5 px-4 text-right <?= (int)$a['grants'] > 0 ? 'text-emerald-400 font-bold' : 'text-red-400 font-bold' ?>"><?= (int)$a['grants'] ?> grants</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Admin-only recommendations -->
        <div class="glass-panel p-6 rounded-2xl border border-amber-500/30 bg-amber-500/5">
            <h3 class="text-sm font-bold text-amber-300"><i class="fa-solid fa-list-check mr-2"></i>Admin-only recommendations</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 text-xs">
                <div class="bg-dark-900/60 border border-dark-700 rounded-xl p-4">
                    <p class="font-bold text-white mb-1">1. Moderate users weekly <span class="text-emerald-400 font-mono">· live</span></p>
                    <p class="text-gray-400">Review suspended/banned queue in <a href="users.php" class="text-amber-400 underline">Manage Users</a>. Self-moderation + last-admin demote are blocked server-side. Login throttle: 5 fails / 15 min.</p>
                </div>
                <div class="bg-dark-900/60 border border-dark-700 rounded-xl p-4">
                    <p class="font-bold text-white mb-1">2. Curate typing texts <span class="text-emerald-400 font-mono">· live</span></p>
                    <p class="text-gray-400">Active quote/code rows now rotate via DB in <a href="texts.php" class="text-amber-400 underline">Typing Texts</a>. Deactivate low-quality ones; words/time still use the generator.</p>
                </div>
                <div class="bg-dark-900/60 border border-dark-700 rounded-xl p-4">
                    <p class="font-bold text-white mb-1">3. Backups + audit <span class="text-emerald-400 font-mono">· live</span></p>
                    <p class="text-gray-400">Every admin op lands in <span class="font-mono">admin_audit_log</span> (see feed above). Still manual: schedule <span class="font-mono">mysqldump typeforge</span> before bulk role changes.</p>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
