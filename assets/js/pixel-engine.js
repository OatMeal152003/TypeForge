/* TypeForge pixel engine - procedural 8-bit sprites, fixed-timestep loop,
 * particle pool, chiptune SFX. Zero image assets: every sprite is a string
 * grid pre-rendered once to an offscreen canvas, blitted with smoothing off.
 * Exposed as window.Pixel for the canvas game modules. */
(function () {
  'use strict';

  var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function soundOn() {
    var h = document.documentElement;
    return (h.getAttribute('data-sound') || '1') === '1';
  }

  var PAL = {
    '.': null,
    K: '#06090e', W: '#f1f5f9', G: '#34d399', g: '#059669', T: '#2dd4bf',
    A: '#fbbf24', R: '#f87171', B: '#60a5fa', P: '#c084fc', C: '#22d3ee'
  };

  var SPRITES = {
    cannon: [
      '.....G.....',
      '.....G.....',
      '.....G.....',
      '..GGGGG....'.slice(0, 11),
      '.GGGGGGG...'.slice(0, 11),
      'GGGGGGGGG..'.slice(0, 11),
      'GGGGGGGGGGG'
    ],
    alien1: [
      '..K...K..',
      '...KKK...',
      '..KKKKK..',
      '.KGKGKGK.',
      'KKKKKKKKK',
      'K.KKKKK.K',
      'K.K...K.K',
      '...K.K...'
    ],
    alien2: [
      '....KK....',
      '..KKKKKK..',
      '.KKGKKGKK.',
      'KKKKKKKKKK',
      'GKKKGGKKKG',
      'G.KKKKKK.G',
      '..K.KK.K..',
      '.KK....KK.'
    ],
    alien3: [
      '...KKKK...',
      '.KKKKKKKK.',
      'KKPKKPPKKK',
      'KKKKKKKKKK',
      '.KKKGGKKK.',
      '..K.KK.K..',
      '.K..KK..K.',
      'K....K....'.slice(0, 10)
    ],
    boom1: [
      'K..K...K..K',
      '.K..K.K..K.',
      '..K.KKK.K..',
      '...KKKKK...',
      'KKKKKKKKKKK',
      '...KKKKK...',
      '..K.KKK.K..',
      '.K..K.K..K.',
      'K..K...K..K'
    ],
    boom2: [
      '...........',
      '.....K.....',
      '..K..K..K..',
      '.....K.....',
      '.K..KKK..K.',
      '.....K.....',
      '..K..K..K..',
      '.....K.....',
      '...........'
    ]
  };

  function buildAtlas(px) {
    var atlas = {};
    Object.keys(SPRITES).forEach(function (name) {
      var rows = SPRITES[name];
      var h = rows.length;
      var w = Math.max.apply(null, rows.map(function (r) { return r.length; }));
      var cv = document.createElement('canvas');
      cv.width = w;
      cv.height = h;
      var ctx = cv.getContext('2d');
      rows.forEach(function (row, y) {
        for (var x = 0; x < row.length; x++) {
          var c = PAL[row[x]];
          if (!c) continue;
          ctx.fillStyle = c;
          ctx.fillRect(x, y, 1, 1);
        }
      });
      atlas[name] = { cv: cv, w: w, h: h };
    });
    return atlas;
  }

  function fitCanvas(canvas, W, H) {
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.round(W * dpr);
    canvas.height = Math.round(H * dpr);
    var ctx = canvas.getContext('2d');
    ctx.imageSmoothingEnabled = false;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    return ctx;
  }

  /* Pixel particle pool: fixed size, zero per-frame allocation. */
  function Particles(n) {
    this.pool = [];
    for (var i = 0; i < (n || 120); i++) this.pool.push({ on: false, x: 0, y: 0, vx: 0, vy: 0, life: 0, max: 1, c: '#fff', s: 2 });
    this.idx = 0;
  }
  Particles.prototype.burst = function (x, y, color, count, spd) {
    if (REDUCED) return;
    for (var i = 0; i < count; i++) {
      var p = this.pool[this.idx];
      this.idx = (this.idx + 1) % this.pool.length;
      var a = Math.random() * Math.PI * 2;
      var v = (spd || 90) * (0.4 + Math.random() * 0.8);
      p.on = true; p.x = x; p.y = y;
      p.vx = Math.cos(a) * v; p.vy = Math.sin(a) * v;
      p.max = p.life = 0.45 + Math.random() * 0.35;
      p.c = color; p.s = 2 + Math.floor(Math.random() * 2);
    }
  };
  Particles.prototype.step = function (dt) {
    for (var i = 0; i < this.pool.length; i++) {
      var p = this.pool[i];
      if (!p.on) continue;
      p.life -= dt;
      if (p.life <= 0) { p.on = false; continue; }
      p.x += p.vx * dt;
      p.y += p.vy * dt;
      p.vy += 160 * dt;
    }
  };
  Particles.prototype.draw = function (ctx) {
    for (var i = 0; i < this.pool.length; i++) {
      var p = this.pool[i];
      if (!p.on) continue;
      ctx.globalAlpha = Math.max(0, p.life / p.max);
      ctx.fillStyle = p.c;
      ctx.fillRect(Math.round(p.x), Math.round(p.y), p.s, p.s);
    }
    ctx.globalAlpha = 1;
  };

  /* Chiptune SFX: square-wave blips, gated by the site sound setting. */
  var Sfx = {
    ctx: null,
    ensure: function () {
      if (this.ctx) return true;
      if (!soundOn()) return false;
      try {
        var AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) return false;
        this.ctx = new AC();
      } catch (e) { return false; }
      return true;
    },
    tone: function (f0, f1, ms, vol) {
      if (!soundOn()) return;
      if (!this.ensure()) return;
      try {
        if (this.ctx.state === 'suspended') this.ctx.resume();
        var o = this.ctx.createOscillator();
        var g = this.ctx.createGain();
        o.type = 'square';
        var t = this.ctx.currentTime;
        o.frequency.setValueAtTime(f0, t);
        o.frequency.exponentialRampToValueAtTime(Math.max(30, f1), t + ms / 1000);
        g.gain.setValueAtTime(vol || 0.04, t);
        g.gain.exponentialRampToValueAtTime(0.0001, t + ms / 1000);
        o.connect(g); g.connect(this.ctx.destination);
        o.start(t); o.stop(t + ms / 1000 + 0.02);
      } catch (e) { /* silent */ }
    },
    shoot: function () { this.tone(880, 220, 90); },
    pop: function () { this.tone(520, 990, 80); },
    hurt: function () { this.tone(220, 60, 220, 0.06); },
    wave: function () { this.tone(330, 660, 160); this.tone(495, 990, 160); },
    win: function () {
      var self = this;
      [523, 659, 784, 1046].forEach(function (f, i) {
        setTimeout(function () { self.tone(f, f, 140); }, i * 130);
      });
    }
  };

  /* Shared fetch / submit / celebrate / input rig (honest-metrics core). */
  function esc(s) { return String(s == null ? '' : s).replace(/</g, '&lt;'); }

  async function fetchWords(count) {
    var params = 'mode=words&count=' + encodeURIComponent(count || 60) + '&difficulty=intermediate';
    var urls = ['api/v1/index.php?r=texts&' + params, 'api/get-text.php?' + params];
    for (var i = 0; i < urls.length; i++) {
      try {
        var res = await fetch(urls[i]);
        var data = await res.json();
        var t = (data && data.data && data.data.text) ? data.data.text : data.text;
        var tid = (data && data.data && data.data.text_id) ? data.data.text_id : data.text_id;
        if (data.success && t) {
          return { words: String(t).split(/\s+/).filter(Boolean), textId: tid ? parseInt(tid, 10) || null : null };
        }
      } catch (e) { /* try next */ }
    }
    return { words: 'pixel forge cannon laser alien wave score combo blast neon grid storm rapid light speed'.split(' '), textId: null };
  }

  async function submitRun(payload) {
    payload.csrf_token = window.CSRF_TOKEN || '';
    var urls = ['api/v1/index.php?r=sessions', 'api/save-result.php'];
    for (var i = 0; i < urls.length; i++) {
      try {
        var res = await fetch(urls[i], {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        var out = await res.json();
        if (out && out.success) return out;
      } catch (e) { /* try next */ }
    }
    return null;
  }

  function celebrate(result, titleHtml, subHtml) {
    try {
      var fresh = (result && result.data && result.data.new_achievements) || (result && result.new_achievements) || [];
      var m = (result && result.data && result.data.metrics) || (result && result.metrics) || {};
      var achHtml = '';
      if (Array.isArray(fresh) && fresh.length > 0 && window.Swal) {
        var first = fresh[0];
        achHtml = '<div style="margin-top:.75rem;padding:.6rem;border-radius:.75rem;background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.35)">'
          + '<div style="font-weight:800">Achievement unlocked: ' + esc(first.title) + ' (+' + parseInt(first.points || 0, 10) + ')</div>'
          + (fresh.length > 1 ? '<div class="text-xs">Plus ' + (fresh.length - 1) + ' more on your shelf.</div>' : '') + '</div>';
      }
      if (window.Swal) {
        window.Swal.fire({
          title: 'Run complete!',
          html: titleHtml + '<div class="text-sm">WPM ' + esc(m.wpm) + ' · ' + esc(m.accuracy) + '% acc</div>' + (subHtml || '') + achHtml,
          confirmButtonText: 'Play again',
          confirmButtonColor: '#10b981',
          showCancelButton: true,
          cancelButtonText: 'Arcade hub'
        }).then(function (r) {
          if (r.isConfirmed) { window.location.reload(); }
          else if (r.dismiss === window.Swal.DismissReason.cancel) { window.location.href = 'games.php'; }
        });
      }
    } catch (e) { /* results already banked */ }
  }

  function rigInput(input, area, onChar, onBack) {
    input.addEventListener('paste', function (e) { e.preventDefault(); });
    input.addEventListener('drop', function (e) { e.preventDefault(); });
    input.addEventListener('copy', function (e) { e.preventDefault(); });
    area.addEventListener('click', function () { input.focus(); });
    var lastLen = 0;
    input.addEventListener('input', function () {
      var v = input.value;
      if (v.length > lastLen) {
        var added = v.slice(lastLen);
        for (var i = 0; i < added.length; i++) onChar(added[i]);
      } else if (v.length < lastLen) {
        onBack();
      }
      lastLen = v.length;
      if (v.length > 64) { input.value = ''; lastLen = 0; }
    });
    input.focus();
  }

  function elapsedSec(startT) { return Math.max(1, Math.round((Date.now() - startT) / 1000)); }

  /* Fixed-timestep loop runner: 60Hz updates, rAF render, auto-pause offscreen. */
  function runLoop(step, draw, isOver) {
    var acc = 0, last = performance.now(), raf = 0, running = true;
    var STEP = 1000 / 60;
    function frame(now) {
      if (!running) return;
      var dt = Math.min(100, now - last);
      last = now;
      acc += dt;
      while (acc >= STEP) { if (!isOver()) step(STEP / 1000); acc -= STEP; }
      draw();
      if (!isOver()) { raf = requestAnimationFrame(frame); }
      else { running = false; }
    }
    document.addEventListener('visibilitychange', function () { last = performance.now(); });
    raf = requestAnimationFrame(frame);
    return function stop() { running = false; cancelAnimationFrame(raf); };
  }

  window.Pixel = {
    REDUCED: REDUCED,
    PAL: PAL,
    buildAtlas: buildAtlas,
    fitCanvas: fitCanvas,
    Particles: Particles,
    Sfx: Sfx,
    fetchWords: fetchWords,
    submitRun: submitRun,
    celebrate: celebrate,
    rigInput: rigInput,
    elapsedSec: elapsedSec,
    runLoop: runLoop,
    esc: esc
  };
})();
