<?php
// TypeForge Admin — user management. Admin-only.
$base_prefix = '../';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$db = Database::getConnection();
$csrf_token = generateCsrfToken();
$me = getCurrentUserId();
$flash = sanitize($_GET['msg'] ?? '');
$err = sanitize($_GET['err'] ?? '');

$q = trim((string)($_GET['q'] ?? ''));
$role_f = $_GET['role'] ?? 'all';
$status_f = $_GET['status'] ?? 'all';
if (!in_array($role_f, ['all', 'user', 'admin'], true)) $role_f = 'all';
if (!in_array($status_f, ['all', 'active', 'suspended', 'banned'], true)) $status_f = 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$where = ['1=1'];
$params = [];
if ($q !== '') { $where[] = '(u.username LIKE :q OR u.email LIKE :q OR u.full_name LIKE :q)'; $params[':q'] = '%' . $q . '%'; }
if ($role_f !== 'all') { $where[] = 'u.role = :role'; $params[':role'] = $role_f; }
if ($status_f !== 'all') { $where[] = 'u.status = :status'; $params[':status'] = $status_f; }
$where_sql = implode(' AND ', $where);

$stmt_c = $db->prepare("SELECT COUNT(*) FROM users u WHERE $where_sql");
$stmt_c->execute($params);
$total = (int)$stmt_c->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

$stmt = $db->prepare("SELECT u.id, u.full_name, u.username, u.email, u.role, u.status, u.created_at, COUNT(t.id) AS tests, COALESCE(MAX(t.wpm),0) AS best FROM users u LEFT JOIN typing_sessions t ON t.user_id=u.id WHERE $where_sql GROUP BY u.id ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function u_url(array $o = []): string {
    $b = ['q' => $GLOBALS['q'], 'role' => $GLOBALS['role_f'], 'status' => $GLOBALS['status_f'], 'page' => $GLOBALS['page']];
    return 'users.php?' . http_build_query(array_filter(array_merge($b, $o), fn($v) => $v !== '' && $v !== null));
}

$page_title = 'Manage Users — Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen bg-dark-900 text-gray-200">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
    <main class="flex-1 p-6 md:p-10 space-y-6 overflow-y-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-mono text-amber-400 uppercase tracking-widest">Admin only · user management</p>
                <h1 class="text-2xl font-bold text-white mt-1">Users <span class="text-sm text-gray-500 font-mono">(<?= number_format($total) ?>)</span></h1>
            </div>
            <a href="dashboard.php" class="px-4 py-2.5 text-xs font-bold text-gray-300 bg-dark-800 border border-dark-600 rounded-xl hover:bg-dark-700 transition">← Console</a>
        </div>

        <?php if ($flash): ?><div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-xl text-xs"><?= $flash ?></div><?php endif; ?>
        <?php if ($err): ?><div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-xl text-xs"><?= $err ?></div><?php endif; ?>

        <form method="GET" class="ad-card tf-reveal glass-panel p-4 rounded-2xl border border-dark-700 flex flex-wrap gap-3 items-center text-xs">
            <input name="q" value="<?= sanitize($q) ?>" placeholder="Search username / email / name" class="px-3 py-2 bg-dark-800 border border-dark-600 rounded-xl text-white w-64 focus:outline-none focus:border-amber-400">
            <select name="role" class="px-3 py-2 bg-dark-800 border border-dark-600 rounded-xl text-gray-300">
                <?php foreach (['all', 'user', 'admin'] as $r): ?><option value="<?= $r ?>" <?= $role_f === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?>
            </select>
            <select name="status" class="px-3 py-2 bg-dark-800 border border-dark-600 rounded-xl text-gray-300">
                <?php foreach (['all', 'active', 'suspended', 'banned'] as $s): ?><option value="<?= $s ?>" <?= $status_f === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
            </select>
            <button class="ad-btn px-4 py-2 bg-amber-400 text-dark-900 font-bold rounded-xl hover:bg-amber-300">Filter</button>
            <a href="users.php" class="text-gray-500 hover:text-red-400 underline">reset</a>
        </form>

        <div class="ad-card tf-reveal glass-panel rounded-2xl border border-dark-700 overflow-hidden" style="--d:.08s">
            <div class="overflow-x-auto">
                <table class="ad-table w-full text-left text-xs">
                    <thead><tr class="border-b border-dark-700 bg-dark-800/60 text-gray-500 uppercase font-mono">
                        <th class="py-3 px-4">User</th><th class="py-3 px-4">Role</th><th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Tests / Best</th><th class="py-3 px-4">Joined</th><th class="py-3 px-4 text-right">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-dark-700/50">
                        <?php foreach ($rows as $u): $self = ((int)$u['id'] === (int)$me); ?>
                        <tr class="hover:bg-dark-800/40 <?= $self ? 'bg-amber-400/5' : '' ?>">
                            <td class="py-3 px-4"><span class="font-bold text-gray-100"><?= sanitize($u['username']) ?></span><?= $self ? ' <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-400/20 text-amber-300 font-mono">YOU</span>' : '' ?><br><span class="text-gray-500"><?= sanitize($u['full_name']) ?> · <?= sanitize($u['email']) ?></span></td>
                            <td class="py-3 px-4 font-mono"><span class="px-2 py-0.5 rounded <?= $u['role'] === 'admin' ? 'bg-amber-500/15 text-amber-300 border border-amber-500/30' : 'bg-dark-700 text-gray-300' ?>"><?= sanitize($u['role']) ?></span></td>
                            <td class="py-3 px-4 font-mono"><span class="px-2 py-0.5 rounded <?= $u['status'] === 'active' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-red-500/15 text-red-300' ?>"><?= sanitize($u['status']) ?></span></td>
                            <td class="py-3 px-4 font-mono text-gray-400"><?= number_format((int)$u['tests']) ?> / <span class="text-emerald-400 font-bold"><?= (int)$u['best'] ?></span></td>
                            <td class="py-3 px-4 text-gray-500"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                            <td class="py-3 px-4 text-right">
                                <?php if (!$self): ?>
                                <form method="POST" action="actions.php" class="inline-flex gap-1.5 justify-end">
                                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token) ?>">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <?php if ($u['status'] === 'active'): ?>
                                        <button name="op" value="suspend" class="px-2.5 py-1.5 rounded-lg bg-dark-700 text-gray-300 hover:bg-red-500/20 hover:text-red-300" title="Suspend">Suspend</button>
                                    <?php else: ?>
                                        <button name="op" value="activate" class="px-2.5 py-1.5 rounded-lg bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25" title="Activate">Activate</button>
                                    <?php endif; ?>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <button name="op" value="demote" class="px-2.5 py-1.5 rounded-lg bg-dark-700 text-gray-300 hover:bg-dark-600" title="Demote to user">Demote</button>
                                    <?php else: ?>
                                        <button name="op" value="promote" class="px-2.5 py-1.5 rounded-lg bg-amber-500/15 text-amber-300 hover:bg-amber-500/25" title="Promote to admin">Promote</button>
                                    <?php endif; ?>
                                </form>
                                <?php else: ?><span class="text-[11px] text-gray-600 font-mono">self — no actions</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rows)): ?><tr><td colspan="6" class="py-10 text-center text-gray-500">No users match.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pages > 1): ?>
            <div class="p-4 border-t border-dark-700 flex items-center justify-between text-xs font-mono">
                <span class="text-gray-500">Page <?= $page ?> of <?= $pages ?></span>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?><a href="<?= u_url(['page' => $page - 1]) ?>" class="px-3 py-1.5 bg-dark-800 border border-dark-700 rounded-lg text-gray-300">Previous</a><?php endif; ?>
                    <?php if ($page < $pages): ?><a href="<?= u_url(['page' => $page + 1]) ?>" class="px-3 py-1.5 bg-dark-800 border border-dark-700 rounded-lg text-gray-300">Next</a><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <p class="text-[11px] text-gray-600 font-mono">Safety: self-actions blocked server-side. Role changes take effect on next request (session role refreshed at login).</p>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
