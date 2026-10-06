/* Block Drop - Tetris-typing hybrid. Type the piece word to smash it. Canvas. */
(function () {
  'use strict';
  var P = window.Pixel;
  var COLS = 10, ROWS = 16, CELL = 26;
  var WELL_W = COLS * CELL, WELL_H = ROWS * CELL;
  var W = WELL_W + 170, H = WELL_H + 20;

  var SHAPES = [
    { cells: [[0, 0], [1, 0], [2, 0], [3, 0]], c: '#22d3ee' },
    { cells: [[0, 0], [1, 0], [0, 1], [1, 1]], c: '#fbbf24' },
    { cells: [[0, 0], [1, 0], [2, 0], [1, 1]], c: '#c084fc' },
    { cells: [[1, 0], [2, 0], [0, 1], [1, 1]], c: '#34d399' },
    { cells: [[0, 0], [1, 0], [1, 1], [2, 1]], c: '#f87171' },
    { cells: [[0, 0], [0, 1], [1, 1], [2, 1]], c: '#60a5fa' },
    { cells: [[2, 0], [0, 1], [1, 1], [2, 1]], c: '#fb923c' }
  ];

  document.addEventListener('DOMContentLoaded', function () {
    var canvas = document.getElementById('game-canvas');
    if (!canvas || (window.GAME || '') !== 'blocks') return;
    var startBtn = document.getElementById('game-start-btn');
    startBtn.addEventListener('click', function () {
      document.getElementById('game-start').classList.add('hidden');
      play(canvas);
    });
  });

  async function play(canvas) {
    var ctx = P.fitCanvas(canvas, W, H);
    var parts = new P.Particles(200);
    var pack = await P.fetchWords(100);
    var pool = pack.words.map(function (w) { return w.toLowerCase().replace(/[^a-z]/g, '').slice(0, 6); }).filter(function (w) { return w.length >= 3; });
    if (!pool.length) pool = ['pixel', 'forge', 'combo', 'smash', 'stack', 'block'];

    var st = {
      over: false, startT: Date.now(), keys: 0, errs: 0, textId: pack.textId,
      grid: [], active: null, queue: [], score: 0, lines: 0, smashed: 0,
      level: 1, dropAcc: 0, shake: 0, flash: 0
    };
    for (var r = 0; r < ROWS; r++) { st.grid.push(new Array(COLS).fill(null)); }

    document.getElementById('game-hud').innerHTML =
      '<div><span class="hud-label">Score</span><span id="hz-score" class="hud-big text-emerald-400">0</span></div>'
      + '<div><span class="hud-label">Level</span><span id="hz-level" class="hud-big text-amber-400">1</span></div>'
      + '<div><span class="hud-label">Lines</span><span id="hz-lines" class="hud-big">0</span></div>'
      + '<div><span class="hud-label">Smashed</span><span id="hz-smash" class="hud-big text-cyan-400">0</span></div>';
    function syncHud() {
      document.getElementById('hz-score').textContent = st.score;
      document.getElementById('hz-level').textContent = st.level;
      document.getElementById('hz-lines').textContent = st.lines;
      document.getElementById('hz-smash').textContent = st.smashed;
    }

    var wi = 0;
    function nextWord() {
      var w = pool[wi % pool.length];
      wi++;
      return w;
    }
    function spawn() {
      var shape = SHAPES[Math.floor(Math.random() * SHAPES.length)];
      // Center horizontally by shape width.
      var maxX = 0;
      shape.cells.forEach(function (c) { maxX = Math.max(maxX, c[0]); });
      var ox = Math.floor((COLS - maxX - 1) / 2);
      st.active = {
        cells: shape.cells.map(function (c) { return [c[0] + ox, c[1] - 2]; }),
        c: shape.c, word: nextWord(), buf: ''
      };
      // Top-out check.
      for (var i = 0; i < st.active.cells.length; i++) {
        var cc = st.active.cells[i];
        if (cc[1] >= 0 && st.grid[cc[1]][cc[0]]) { finish(); return; }
      }
    }
    function fallSpeed() { return Math.min(14, 2.2 + st.level * 0.9); } // rows/sec

    function lockPiece() {
      var a = st.active;
      var over = false;
      a.cells.forEach(function (cc) {
        if (cc[1] < 0) { over = true; return; }
        st.grid[cc[1]][cc[0]] = a.c;
      });
      if (over) { finish(); return; }
      clearLines();
      spawn();
    }
    function clearLines() {
      var cleared = 0;
      for (var y = ROWS - 1; y >= 0; y--) {
        var full = true;
        for (var x = 0; x < COLS; x++) if (!st.grid[y][x]) { full = false; break; }
        if (full) {
          cleared++;
          for (var x2 = 0; x2 < COLS; x2++) {
            parts.burst(10 + x2 * CELL + CELL / 2, 10 + y * CELL + CELL / 2, '#fbbf24', 3, 70);
          }
          st.grid.splice(y, 1);
          st.grid.unshift(new Array(COLS).fill(null));
          y++;
        }
      }
      if (cleared) {
        st.lines += cleared;
        st.score += cleared * 100;
        P.Sfx.wave();
        syncHud();
      }
    }

    var input = document.getElementById('game-input');
    P.rigInput(input, document.getElementById('game-arena'), function (ch) {
      if (st.over || !st.active) return;
      ch = ch.toLowerCase();
      st.keys++;
      var a = st.active;
      if (ch === a.word[a.buf.length]) {
        a.buf += ch;
        if (a.buf === a.word) {
          // SMASH: burst every cell, award, level up.
          a.cells.forEach(function (cc) {
            parts.burst(10 + cc[0] * CELL + CELL / 2, 10 + cc[1] * CELL + CELL / 2, a.c, 5, 110);
          });
          st.smashed++;
          st.score += a.cells.length * 10;
          if (st.smashed % 8 === 0) { st.level++; P.Sfx.win(); }
          else { P.Sfx.pop(); }
          input.value = '';
          syncHud();
          spawn();
        }
      } else {
        st.errs++;
        st.shake = P.REDUCED ? 0 : 6;
        P.Sfx.hurt();
      }
    }, function () {
      if (st.active && st.active.buf.length) st.active.buf = st.active.buf.slice(0, -1);
      input.value = st.active ? st.active.buf : '';
    });

    function step(dt) {
      if (!st.active) return;
      st.dropAcc += dt * fallSpeed();
      while (st.dropAcc >= 1) {
        st.dropAcc -= 1;
        // Can it descend?
        var can = true;
        for (var i = 0; i < st.active.cells.length; i++) {
          var cc = st.active.cells[i];
          var ny = cc[1] + 1;
          if (ny >= ROWS || (ny >= 0 && st.grid[ny][cc[0]])) { can = false; break; }
        }
        if (can) {
          for (var j = 0; j < st.active.cells.length; j++) st.active.cells[j][1]++;
        } else {
          lockPiece();
          break;
        }
      }
      if (st.shake > 0) st.shake = Math.max(0, st.shake - dt * 30);
      if (st.flash > 0) st.flash -= dt;
      parts.step(dt);
    }

    function cellRect(x, y, color, ghost) {
      var px = 10 + x * CELL, py = 10 + y * CELL;
      ctx.fillStyle = ghost ? 'rgba(255,255,255,0.06)' : color;
      ctx.fillRect(px + 1, py + 1, CELL - 2, CELL - 2);
      if (!ghost) {
        ctx.fillStyle = 'rgba(255,255,255,0.28)';
        ctx.fillRect(px + 1, py + 1, CELL - 2, 3);
      }
    }

    function draw() {
      ctx.save();
      if (st.shake > 0) ctx.translate((Math.random() - 0.5) * st.shake, (Math.random() - 0.5) * st.shake);
      ctx.fillStyle = '#06090e';
      ctx.fillRect(-10, -10, W + 20, H + 20);
      // Well frame.
      ctx.strokeStyle = '#30363d';
      ctx.strokeRect(9.5, 9.5, WELL_W + 1, WELL_H + 1);
      // Locked cells.
      for (var y = 0; y < ROWS; y++) {
        for (var x = 0; x < COLS; x++) {
          if (st.grid[y][x]) cellRect(x, y, st.grid[y][x], false);
          else if ((x + y) % 2 === 0) { ctx.fillStyle = 'rgba(255,255,255,0.02)'; ctx.fillRect(10 + x * CELL + 1, 10 + y * CELL + 1, CELL - 2, CELL - 2); }
        }
      }
      // Active piece.
      if (st.active) {
        var a = st.active;
        for (var i = 0; i < a.cells.length; i++) {
          var cc = a.cells[i];
          if (cc[1] >= 0) cellRect(cc[0], cc[1], a.c, false);
        }
        // Big target word in the strip beneath the well.
        ctx.textAlign = 'center';
        ctx.font = 'bold 26px "JetBrains Mono", monospace';
        ctx.fillStyle = '#475569';
        ctx.fillText(a.word, 10 + WELL_W / 2, H - 2);
        if (a.buf.length) {
          var tw = ctx.measureText(a.word).width;
          ctx.textAlign = 'left';
          ctx.fillStyle = '#34d399';
          ctx.fillText(a.buf, 10 + WELL_W / 2 - tw / 2, H - 2);
          ctx.textAlign = 'center';
        }
      }
      // Side panel.
      var sx = 10 + WELL_W + 16;
      ctx.textAlign = 'left';
      ctx.font = '11px "JetBrains Mono", monospace';
      ctx.fillStyle = '#64748b';
      ctx.fillText('NEXT', sx, 40);
      ctx.font = 'bold 15px "JetBrains Mono", monospace';
      ctx.fillStyle = '#e2e8f0';
      var preview = pool[(wi) % pool.length] || '';
      ctx.fillText(preview, sx, 62);
      ctx.font = '11px "JetBrains Mono", monospace';
      ctx.fillStyle = '#64748b';
      ctx.fillText('TYPE THE', sx, 110);
      ctx.fillText('WORD TO', sx, 126);
      ctx.fillText('SMASH', sx, 142);
      ctx.fillStyle = '#fbbf24';
      ctx.fillText('LV ' + st.level, sx, 190);
      ctx.fillStyle = '#64748b';
      ctx.fillText('fall ' + fallSpeed().toFixed(1) + '/s', sx, 208);
      parts.draw(ctx);
      ctx.restore();
    }

    spawn();
    P.runLoop(step, draw, function () { return st.over; });
    input.focus();

    async function finish() {
      if (st.over) return;
      st.over = true;
      input.blur();
      P.Sfx.win();
      var out = await P.submitRun({ mode: 'blocks', mode_value: P.elapsedSec(st.startT), game_score: st.score, duration_seconds: P.elapsedSec(st.startT), total_keystrokes: Math.max(1, st.keys), error_count: st.errs, text_id: st.textId });
      P.celebrate(out, '<div class="text-4xl font-extrabold">' + st.score + ' <span class="text-base font-normal">pts</span></div><div class="text-xs">Level ' + st.level + ' · ' + st.smashed + ' smashed · ' + st.lines + ' lines</div>');
    }
  }
})();
