<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requirePlayer();

$user_id = getCurrentUserId();
$db = Database::getConnection();

 
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

 
$stmt_count = $db->prepare("SELECT COUNT(id) FROM typing_sessions WHERE user_id = :user_id");
$stmt_count->execute(['user_id' => $user_id]);
$total_records = (int)$stmt_count->fetchColumn();
$total_pages   = max(1, (int)ceil($total_records / $limit));
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

 
$stmt_history = $db->prepare("
    SELECT id, mode, wpm, raw_wpm, accuracy, duration_seconds, total_chars AS total_keystrokes, incorrect_chars AS error_count, created_at
    FROM typing_sessions
    WHERE user_id = :user_id
    ORDER BY created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt_history->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt_history->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt_history->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_history->execute();
$history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Typing History — TypeForge';
require_once __DIR__ . '/includes/header.php';
?>

<div class="flex min-h-screen bg-dark-900 text-gray-200">
    
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    
    <main class="flex-1 p-6 md:p-10 space-y-8 overflow-y-auto">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Typing History</h1>
                <p class="text-xs text-gray-400 mt-1">Full log of all completed typing test attempts (<?= number_format($total_records) ?> total)</p>
            </div>
            <a href="typing-test.php" class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-dark-900 bg-emerald-400 rounded-xl hover:bg-emerald-300 transition">
                <i class="fa-solid fa-plus text-xs"></i> New Test
            </a>
        </div>

        <div class="glass-panel rounded-2xl border border-dark-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-dark-700 bg-dark-800/50 text-gray-400 uppercase font-mono">
                            <th class="py-3.5 px-4">#</th>
                            <th class="py-3.5 px-4">Mode</th>
                            <th class="py-3.5 px-4">Net WPM</th>
                            <th class="py-3.5 px-4">Raw WPM</th>
                            <th class="py-3.5 px-4">Accuracy</th>
                            <th class="py-3.5 px-4">Keystrokes</th>
                            <th class="py-3.5 px-4">Errors</th>
                            <th class="py-3.5 px-4">Duration</th>
                            <th class="py-3.5 px-4 text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-700/50">
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="9" class="py-8 text-center text-gray-500">
                                    No test history found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $idx => $row): ?>
                                <tr class="hover:bg-dark-800/30 transition">
                                    <td class="py-3 px-4 font-mono text-gray-500">
                                        <?= $offset + $idx + 1 ?>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-gray-300">
                                        <span class="px-2 py-0.5 rounded bg-dark-700 border border-dark-600 font-bold uppercase text-[10px]">
                                            <?= sanitize($row['mode']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-mono font-bold text-emerald-400 text-sm">
                                        <?= (int)$row['wpm'] ?>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-gray-400">
                                        <?= (int)$row['raw_wpm'] ?>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-cyan-400 font-semibold">
                                        <?= $row['accuracy'] ?>%
                                    </td>
                                    <td class="py-3 px-4 font-mono text-gray-400">
                                        <?= (int)$row['total_keystrokes'] ?>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-red-400">
                                        <?= (int)$row['error_count'] ?>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-gray-400">
                                        <?= (int)$row['duration_seconds'] ?>s
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-500">
                                        <?= date('M j, Y g:i A', strtotime($row['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            
            <?php if ($total_pages > 1): ?>
                <div class="p-4 border-t border-dark-700 flex items-center justify-between text-xs font-mono">
                    <span class="text-gray-500">Page <?= $page ?> of <?= $total_pages ?></span>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="px-3 py-1.5 bg-dark-800 border border-dark-700 rounded-lg text-gray-300 hover:bg-dark-700">Previous</a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="px-3 py-1.5 bg-dark-800 border border-dark-700 rounded-lg text-gray-300 hover:bg-dark-700">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>