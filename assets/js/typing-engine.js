class TypingEngine {
    constructor(options = {}) {
        this.displayEl = document.getElementById(options.displayId || 'text-display');
        this.inputEl   = document.getElementById(options.inputId || 'hidden-input');
        this.wpmEl     = document.getElementById(options.wpmId || 'live-wpm');
        this.accEl     = document.getElementById(options.accId || 'live-accuracy');
        this.timerEl   = document.getElementById(options.timerId || 'live-timer');
        this.cpmEl     = document.getElementById(options.cpmId || 'live-cpm');

        this.mode = 'words';
        this.targetCount = 25;
        this.timeLimit = 30;
        this.usePunctuation = false;
        this.useNumbers = false;
        this.difficulty = 'intermediate';
        this.textId = null;

        this.rawText = '';
        this.characters = [];
        this.currentIndex = 0;
        this.totalKeystrokes = 0;
        this.errorCount = 0;
        this.startTime = null;
        this.missedWordsSet = new Set();

        this.timer = null;
        this.timeLeft = this.timeLimit;
        this.elapsedSeconds = 0;
        this.isRunning = false;
        this.isFinished = false;

        const html = document.documentElement;
        this.caretStyle = html.dataset.caret || 'line';
        this.smoothCaret = html.dataset.smooth === '1';
        this.soundEnabled = html.dataset.sound === '1';

        this.initAudio();
        this.initListeners();
    }

    initAudio() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (AudioCtx) {
                this.audioCtx = new AudioCtx();
            }
        } catch (e) {
            this.audioCtx = null;
        }
    }

    playKeySound(isError = false) {
        if (!this.soundEnabled || !this.audioCtx) return;
        if (this.audioCtx.state === 'suspended') {
            this.audioCtx.resume();
        }

        try {
            const osc = this.audioCtx.createOscillator();
            const gain = this.audioCtx.createGain();
            osc.connect(gain);
            gain.connect(this.audioCtx.destination);

            if (isError) {
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(140, this.audioCtx.currentTime);
                gain.gain.setValueAtTime(0.08, this.audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, this.audioCtx.currentTime + 0.08);
                osc.start();
                osc.stop(this.audioCtx.currentTime + 0.08);
            } else {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(580, this.audioCtx.currentTime);
                gain.gain.setValueAtTime(0.04, this.audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, this.audioCtx.currentTime + 0.04);
                osc.start();
                osc.stop(this.audioCtx.currentTime + 0.04);
            }
        } catch (err) {

        }
    }

    async loadText(mode = 'words', count = 25, difficulty = null) {
        this.mode = mode;
        this.targetCount = count;
        if (difficulty) this.difficulty = difficulty;
        if (mode === 'time') {
            this.timeLimit = count;
            this.timeLeft = count;
        }
        this.resetState();

        try {
            const params = 'mode=' + encodeURIComponent(mode) + '&count=' + encodeURIComponent(count)
                + '&difficulty=' + encodeURIComponent(this.difficulty)
                + (this.usePunctuation ? '&punctuation=1' : '')
                + (this.useNumbers ? '&numbers=1' : '');
            // REST v1 preferred, legacy shim fallback for easy deployment stages.
            const urls = ['api/v1/index.php?r=texts&' + params, 'api/get-text.php?' + params];
            let loaded = '';
            this.textId = null;
            for (const url of urls) {
                try {
                    const res = await fetch(url);
                    const data = await res.json();
                    loaded = (data && data.data && data.data.text) ? data.data.text : data.text;
                    const tid = (data && data.data && data.data.text_id) ? data.data.text_id : data.text_id;
                    if (data.success && loaded) {
                        this.textId = tid ? parseInt(tid) || null : null;
                        break;
                    }
                    loaded = '';
                } catch (e) { /* try next */ }
            }
            if (loaded) {
                this.rawText = loaded;
                this.renderText();
            } else {
                this.rawText = 'the quick brown fox jumps over the lazy dog';
                this.renderText();
            }
        } catch (err) {
            console.error('Failed to load text snippet:', err);
            this.rawText = 'the quick brown fox jumps over the lazy dog';
            this.renderText();
        }
    }

    practiceMissed() {
        if (!this.missedWordsSet || this.missedWordsSet.size === 0) return;
        const words = Array.from(this.missedWordsSet);

        let repeated = [];
        while (repeated.length < Math.max(15, words.length * 2)) {
            repeated = repeated.concat(words);
        }
        repeated = repeated.slice(0, 25);
        this.resetState();
        this.rawText = repeated.join(' ');
        this.renderText();
    }

    getCaretClasses() {
        const classes = ['caret-active'];
        if (this.caretStyle === 'block') {
            classes.push('bg-emerald-400/40', 'rounded-sm');
        } else if (this.caretStyle === 'underline') {
            classes.push('border-b-2', 'border-emerald-400');
        } else {
            classes.push('border-l-2', 'border-emerald-400');
        }
        if (this.smoothCaret) {
            classes.push('transition-all', 'duration-75');
        } else {
            classes.push('animate-pulse');
        }
        return classes;
    }

    renderText() {
        this.displayEl.innerHTML = '';
        this.characters = [];

        const caretClasses = this.getCaretClasses();

        this.rawText.split('').forEach((char, idx) => {
            const span = document.createElement('span');
            span.innerText = char;
            span.className = 'char text-gray-500 font-mono select-none';
            if (idx === 0) {
                span.classList.add(...caretClasses);
            }
            this.displayEl.appendChild(span);
            this.characters.push(span);
        });
    }

    initListeners() {
        const area = document.getElementById('typing-area');
        if (area) {
            area.addEventListener('click', () => {
                if (this.inputEl) this.inputEl.focus();
            });
        }

        if (this.inputEl) {
            this.inputEl.addEventListener('input', (e) => this.handleInput(e));

            this.inputEl.addEventListener('paste', (e) => e.preventDefault());
            this.inputEl.addEventListener('drop', (e) => e.preventDefault());
            this.inputEl.addEventListener('copy', (e) => e.preventDefault());
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                e.preventDefault();
                this.loadText(this.mode, this.targetCount);
                return;
            }

            if (e.key === 'Escape') {
                const modal = document.getElementById('results-modal');
                if (modal && !modal.classList.contains('hidden')) {
                    modal.classList.add('hidden');
                    this.loadText(this.mode, this.targetCount);
                } else {
                    this.resetState();
                    this.renderText();
                }
                return;
            }

            const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
            const modal = document.getElementById('results-modal');
            const isModalOpen = modal && !modal.classList.contains('hidden');

            if (!isModalOpen && activeTag !== 'input' && activeTag !== 'textarea' && activeTag !== 'select' && activeTag !== 'button') {
                if (e.key.length === 1 || e.key === 'Backspace') {
                    if (this.inputEl && document.activeElement !== this.inputEl) {
                        this.inputEl.focus();
                    }
                }
            }
        });
    }

    handleInput(e) {
        if (this.isFinished) return;

        const inputVal = this.inputEl.value;
        const currentLength = inputVal.length;

        if (!this.isRunning && currentLength > 0) {
            this.startTimer();
        }

        if (currentLength > this.currentIndex) {
            this.totalKeystrokes += (currentLength - this.currentIndex);
            const lastTypedIdx = currentLength - 1;
            const isWrong = inputVal[lastTypedIdx] !== this.rawText[lastTypedIdx];
            this.playKeySound(isWrong);

            if (isWrong) {
                const wordsList = this.rawText.split(' ');
                let charCount = 0;
                for (const w of wordsList) {
                    if (lastTypedIdx >= charCount && lastTypedIdx <= charCount + w.length) {
                        this.missedWordsSet.add(w.replace(/[^a-zA-Z0-9]/g, ''));
                        break;
                    }
                    charCount += w.length + 1;
                }
            }
        }

        this.currentIndex = currentLength;

        let errors = 0;
        const caretClasses = this.getCaretClasses();

        this.characters.forEach((span, idx) => {
            span.className = 'char font-mono select-none';

            if (idx < currentLength) {
                const typedChar = inputVal[idx];
                const targetChar = this.rawText[idx];

                if (typedChar === targetChar) {
                    span.classList.add('text-emerald-400');
                } else {
                    span.classList.add('text-red-400', 'bg-red-500/20', 'rounded');
                    errors++;
                }
            } else if (idx === currentLength) {
                span.classList.add('text-gray-500', ...caretClasses);
            } else {
                span.classList.add('text-gray-500');
            }
        });

        this.errorCount = errors;
        this.updateMetrics();

        if (this.mode === 'time') {

            if (currentLength >= this.rawText.length - 30) {
                this.extendTextForTimeMode();
            }
        } else if (currentLength >= this.rawText.length) {
            this.finishTest();
        }
    }

    startTimer() {
        this.isRunning = true;
        this.startTime = Date.now();
        document.body.classList.add('zen-active');

        this.timer = setInterval(() => {
            const now = Date.now();
            this.elapsedSeconds = Math.max(0, Math.floor((now - this.startTime) / 1000));

            if (this.mode === 'time') {
                this.timeLeft = Math.max(0, this.timeLimit - this.elapsedSeconds);
                if (this.timerEl) this.timerEl.innerText = this.timeLeft + 's';
                if (this.timeLeft <= 0) {
                    this.finishTest();
                }
            } else {
                if (this.timerEl) this.timerEl.innerText = this.elapsedSeconds + 's';
            }

            this.updateMetrics();
        }, 200);
    }

    extendTextForTimeMode() {

        const extra = this.rawText.split(' ').slice(0, 100).join(' ');
        if (extra) {
            this.rawText += ' ' + extra;
            this.renderTextPreservingProgress();
        }
    }

    renderTextPreservingProgress() {
        const inputVal = this.inputEl ? this.inputEl.value : '';
        this.renderText();

        const currentLength = inputVal.length;
        const caretClasses = this.getCaretClasses();
        this.characters.forEach((span, idx) => {
            span.className = 'char font-mono select-none';
            if (idx < currentLength) {
                if (inputVal[idx] === this.rawText[idx]) {
                    span.classList.add('text-emerald-400');
                } else {
                    span.classList.add('text-red-400', 'bg-red-500/20', 'rounded');
                }
            } else if (idx === currentLength) {
                span.classList.add('text-gray-500', ...caretClasses);
            } else {
                span.classList.add('text-gray-500');
            }
        });
    }

    updateMetrics() {

        let seconds;
        if (this.isRunning && this.startTime) {
            seconds = Math.max(1, (Date.now() - this.startTime) / 1000);
        } else {
            seconds = Math.max(1, this.elapsedSeconds || 1);
        }
        const minutes = seconds / 60.0;
        const correctChars = Math.max(0, this.currentIndex - this.errorCount);

        let netWpm = Math.round(((this.currentIndex / 5) - this.errorCount) / minutes);
        let rawWpm = Math.round((this.currentIndex / 5) / minutes);

        netWpm = Math.max(0, netWpm);
        rawWpm = Math.max(0, rawWpm);

        const accuracy = this.currentIndex > 0
            ? Math.max(0, Math.min(100, Math.round((correctChars / this.currentIndex) * 100)))
            : 100;

        if (this.wpmEl) this.wpmEl.innerText = netWpm;
        if (this.accEl) this.accEl.innerText = accuracy + '%';
        if (this.cpmEl) this.cpmEl.innerText = Math.round(correctChars / minutes);

        return { netWpm, rawWpm, accuracy, correctChars };
    }

    async finishTest() {
        if (this.isFinished) return;
        clearInterval(this.timer);
        this.isRunning = false;
        this.isFinished = true;
        document.body.classList.remove('zen-active');
        if (this.inputEl) this.inputEl.blur();

        const metrics = this.updateMetrics();
        const duration = Math.max(1, this.elapsedSeconds);
        const modeValue = this.mode === 'time' ? this.timeLimit : this.targetCount;

        const payload = {
            mode: this.mode,
            mode_value: modeValue,
            has_punctuation: this.usePunctuation ? 1 : 0,
            has_numbers: this.useNumbers ? 1 : 0,
            difficulty: this.difficulty,
            text_id: this.textId,
            wpm: metrics.netWpm,
            raw_wpm: metrics.rawWpm,
            accuracy: metrics.accuracy,
            duration_seconds: duration,
            total_keystrokes: Math.max(this.currentIndex, this.totalKeystrokes),
            error_count: this.errorCount,
            csrf_token: window.CSRF_TOKEN || ''
        };

        try {
            // REST v1 preferred, legacy shim fallback.
            const urls = ['api/v1/index.php?r=sessions', 'api/save-result.php'];
            let result = null;
            for (const url of urls) {
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    result = await res.json();
                    if (result && result.success) break;
                } catch (e) { result = null; }
            }
            if (result && result.success) {
                const modal = document.getElementById('results-modal');
                if (modal) modal.classList.remove('hidden');
                const modalWpm = document.getElementById('modal-wpm');
                if (modalWpm) modalWpm.innerText = metrics.netWpm;
                const modalAcc = document.getElementById('modal-acc');
                if (modalAcc) modalAcc.innerText = metrics.accuracy + '%';

                // Achievement unlock celebration (v1 nests under data, legacy is flat).
                try {
                    const fresh = (result.data && result.data.new_achievements) || result.new_achievements || [];
                    if (Array.isArray(fresh) && fresh.length > 0 && window.Swal) {
                        const first = fresh[0];
                        const extra = fresh.length > 1 ? '<div class="text-xs mt-2">Plus ' + (fresh.length - 1) + ' more — see your dashboard shelf.</div>' : '';
                        window.Swal.fire({
                            title: 'Achievement unlocked!',
                            html: '<div style="display:flex;flex-direction:column;align-items:center;gap:.5rem">'
                                + '<div style="width:64px;height:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;color:#34d399;background:rgba(52,211,153,.12);border:1px solid rgba(52,211,153,.4)"><i class="fa-solid ' + String(first.icon || 'fa-medal').replace(/[^a-z0-9- ]/gi, '') + '"></i></div>'
                                + '<div style="font-weight:800;font-size:1.1rem">' + String(first.title || 'New badge').replace(/</g, '&lt;') + '</div>'
                                + '<div class="text-sm">' + String(first.description || '').replace(/</g, '&lt;') + '</div>'
                                + '<div class="text-xs font-mono">+' + parseInt(first.points || 0, 10) + ' points</div>' + extra + '</div>',
                            confirmButtonText: 'Keep typing',
                            confirmButtonColor: '#10b981',
                            showCloseButton: true
                        });
                    }
                } catch (e) { /* celebration never blocks results */ }

                const btnPractice = document.getElementById('btn-practice-missed');
                if (btnPractice) {
                    if (this.missedWordsSet && this.missedWordsSet.size > 0) {
                        btnPractice.classList.remove('hidden');
                    } else {
                        btnPractice.classList.add('hidden');
                    }
                }
            } else {
                console.warn('API Response Warning:', result.error);
            }
        } catch (err) {
            console.error('Failed to submit test results:', err);
        }
    }

    resetState() {
        clearInterval(this.timer);
        this.isRunning = false;
        this.isFinished = false;
        this.currentIndex = 0;
        this.totalKeystrokes = 0;
        this.errorCount = 0;
        this.elapsedSeconds = 0;
        this.startTime = null;
        this.timeLeft = this.timeLimit;
        this.missedWordsSet = new Set();
        document.body.classList.remove('zen-active');

        if (this.inputEl) this.inputEl.value = '';
        if (this.wpmEl) this.wpmEl.innerText = '0';
        if (this.accEl) this.accEl.innerText = '100%';
        if (this.timerEl) this.timerEl.innerText = this.mode === 'time' ? (this.timeLimit + 's') : '0s';

        const modal = document.getElementById('results-modal');
        if (modal) modal.classList.add('hidden');
        if (this.inputEl) this.inputEl.focus();
    }
}
