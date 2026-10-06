<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requirePlayer();

$csrf_token = generateCsrfToken();
$page_title = 'Typing Test — TypeForge';
require_once __DIR__ . '/includes/header.php';
?>

<script>
    window.CSRF_TOKEN = "<?= sanitize($csrf_token) ?>";
</script>

<div class="flex min-h-screen bg-dark-900 text-gray-200">
    
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    
    <main class="flex-1 p-6 md:p-10 flex flex-col items-center justify-center relative">
        
        
        <div class="zen-dim glass-panel px-6 py-2.5 rounded-2xl border border-dark-700 flex flex-wrap items-center justify-center gap-6 text-xs mb-10 font-mono">
            <div class="flex items-center gap-2">
                <span class="text-gray-500">mode:</span>
                <button id="btn-mode-words" class="mode-btn text-emerald-400 font-bold hover:underline" data-mode="words">words</button>
                <button id="btn-mode-time" class="mode-btn text-gray-400 hover:text-white" data-mode="time">time</button>
                <button id="btn-mode-quote" class="mode-btn text-gray-400 hover:text-white" data-mode="quote">quote</button>
                <button id="btn-mode-code" class="mode-btn text-gray-400 hover:text-white" data-mode="code">code</button>
            </div>
            
            <div class="h-4 w-[1px] bg-dark-700 hidden sm:block"></div>
            
            <div class="flex items-center gap-2" id="length-selector">
                <span class="text-gray-500" id="length-label">words:</span>
                <button class="count-btn text-gray-400 hover:text-white" data-count="15">15</button>
                <button class="count-btn text-emerald-400 font-bold" data-count="25">25</button>
                <button class="count-btn text-gray-400 hover:text-white" data-count="50">50</button>
                <button class="count-btn text-gray-400 hover:text-white" data-count="100">100</button>
            </div>

            <div class="h-4 w-[1px] bg-dark-700 hidden sm:block" id="modifier-separator"></div>

            
            <div class="flex items-center gap-3" id="modifier-selector">
                <button id="btn-toggle-punct" class="px-2 py-1 rounded-lg border border-dark-700 text-gray-400 hover:text-white transition flex items-center gap-1.5" data-active="0">
                    <i class="fa-solid fa-at text-[10px]"></i> punctuation
                </button>
                <button id="btn-toggle-numbers" class="px-2 py-1 rounded-lg border border-dark-700 text-gray-400 hover:text-white transition flex items-center gap-1.5" data-active="0">
                    <i class="fa-solid fa-hashtag text-[10px]"></i> numbers
                </button>
            </div>

            <div class="h-4 w-[1px] bg-dark-700 hidden sm:block" id="difficulty-separator"></div>

            <div class="flex items-center gap-2" id="difficulty-selector">
                <span class="text-gray-500"><i class="fa-solid fa-layer-group text-[10px] mr-1"></i>level:</span>
                <button class="diff-btn text-gray-400 hover:text-white" data-difficulty="beginner">beginner</button>
                <button class="diff-btn text-emerald-400 font-bold" data-difficulty="intermediate">intermediate</button>
                <button class="diff-btn text-gray-400 hover:text-white" data-difficulty="advanced">advanced</button>
            </div>
        </div>

        
        <div class="zen-dim w-full max-w-4xl flex items-center justify-between mb-6 px-4">
            <div class="flex items-center gap-8 font-mono">
                <div>
                    <span class="text-[10px] text-gray-500 uppercase tracking-wider block">WPM</span>
                    <span id="live-wpm" class="text-3xl font-bold text-emerald-400">0</span>
                </div>
                <div>
                    <span class="text-[10px] text-gray-500 uppercase tracking-wider block">Accuracy</span>
                    <span id="live-accuracy" class="text-3xl font-bold text-cyan-400">100%</span>
                </div>
            </div>
            <div>
                <span class="text-[10px] text-gray-500 uppercase tracking-wider block font-mono">Time</span>
                <span id="live-timer" class="text-3xl font-bold text-gray-300 font-mono">0s</span>
            </div>
        </div>

        
        <div id="typing-area" class="w-full max-w-4xl glass-panel p-8 rounded-2xl border border-dark-700 cursor-text relative min-h-[180px] flex items-center shadow-2xl">
            
            <input type="text" id="hidden-input" class="absolute inset-0 w-full h-full opacity-0 cursor-text z-10" autofocus autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore onpaste="return false;" ondrop="return false;" oncopy="return false;">
            
            
            <div id="text-display" class="leading-relaxed select-none break-words w-full pointer-events-none z-0 text-xl md:text-2xl">
                
            </div>
        </div>

        
        <div class="zen-dim mt-8 text-xs text-gray-500 font-mono flex items-center gap-4">
            <div class="flex items-center gap-1.5">
                <kbd class="px-2 py-0.5 bg-dark-800 border border-dark-700 rounded text-gray-400">Tab</kbd>
                <span>Restart Test</span>
            </div>
            <span class="text-dark-700">•</span>
            <div class="flex items-center gap-1.5">
                <kbd class="px-2 py-0.5 bg-dark-800 border border-dark-700 rounded text-gray-400">Esc</kbd>
                <span>Clear / Reset</span>
            </div>
        </div>

        
        <div id="results-modal" class="fixed inset-0 bg-dark-950/85 backdrop-blur-md flex items-center justify-center p-4 hidden z-50">
            <div class="glass-panel p-8 rounded-2xl border border-dark-700 max-w-md w-full text-center space-y-6 shadow-2xl relative">
                <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-2xl mx-auto">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">Test Completed!</h2>
                    <p class="text-xs text-gray-400 mt-1">Great job! Here is your performance telemetry:</p>
                </div>

                <div class="grid grid-cols-2 gap-4 py-2">
                    <div class="bg-dark-800/50 p-4 rounded-xl border border-dark-700">
                        <span class="text-[10px] font-mono text-gray-500 uppercase">Speed</span>
                        <div id="modal-wpm" class="text-3xl font-bold text-emerald-400 font-mono">0</div>
                        <span class="text-[10px] text-gray-400">Words Per Min</span>
                    </div>
                    <div class="bg-dark-800/50 p-4 rounded-xl border border-dark-700">
                        <span class="text-[10px] font-mono text-gray-500 uppercase">Accuracy</span>
                        <div id="modal-acc" class="text-3xl font-bold text-cyan-400 font-mono">100%</div>
                        <span class="text-[10px] text-gray-400">Precision Rate</span>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex gap-3">
                        <button id="btn-next-test" class="flex-1 py-3 px-4 bg-emerald-400 text-dark-900 rounded-xl font-bold text-xs hover:bg-emerald-300 transition shadow-lg shadow-emerald-500/10 flex items-center justify-center gap-2">
                            Next Test <kbd class="px-1.5 py-0.5 bg-emerald-500/20 text-dark-900 rounded text-[10px]">Tab</kbd>
                        </button>
                        <a href="dashboard.php" class="px-4 py-3 bg-dark-800 border border-dark-700 text-gray-300 rounded-xl font-bold text-xs hover:bg-dark-700 transition">
                            Dashboard
                        </a>
                    </div>
                    
                    
                    <button id="btn-practice-missed" class="w-full py-2.5 px-4 bg-dark-800/90 border border-amber-500/30 text-amber-400 hover:bg-amber-500/10 rounded-xl font-semibold text-xs transition flex items-center justify-center gap-2 hidden">
                        <i class="fa-solid fa-bullseye"></i> Practice Missed Words
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="assets/js/typing-engine.js"></script>
<script>
let engine;
document.addEventListener('DOMContentLoaded', () => {
    engine = new TypingEngine();
    engine.loadText('words', 25);

    const lengthSelector = document.getElementById('length-selector');
    const lengthLabel = document.getElementById('length-label');
    const modifierSelector = document.getElementById('modifier-selector');
    const modifierSep = document.getElementById('modifier-separator');
    const difficultySelector = document.getElementById('difficulty-selector');
    const difficultySep = document.getElementById('difficulty-separator');
    const btnNextTest = document.getElementById('btn-next-test');
    const btnPracticeMissed = document.getElementById('btn-practice-missed');
    const btnTogglePunct = document.getElementById('btn-toggle-punct');
    const btnToggleNumbers = document.getElementById('btn-toggle-numbers');

    function updateControls(mode) {
        // Difficulty pills stay visible for every mode: quote/code pull curated
        // DB texts at that level, words/time scale generator vocabulary.
        if (difficultySelector) difficultySelector.classList.remove('hidden');
        if (difficultySep) difficultySep.classList.remove('hidden');
        if (mode === 'code' || mode === 'quote') {
            lengthSelector.classList.add('hidden');
            if (modifierSelector) modifierSelector.classList.add('hidden');
            if (modifierSep) modifierSep.classList.add('hidden');
        } else {
            lengthSelector.classList.remove('hidden');
            if (modifierSelector) modifierSelector.classList.remove('hidden');
            if (modifierSep) modifierSep.classList.remove('hidden');
            if (mode === 'time') {
                lengthLabel.textContent = 'seconds:';
                const times = [15, 30, 60];
                const btns = lengthSelector.querySelectorAll('.count-btn');
                btns.forEach((btn, idx) => {
                    if (idx < times.length) {
                        btn.classList.remove('hidden');
                        btn.dataset.count = times[idx];
                        btn.textContent = times[idx];
                        if (times[idx] === 30) {
                            btn.className = 'count-btn text-emerald-400 font-bold';
                        } else {
                            btn.className = 'count-btn text-gray-400 hover:text-white';
                        }
                    } else {
                        btn.classList.add('hidden');
                    }
                });
                engine.targetCount = 30;
                engine.timeLimit = 30;
            } else {
                lengthLabel.textContent = 'words:';
                const counts = [15, 25, 50, 100];
                const btns = lengthSelector.querySelectorAll('.count-btn');
                btns.forEach((btn, idx) => {
                    if (idx < counts.length) {
                        btn.classList.remove('hidden');
                        btn.dataset.count = counts[idx];
                        btn.textContent = counts[idx];
                        if (counts[idx] === 25) {
                            btn.className = 'count-btn text-emerald-400 font-bold';
                        } else {
                            btn.className = 'count-btn text-gray-400 hover:text-white';
                        }
                    } else {
                        btn.classList.add('hidden');
                    }
                });
                engine.targetCount = 25;
            }
        }
    }

    document.querySelectorAll('.mode-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.mode-btn').forEach(b => b.className = 'mode-btn text-gray-400 hover:text-white');
            e.target.className = 'mode-btn text-emerald-400 font-bold hover:underline';
            const mode = e.target.dataset.mode || 'words';
            updateControls(mode);
            engine.loadText(mode, engine.targetCount);
        });
    });

    document.querySelectorAll('.count-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.count-btn').forEach(b => b.className = 'count-btn text-gray-400 hover:text-white');
            e.target.className = 'count-btn text-emerald-400 font-bold';
            const count = parseInt(e.target.dataset.count);
            engine.loadText(engine.mode, count);
        });
    });

    document.querySelectorAll('.diff-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.diff-btn').forEach(b => b.className = 'diff-btn text-gray-400 hover:text-white');
            e.target.className = 'diff-btn text-emerald-400 font-bold';
            const diff = e.target.dataset.difficulty || 'intermediate';
            engine.loadText(engine.mode, engine.targetCount, diff);
        });
    });

    updateControls('words');

    if (btnTogglePunct) {
        btnTogglePunct.addEventListener('click', () => {
            engine.usePunctuation = !engine.usePunctuation;
            if (engine.usePunctuation) {
                btnTogglePunct.className = 'px-2 py-1 rounded-lg border border-emerald-500/40 bg-emerald-500/10 text-emerald-400 transition flex items-center gap-1.5 font-bold';
            } else {
                btnTogglePunct.className = 'px-2 py-1 rounded-lg border border-dark-700 text-gray-400 hover:text-white transition flex items-center gap-1.5';
            }
            engine.loadText(engine.mode, engine.targetCount);
        });
    }

    if (btnToggleNumbers) {
        btnToggleNumbers.addEventListener('click', () => {
            engine.useNumbers = !engine.useNumbers;
            if (engine.useNumbers) {
                btnToggleNumbers.className = 'px-2 py-1 rounded-lg border border-emerald-500/40 bg-emerald-500/10 text-emerald-400 transition flex items-center gap-1.5 font-bold';
            } else {
                btnToggleNumbers.className = 'px-2 py-1 rounded-lg border border-dark-700 text-gray-400 hover:text-white transition flex items-center gap-1.5';
            }
            engine.loadText(engine.mode, engine.targetCount);
        });
    }

    if (btnNextTest) {
        btnNextTest.addEventListener('click', () => {
            engine.loadText(engine.mode, engine.targetCount);
        });
    }

    if (btnPracticeMissed) {
        btnPracticeMissed.addEventListener('click', () => {
            engine.practiceMissed();
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>