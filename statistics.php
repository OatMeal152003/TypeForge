<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requirePlayer();

$user_id = getCurrentUserId();
$db = Database::getConnection();

 
$stmt_history = $db->prepare("
    SELECT wpm, raw_wpm, accuracy, created_at
    FROM typing_sessions
    WHERE user_id = :user_id
    ORDER BY created_at ASC
    LIMIT 50
");
$stmt_history->execute(['user_id' => $user_id]);
$chart_data = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

 
$labels    = [];
$wpm_data  = [];
$raw_data  = [];
$acc_data  = [];

foreach ($chart_data as $row) {
    $labels[]   = date('M j, H:i', strtotime($row['created_at']));
    $wpm_data[] = (int)$row['wpm'];
    $raw_data[] = (int)$row['raw_wpm'];
    $acc_data[] = (float)$row['accuracy'];
}

$page_title = 'Performance Statistics — TypeForge';
require_once __DIR__ . '/includes/header.php';
?>

<div class="flex min-h-screen bg-dark-900 text-gray-200">
    
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    
    <main class="flex-1 p-6 md:p-10 space-y-8 overflow-y-auto">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Performance Analytics</h1>
            <p class="text-xs text-gray-400 mt-1">Visualize your speed trends and accuracy consistency over time.</p>
        </div>

        
        <div class="glass-panel p-6 rounded-2xl border border-dark-700 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white">Speed Progression (WPM)</h3>
                    <p class="text-xs text-gray-400">Net WPM vs Raw WPM across your last 50 tests</p>
                </div>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="wpmChart"></canvas>
            </div>
        </div>

        
        <div class="glass-panel p-6 rounded-2xl border border-dark-700 space-y-4">
            <div>
                <h3 class="text-base font-bold text-white">Accuracy History (%)</h3>
                <p class="text-xs text-gray-400">Consistency and precision rate per test session</p>
            </div>
            <div class="relative h-60 w-full">
                <canvas id="accuracyChart"></canvas>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const labels = <?= json_encode($labels) ?>;
    const wpmData = <?= json_encode($wpm_data) ?>;
    const rawData = <?= json_encode($raw_data) ?>;
    const accData = <?= json_encode($acc_data) ?>;

    const ctxWpm = document.getElementById('wpmChart').getContext('2d');
    new Chart(ctxWpm, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Net WPM',
                    data: wpmData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Raw WPM',
                    data: rawData,
                    borderColor: '#6b7280',
                    borderWidth: 1.5,
                    borderDash: [4, 4],
                    fill: false,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#9ca3af', font: { family: 'monospace', size: 11 } } }
            },
            scales: {
                x: { ticks: { color: '#6b7280', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { ticks: { color: '#6b7280', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true }
            }
        }
    });

    const ctxAcc = document.getElementById('accuracyChart').getContext('2d');
    new Chart(ctxAcc, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Accuracy (%)',
                    data: accData,
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6, 182, 212, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#9ca3af', font: { family: 'monospace', size: 11 } } }
            },
            scales: {
                x: { ticks: { color: '#6b7280', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { ticks: { color: '#6b7280', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' }, min: 50, max: 100 }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>