<?php
$base_prefix = $base_prefix ?? '';
// Marketing footer renders ONLY on landing page (index.php).
// App/auth/admin pages just need closing tags — no footer UI.
$__footer_page = $current_script ?? basename($_SERVER['PHP_SELF'] ?? '');
if ($__footer_page !== 'index.php') {
    echo "</body>\n</html>";
    return;
}
?>
    
    <footer class="mt-auto bg-dark-800 border-t border-dark-700 py-12 text-sm text-gray-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div class="space-y-4 md:col-span-1">
                    <div class="flex items-center gap-2">
                        <img src="<?= $base_prefix ?>images/logo_TypeForge.png" alt="TypeForge logo" class="w-8 h-8 rounded-lg object-contain bg-white/5 border border-emerald-500/20">
                        <span class="text-lg font-bold text-white font-mono">TypeForge</span>
                    </div>
                    <p class="text-xs text-gray-400 leading-relaxed">
                        A modern, developer-grade platform engineered to boost your typing velocity, accuracy, and muscle memory.
                    </p>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-3">Product</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="<?= $base_prefix ?>typing-test.php" class="hover:text-emerald-400 transition">Typing Practice</a></li>
                        <li><a href="<?= $base_prefix ?>leaderboard.php" class="hover:text-emerald-400 transition">Global Leaderboard</a></li>
                        <li><a href="<?= $base_prefix ?>statistics.php" class="hover:text-emerald-400 transition">Analytics Engine</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-3">Modes</h4>
                    <ul class="space-y-2 text-xs">
                        <li><span class="text-gray-400">Time Benchmark</span></li>
                        <li><span class="text-gray-400">Word Speed Test</span></li>
                        <li><span class="text-gray-400">Code Syntax Mode</span></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-3">System</h4>
                    <p class="text-xs text-gray-400 mb-2">Powered by PHP 8, Vanilla JS, and MySQL.</p>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-mono bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> v1.0.0 Online
                    </span>
                </div>
            </div>
            <div class="pt-8 border-t border-dark-700 flex flex-col md:flex-row justify-between items-center gap-4 text-xs">
                <p>&copy; <?= date('Y') ?> TypeForge. All rights reserved.</p>
                <div class="flex gap-6">
                    <a href="#" class="hover:text-emerald-400 transition">Privacy Policy</a>
                    <a href="#" class="hover:text-emerald-400 transition">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>