/* Neon Rush - ghost-race typing. Your live WPM is your engine: outtype three
 * AI ghosts over a 40-word sprint. Position, speed, progress and errors on HUD. */
(function () {
  'use strict';
  var P = window.Pixel;
  var W = 480, H = 600;
  var PX = 3;

  var GHOSTS = [
    { name: 'Sparky', wpm: 35, color: '#60a5fa' },
    { name: 'Bolt', wpm: 55, color: '#c084fc' },
    { name: 'Vortex', wpm: 75, color: '#f87171' }
  ];
  var BONUS = [500, 300, 150, 50];

  document.addEventListener('DOMContentLoaded', function () {
    var canvas = document.getElementById('game-canvas');
    if (!canvas || (window.GAME || '') !== 'rush') return;
    var startBtn = document.getElementById('game-start-btn');
    startBtn.addEventListener('click', function () {
      document.getElementById('game-start').classList.add('hidden');
      play(canvas);
    });
  });

  async function play(canvas) {
    var ctx = P.fitCanvas(canvas, W, H);
    var parts = new P.Particles(160);
    var pack = await P.fetchWords(40);
    var text = pack.words.join(' ');
    var total = text.length;

    var st = {
      over: false, phase: 'count', countT: 0, goT: 0, startT: 0,
      idx: 0, errs: 0, keys: 0, textId: pack.textId,
      ghosts: [], shake: 0, done: false, place: 4, wpmNow: 0
    };
    for (var g = 0; g < GHOSTS.length; g++) {
      st.ghosts.push({ name: GHOSTS[g].name, wpm: GHOSTS[g].wpm, color: GHOSTS[g].color, prog: 0, finished: false, t: 0 });
    }

    document.getElementById('game-hud').innerHTML =
      '<div><span class="hud-label">Position</span><span id="hz-pos" class="hud-big text-emerald-400">4th</span></div>'
      + '<div><span class="hud-label">Speed</span><span id="hz-wpm" class="hud-big text-amber-400">0</span></div>'
      + '<div><span class="hud-label">Progress</span><span id="hz-prog" class="hud-big">0%</span></div>'
      + '<div><span class="hud-label">Errors</span><span id="hz-err" class="hud-big text-red-400">0</span></div>';
    var hudPos = document.getElementById('hz-pos');
    var hudWpm = document.getElementById('hz-wpm');
    var hudProg = document.getElementById('hz-prog');
    var hudErr = document.getElementById('hz-err');

    var ORD = ['1st', '2nd', '3rd', '4th'];
    function myProg() { return st.idx / total; }
    function place() {
      var ahead = 0;
      for (var i = 0; i < st.ghosts.length; i++) {
        if (st.ghosts[i].prog > myProg() || (st.ghosts[i].finished && !st.done)) ahead++;
      }
      return ahead + 1;
    }
    function liveWpm() {
      var s = Math.max(1, (Date.now() - st.startT) / 1000 / 60);
      return Math.round((st.idx / 5) / s);
    }
    function syncHud() {
      var p = place();
      hudPos.textContent = ORD[p - 1];
      hudPos.className = 'hud-big ' + (p === 1 ? 'text-emerald-400' : (p === 4 ? 'text-red-400' : 'text-amber-400'));
      hudWpm.textContent = st.phase === 'race' ? liveWpm() : 0;
      hudProg.textContent = Math.floor(myProg() * 100) + '%';
      hudErr.textContent = st.errs;
    }

    var input = document.getElementById('game-input');
    P.rigInput(input, document.getElementById('game-arena'), function (ch) {
      if (st.over || st.phase !== 'race') return;
      st.keys++;
      if (ch === text[st.idx]) {
        st.idx++;
        if (st.idx % 4 === 0) parts.burst(60, laneY(3) - 10, '#34d399', 2, 60);
        if (st.idx >= total) { finishRace(true); return; }
      } else {
        st.errs++;
        st.shake = P.REDUCED ? 0 : 5;
        P.Sfx.hurt();
      }
      syncHud();
    }, function () {
      // No backspace in a race: the track only moves forward.
      if (!st.over && st.phase === 'race' && st.idx > 0) { st.idx--; syncHud(); }
    });

    function laneY(lane) { return 150 + lane * 100; }

    function step(dt) {
      if (st.phase === 'count') {
        st.countT += dt;
        if (st.countT > 0.7) { st.countT = 0; st.count++; }
        if (st.count > 4) {
          st.phase = 'race';
          st.startT = Date.now();
          P.Sfx.wave();
        }
        return;
      }
      if (st.phase !== 'race') return;
      var t = (Date.now() - st.startT) / 1000;
      var allDone = true;
      for (var i = 0; i < st.ghosts.length; i++) {
        var gh = st.ghosts[i];
        if (!gh.finished) {
          gh.prog = Math.min(1, (gh.wpm / 5 * t) / total);
          if (gh.prog >= 1) { gh.finished = true; gh.t = t; P.Sfx.pop(); }
          else allDone = false;
        }
      }
      if (st.shake > 0) st.shake = Math.max(0, st.shake - dt * 30);
      parts.step(dt);
      syncHud();
      if (allDone && !st.done) {
        // Everyone beat you: crucible over, bank 4th.
        finishRace(false);
      }
      if ((Date.now() - st.startT) / 1000 > 600) finishRace(st.idx >= total);
    }

    function drawShip(x, y, color, glow, name, finished) {
      ctx.globalAlpha = finished ? 0.45 : 1;
      if (glow) {
        ctx.globalAlpha *= 1;
        ctx.fillStyle = color;
        ctx.globalAlpha = 0.22;
        ctx.fillRect(x - 26, y - 12, 52, 24);
        ctx.globalAlpha = finished ? 0.45 : 1;
      }
      // Hull.
      ctx.fillStyle = color;
      ctx.fillRect(x - 18, y - 7, 30, 14);
      ctx.fillRect(x + 8, y - 11, 10, 22);
      // Cockpit.
      ctx.fillStyle = '#06090e';
      ctx.fillRect(x + 2, y - 3, 8, 6);
      // Engine flame.
      if (!finished && !P.REDUCED) {
        var f = 6 + Math.random() * 8;
        ctx.fillStyle = '#fbbf24';
        ctx.fillRect(x - 18 - f, y - 3, f, 6);
        ctx.fillStyle = '#f97316';
        ctx.fillRect(x - 18 - f, y - 1, f, 2);
      }
      ctx.fillStyle = '#94a3b8';
      ctx.font = '10px "JetBrains Mono", monospace';
      ctx.textAlign = 'left';
      ctx.fillText(name, x - 18, y - 14);
      ctx.globalAlpha = 1;
    }

    function draw() {
      ctx.save();
      if (st.shake > 0) ctx.translate((Math.random() - 0.5) * st.shake, 0);
      var g = ctx.createLinearGradient(0, 0, 0, H);
      g.addColorStop(0, '#020617');
      g.addColorStop(1, '#0a0f1e');
      ctx.fillStyle = g;
      ctx.fillRect(-10, -10, W + 20, H + 20);
      // Lane guides + finish line at right.
      for (var l = 0; l < 4; l++) {
        var y = laneY(l);
        ctx.strokeStyle = 'rgba(148,163,184,0.16)';
        ctx.setLineDash([4, 8]);
        ctx.beginPath();
        ctx.moveTo(10, y + 22);
        ctx.lineTo(W - 10, y + 22);
        ctx.stroke();
        ctx.setLineDash([]);
      }
      ctx.fillStyle = 'rgba(52,211,153,0.7)';
      for (var c = 0; c < 12; c++) {
        ctx.fillRect(W - 34, 110 + c * 36, (c % 2 === 0) ? 8 : 4, 18);
      }
      // Ghosts.
      var order = ['you'].concat(st.ghosts.map(function (gh) { return gh.name; }));
      var positions = [{ prog: myProg(), you: true }].concat(st.ghosts);
      for (var i = 0; i < positions.length; i++) {
        var p = positions[i];
        var lane = order.indexOf(p.you ? 'you' : p.name);
        var x = 30 + p.prog * (W - 90);
        if (p.you) drawShip(x, laneY(lane), '#34d399', true, 'YOU', st.done);
        else drawShip(x, laneY(lane), p.color, false, p.name, p.finished);
      }
      // Passage ribbon (your text, typed prefix lit).
      ctx.fillStyle = 'rgba(6,9,14,0.85)';
      ctx.fillRect(10, H - 118, W - 20, 108);
      ctx.strokeStyle = 'rgba(48,54,61,0.9)';
      ctx.strokeRect(10.5, H - 117.5, W - 21, 107);
      ctx.font = '13px "JetBrains Mono", monospace';
      ctx.textAlign = 'left';
      var line = text.slice(Math.max(0, st.idx - 60), st.idx + 60);
      var base = Math.max(0, st.idx - 60);
      var cx = 24, cy = H - 88;
      for (var k = 0; k < line.length; k++) {
        var global = base + k;
        var ch = line[k];
        ctx.fillStyle = global < st.idx ? '#34d399' : (global === st.idx ? '#fbbf24' : '#64748b');
        if (global === st.idx && !P.REDUCED) {
          ctx.fillRect(cx - 1, cy - 12, 2, 16);
        }
        ctx.fillText(ch === ' ' ? '·' : ch, cx, cy);
        cx += ctx.measureText(ch === ' ' ? '·' : ch).width;
        if (cx > W - 40 && k < line.length - 1) break;
      }
      parts.draw(ctx);
      // Countdown overlay.
      if (st.phase === 'count') {
        ctx.fillStyle = 'rgba(6,9,14,0.6)';
        ctx.fillRect(0, 0, W, H);
        ctx.textAlign = 'center';
        ctx.fillStyle = '#fbbf24';
        ctx.font = 'bold 72px "JetBrains Mono", monospace';
        ctx.fillText(st.count <= 3 ? String(4 - st.count) : 'GO!', W / 2, H / 2);
        ctx.font = '13px "JetBrains Mono", monospace';
        ctx.fillStyle = '#94a3b8';
        ctx.fillText('Sparky 35 · Bolt 55 · Vortex 75 WPM', W / 2, H / 2 + 40);
      }
      ctx.restore();
    }

    st.count = 1;
    var stop = P.runLoop(step, draw, function () { return st.over; });
    input.focus();

    async function finishRace(completed) {
      if (st.over) return;
      st.over = true;
      try { stop(); } catch (e) { /* noop */ }
      input.blur();
      var secs = P.elapsedSec(st.startT || Date.now());
      var wpm = 0, acc = 100;
      if (completed) {
        st.done = true;
        var mins = Math.max(1 / 60, secs / 60);
        wpm = Math.round((st.idx / 5) / mins);
        acc = st.keys > 0 ? Math.round(Math.max(0, (st.keys - st.errs) / st.keys) * 100) : 100;
      }
      var p = completed ? place() : 4;
      var score = completed ? Math.round(wpm * 10) + BONUS[p - 1] : 50;
      P.Sfx.win();
      parts.burst(W / 2, H / 2, '#34d399', 40, 200);
      var out = await P.submitRun({ mode: 'rush', mode_value: secs, game_score: score, duration_seconds: secs, total_keystrokes: Math.max(1, st.keys), error_count: st.errs, text_id: st.textId });
      P.celebrate(out, '<div class="text-4xl font-extrabold">' + score + ' <span class="text-base font-normal">pts</span></div><div class="text-xs">Finished ' + (completed ? ORD[p - 1] + ' of 4' : '4th of 4') + (completed ? ' · ' + wpm + ' WPM' : '') + '</div>');
    }
    var ORD = ['1st', '2nd', '3rd', '4th'];
  }
})();
