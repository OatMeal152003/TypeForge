<?php
// TypeForge Admin — achievement manager. Admin-only.
// Add / activate / deactivate achievements; revoke individual grants. Never deletes.
$base_prefix = '../';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$db = Database::getConnection();
$csrf_token = generateCsrfToken();
$flash = sanitize($_GET['msg'] ?? '');
$err = sanitize($_GET['err'] ?? '');

$achs = $db->query("SELECT a.id, a.code, a.title, a.description, a.icon, a.criteria_type, a.criteria_value, a.points, a.is_active, COUNT(ua.id) AS grants FROM achievements a LEFT JOIN user_achievements ua ON ua.achievement_id = a.id GROUP BY a.id ORDER BY a.points ASC")->fetchAll(PDO::FETCH_ASSOC);
$active_n = 0;
$total_grants = 0;
foreach ($achs as $a) {
    if (!empty($a['is_active'])) $active_n++;
    $total_grants += (int)$a['grants'];
}
$recent = $db->query("SELECT ua.user_id, ua.achievement_id, ua.unlocked_at, u.username, a.code, a.title FROM user_achievements ua JOIN users u ON u.id = ua.user_id JOIN achievements a ON a.id = ua.achievement_id ORDER BY ua.unlocked_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Achievements — Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen bg-dark-900 text-gray-200">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
    <main class="flex-1 p-6 md:p-10 space-y-6 overflow-y-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-mono text-amber-400 uppercase tracking-widest">Admin only · badge management</p>
                <h1 class="text-2xl font-bold text-white mt-1">Achievements <span class="text-sm text-gray-500 font-mono">(<?= count($achs) ?> defined · <?= $active_n ?> live · <?= $total_grants ?> grants)</span></h1>
                <p class="text-xs text-gray-400 mt-1">Live badges fire from the server-side pipeline on every accepted save. Deactivation retires a badge — earned grants are preserved, never deleted.</p>
            </div>
            <a href="dashboard.php" class="ad-btn px-4 py-2.5 text-xs font-bold text-gray-300 bg-dark-800 border border-dark-600 rounded-xl hover:bg-dark-700 transition">← Console</a>
        </div>

        <?php if ($flash): ?><div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-xl text-xs"><?= $flash ?></div><?php endif; ?>
        <?php if ($err): ?><div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-xl text-xs"><?= $err ?></div><?php endif; ?>

        <div class="ad-card tf-reveal glass-panel rounded-2xl border border-dark-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="ad-table w-full text-left text-xs">
                    <thead><tr class="border-b border-dark-700 bg-dark-800/60 text-gray-500 uppercase font-mono">
                        <th class="py-3 px-4">Badge</th><th class="py-3 px-4">Criteria</th><th class="py-3 px-4">Target</th>
                        <th class="py-3 px-4">Points</th><th class="py-3 px-4">Status</th><th class="py-3 px-4">Grants</th><th class="py-3 px-4 text-right">Action</th>
                    </tr></thead>
                    <tbody class="divide-y divide-dark-700/50">
                        <?php foreach ($achs as $a): ?>
                        <tr class="hover:bg-dark-800/40">
                            <td class="py-3 px-4"><span class="font-bold text-gray-100"><i class="fa-solid <?= sanitize($a['icon']) ?> text-amber-400 mr-2"></i><?= sanitize($a['title']) ?></span><br><span class="text-gray-500 font-mono"><?= sanitize($a['code']) ?></span></td>
                            <td class="py-3 px-4 font-mono text-gray-300"><?= sanitize($a['criteria_type']) ?></td>
                            <td class="py-3 px-4 font-mono text-gray-400"><?= $a['criteria_value'] ?></td>
                            <td class="py-3 px-4 font-mono text-emerald-400 font-bold"><?= (int)$a['points'] ?></td>
                            <td class="py-3 px-4 font-mono"><span class="px-2 py-0.5 rounded <?= $a['is_active'] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-dark-700 text-gray-500' ?>"><?= $a['is_active'] ? 'live' : 'retired' ?></span></td>
                            <td class="py-3 px-4 font-mono <?= (int)$a['grants'] > 0 ? 'text-emerald-400 font-bold' : 'text-gray-500' ?>"><?= (int)$a['grants'] ?></td>
                            <td class="py-3 px-4 text-right">
                                <form method="POST" action="actions.php" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token) ?>">
                                    <input type="hidden" name="back" value="achievements.php">
                                    <input type="hidden" name="achievement_id" value="<?= (int)$a['id'] ?>">
                                    <?php if ($a['is_active']): ?>
                                        <button name="op" value="ach_deactivate" class="px-2.5 py-1.5 rounded-lg bg-dark-700 text-gray-300 hover:bg-red-500/20 hover:text-red-300">Retire</button>
                                    <?php else: ?>
                                        <button name="op" value="ach_activate" class="px-2.5 py-1.5 rounded-lg bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25">Relist</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($achs)): ?><tr><td colspan="7" class="py-10 text-center text-gray-500">No achievements defined.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ad-card tf-reveal glass-panel p-6 rounded-2xl border border-dark-700" style="--d:.08s">
            <h3 class="text-sm font-bold text-white mb-1">Add new achievement</h3>
            <p class="text-[11px] text-gray-500 mb-4">Goes live immediately. Codes are permanent — pick carefully.</p>
            <form method="POST" action="actions.php" class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token) ?>">
                <input type="hidden" name="back" value="achievements.php">
                <input type="hidden" name="op" value="ach_add">
                <input name="code" required maxlength="30" placeholder="CODE_LIKE_THIS" class="px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-xl text-white font-mono focus:outline-none focus:border-amber-400">
                <input name="title" required maxlength="100" placeholder="Title (e.g. Week Warrior)" class="px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-xl text-white focus:outline-none focus:border-amber-400">
                <input name="icon" maxlength="50" placeholder="fa-medal" class="px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-xl text-white font-mono focus:outline-none focus:border-amber-400">
                <input name="description" required maxlength="255" placeholder="What the player did to earn it" class="px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-xl text-white focus:outline-none focus:border-amber-400 md:col-span-3">
                <select name="criteria_type" class="px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-xl text-gray-300">
                    <option value="tests_count">tests_count</option>
                    <option value="wpm_reach">wpm_reach</option>
                    <option value="accuracy_reach">accuracy_reach</option>
                    <option value="streak_days">streak_days</option>
                    <option value="invaders_score">invaders_score</option>
                    <option value="blocks_score">blocks_score</option>
                </select>
                <input name="criteria_value" required type="number" min="1" max="100000" step="any" placeholder="Target (e.g. 7)" class="px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-xl text-white font-mono focus:outline-none focus:border-amber-400">
                <input name="points" type="number" min="1" max="1000" value="10" placeholder="Points" class="px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-xl text-white font-mono focus:outline-none focus:border-amber-400">
                <div class="md:col-span-3"><button class="ad-btn px-5 py-2.5 bg-amber-400 text-dark-900 font-bold rounded-xl hover:bg-amber-300">Add achievement</button></div>
            </form>
        </div>

        <div class="ad-card tf-reveal glass-panel rounded-2xl border border-dark-700 overflow-hidden" style="--d:.12s">
            <div class="p-5 border-b border-dark-700"><h3 class="text-sm font-bold text-white">Latest grants</h3>
            <p class="text-[11px] text-gray-500">Revoking removes the badge; the pipeline may re-award it on the next qualifying save.</p></div>
            <div class="overflow-x-auto">
                <table class="ad-table w-full text-left text-xs">
                    <tbody class="divide-y divide-dark-700/50 font-mono">
                        <?php foreach ($recent as $g): ?>
                        <tr class="hover:bg-dark-800/40">
                            <td class="py-2.5 px-4 text-gray-200"><?= sanitize($g['username']) ?></td>
                            <td class="py-2.5 px-4 text-amber-300"><?= sanitize($g['code']) ?> <span class="text-gray-500"><?= sanitize($g['title']) ?></span></td>
                            <td class="py-2.5 px-4 text-gray-500"><?= date('M j H:i', strtotime($g['unlocked_at'])) ?></td>
                            <td class="py-2.5 px-4 text-right">
                                <form method="POST" action="actions.php" class="inline" onsubmit="return confirm('Revoke this grant?');">
                                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token) ?>">
                                    <input type="hidden" name="back" value="achievements.php">
                                    <input type="hidden" name="op" value="ach_revoke">
                                    <input type="hidden" name="achievement_id" value="<?= (int)$g['achievement_id'] ?>">
                                    <input type="hidden" name="grant_user_id" value="<?= (int)$g['user_id'] ?>">
                                    <button class="px-2.5 py-1.5 rounded-lg bg-dark-700 text-gray-300 hover:bg-red-500/20 hover:text-red-300">Revoke</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent)): ?><tr><td class="py-6 text-center text-gray-500">No grants yet — take a typing test as a player.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
