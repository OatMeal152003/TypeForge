<?php
// TypeForge Admin — typing text curation. Admin-only.
// Controls the DB rotation used by api/get-text.php + api/v1 texts for quote/code.
$base_prefix = '../';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();
$db = Database::getConnection();
$csrf_token = generateCsrfToken();
$flash = sanitize($_GET['msg'] ?? '');
$err = sanitize($_GET['err'] ?? '');

$cat_f = $_GET['category'] ?? 'all';
if (!in_array($cat_f, ['all', 'words', 'quote', 'code', 'custom'], true)) $cat_f = 'all';
$where = $cat_f === 'all' ? '1=1' : 'category = :cat';
$stmt = $db->prepare("SELECT id, category, difficulty, title, content, length_words, is_active, created_at FROM typing_texts WHERE $where ORDER BY created_at DESC");
if ($cat_f === 'all') $stmt->execute(); else $stmt->execute([':cat' => $cat_f]);
$texts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$active_n = (int)$db->query("SELECT COUNT(*) FROM typing_texts WHERE is_active = 1")->fetchColumn();

$page_title = 'Typing Texts — Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex min-h-screen bg-dark-900 text-gray-200">
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
    <main class="flex-1 p-6 md:p-10 space-y-6 overflow-y-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-mono text-amber-400 uppercase tracking-widest">Admin only · content curation</p>
                <h1 class="text-2xl font-bold text-white mt-1">Typing Texts <span class="text-sm text-gray-500 font-mono">(<?= count($texts) ?> shown · <?= $active_n ?> active in rotation)</span></h1>
                <p class="text-xs text-gray-400 mt-1">Active <span class="font-mono text-emerald-400">quote</span> / <span class="font-mono text-emerald-400">code</span> rows rotate via <span class="font-mono">api/get-text.php</span> + <span class="font-mono">api/v1 texts</span>. Words/time modes use the generator (variable length).</p>
            </div>
            <a href="dashboard.php" class="px-4 py-2.5 text-xs font-bold text-gray-300 bg-dark-800 border border-dark-600 rounded-xl hover:bg-dark-700 transition">← Console</a>
        </div>

        <?php if ($flash): ?><div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-xl text-xs"><?= $flash ?></div><?php endif; ?>
        <?php if ($err): ?><div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-xl text-xs"><?= $err ?></div><?php endif; ?>

        <div class="flex gap-2 text-xs font-mono">
            <?php foreach (['all', 'quote', 'code', 'words', 'custom'] as $c): ?>
                <a href="texts.php?category=<?= $c ?>" class="px-3 py-1.5 rounded-lg border <?= $cat_f === $c ? 'bg-amber-400 text-dark-900 font-bold border-amber-400' : 'bg-dark-800 text-gray-400 border-dark-700 hover:text-white' ?>"><?= $c ?></a>
            <?php endforeach; ?>
        </div>

        <div class="ad-card tf-reveal glass-panel rounded-2xl border border-dark-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="ad-table w-full text-left text-xs">
                    <thead><tr class="border-b border-dark-700 bg-dark-800/60 text-gray-500 uppercase font-mono">
                        <th class="py-3 px-4">Title</th><th class="py-3 px-4">Category</th><th class="py-3 px-4">Difficulty</th>
                        <th class="py-3 px-4">Words</th><th class="py-3 px-4">Status</th><th class="py-3 px-4 text-right">Action</th>
                    </tr></thead>
                    <tbody class="divide-y divide-dark-700/50">
                        <?php foreach ($texts as $t): ?>
                        <tr class="hover:bg-dark-800/40">
                            <td class="py-3 px-4"><span class="font-bold text-gray-100"><?= sanitize($t['title']) ?></span><br><span class="text-gray-500 font-mono"><?= sanitize(substr($t['content'], 0, 80)) ?>…</span></td>
                            <td class="py-3 px-4 font-mono text-gray-300"><?= sanitize($t['category']) ?></td>
                            <td class="py-3 px-4 font-mono text-gray-400"><?= sanitize($t['difficulty']) ?></td>
                            <td class="py-3 px-4 font-mono text-gray-400"><?= (int)$t['length_words'] ?></td>
                            <td class="py-3 px-4 font-mono"><span class="px-2 py-0.5 rounded <?= $t['is_active'] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-dark-700 text-gray-500' ?>"><?= $t['is_active'] ? 'active' : 'off' ?></span></td>
                            <td class="py-3 px-4 text-right">
                                <form method="POST" action="actions.php" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token) ?>">
                                    <input type="hidden" name="text_id" value="<?= (int)$t['id'] ?>">
                                    <?php if ($t['is_active']): ?>
                                        <button name="op" value="text_deactivate" class="px-2.5 py-1.5 rounded-lg bg-dark-700 text-gray-300 hover:bg-red-500/20 hover:text-red-300">Deactivate</button>
                                    <?php else: ?>
                                        <button name="op" value="text_activate" class="px-2.5 py-1.5 rounded-lg bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25">Activate</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($texts)): ?><tr><td colspan="6" class="py-10 text-center text-gray-500">No texts in this category.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ad-card tf-reveal glass-panel p-6 rounded-2xl border border-dark-700" style="--d:.08s">
            <h3 class="text-sm font-bold text-white mb-1">Add new text</h3>
            <p class="text-[11px] text-gray-500 mb-4">Goes live in rotation immediately. Keep passages clean — no secrets or credentials.</p>
            <form method="POST" action="actions.php" class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrf_token) ?>">
                <input type="hidden" name="op" value="text_add">
                <input name="title" required maxlength="150" placeholder="Title (e.g. PHP Nullsafe Chains)" class="px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-xl text-white focus:outline-none focus:border-amber-400 md:col-span-2">
                <select name="category" class="px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-xl text-gray-300">
                    <option value="quote">quote</option><option value="code">code</option><option value="words">words</option><option value="custom">custom</option>
                </select>
                <select name="difficulty" class="px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-xl text-gray-300">
                    <option value="beginner">beginner</option><option value="intermediate" selected>intermediate</option><option value="advanced">advanced</option>
                </select>
                <textarea name="content" required rows="3" maxlength="5000" placeholder="Passage content…" class="px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-xl text-white focus:outline-none focus:border-amber-400 md:col-span-2"></textarea>
                <button class="px-4 py-2.5 bg-amber-400 text-dark-900 font-bold rounded-xl hover:bg-amber-300 md:col-span-2">Add to rotation</button>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
