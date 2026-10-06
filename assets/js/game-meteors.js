/* Meteor Defense - missile-command typing. Angled meteors streak at your city;
 * finish a meteor's word to blast it mid-air. 3 city hits ends the run. */
(function () {
  'use strict';
  var P = window.Pixel;
  var W = 480, H = 600;
  var CITY_Y = 520;
  var PX = 3;

  document.addEventListener('DOMContentLoaded', function () {
    var canvas = document.getElementById('game-canvas');
    if (!canvas || (window.GAME || '') !== 'meteors') return;
    var startBtn = document.getElementById('game-start-btn');
    startBtn.addEventListener('click', function () {
      document.getElementById('game-start').classList.add('hidden');
      play(canvas);
    });
  });

  async function play(canvas) {
    var ctx = P.fitCanvas(canvas, W, H);
    var atlas = P.buildAtlas();
    var parts = new P.Particles(200);
    var pack = await P.fetchWords(120);
    var pool = pack.words.slice();
    var pi = 0;
    function drawWord(maxLen) {
      var w = pool[pi % pool.length].toLowerCase().replace(/[^a-z]/g, '');
      pi++;
      if (!w) w = 'meteor';
      return w.slice(0, Math.max(3, maxLen));
    }

    var st = {
      over: false, startT: Date.now(), keys: 0, errs: 0,
      hits: 0, wave: 1, score: 0, combo: 0, bestCombo: 0, kills: 0,
      rocks: [], bolts: [], cannonX: W / 2, shake: 0, textId: pack.textId,
      spawnT: 0
    };

    document.getElementById('game-hud').innerHTML =
      '<div><span class="hud-label">Score</span><span id="hz-score" class="hud-big text-emerald-400">0</span></div>'
      + '<div><span class="hud-label">Wave</span><span id="hz-wave" class="hud-big text-amber-400">1</span></div>'
      + '<div><span class="hud-label">City</span><span id="hz-lives" class="hud-big text-red-400">3</span></div>'
      + '<div><span class="hud-label">Combo</span><span id="hz-combo" class="hud-big">x0</span></div>';
    var hudScore = document.getElementById('hz-score');
    var hudWave = document.getElementById('hz-wave');
    var hudLives = document.getElementById('hz-lives');
    var hudCombo = document.getElementById('hz-combo');

    function syncHud() {
      hudScore.textContent = st.score;
      hudWave.textContent = st.wave;
      hudLives.textContent = Math.max(0, 3 - st.hits);
      hudCombo.textContent = 'x' + st.combo;
    }

    function spawnRock() {
      var maxLen = st.wave < 2 ? 4 : (st.wave < 4 ? 6 : (st.wave < 6 ? 9 : 12));
      var x = 40 + Math.random() * (W - 80);
      // Angled descent toward the city: steeper at higher waves.
      var speed = 34 + st.wave * 7 + Math.random() * 14;
      var drift = (Math.random() - 0.5) * (10 + st.wave * 6);
      var size = 2 + Math.floor(Math.random() * 3) + Math.floor(st.wave / 3);
      st.rocks.push({ x: x, y: -20, vx: drift, vy: speed, word: drawWord(maxLen), buf: '', size: Math.min(5, size), trail: Math.random() * 10 });
    }

    function target() {
      var best = null;
      for (var i = 0; i < st.rocks.length; i++) {
        if (!best || st.rocks[i].y > best.y) best = st.rocks[i];
      }
      return best;
    }

    var input = document.getElementById('game-input');
    P.rigInput(input, document.getElementById('game-arena'), function (ch) {
      if (st.over) return;
      ch = ch.toLowerCase();
      if (ch < 'a' || ch > 'z') return;
      st.keys++;
      var t = target();
      if (!t) return;
      if (ch === t.word[t.buf.length]) {
        t.buf += ch;
        st.bolts.push({ x: st.cannonX, y: 548, tx: t.x, ty: t.y, v: 700 });
        P.Sfx.shoot();
        if (t.buf === t.word) {
          var gain = 10 + t.word.length * 3 + st.combo * 2;
          st.score += gain;
          st.combo++;
          st.bestCombo = Math.max(st.bestCombo, st.combo);
          st.kills++;
          explode(t.x, t.y, t.size);
          st.rocks.splice(st.rocks.indexOf(t), 1);
          P.Sfx.pop();
          syncHud();
        }
      } else {
        st.errs++;
        st.combo = 0;
        st.shake = P.REDUCED ? 0 : 6;
        P.Sfx.hurt();
        syncHud();
      }
    }, function () { /* letters only: ignore backspace */ });

    function explode(x, y, size) {
      parts.burst(x, y, '#fb923c', 10 + size * 3, 150);
      parts.burst(x, y, '#fbbf24', 8, 100);
      parts.burst(x, y, '#78716c', 6, 70);
      st.booms = st.booms || [];
      st.booms.push({ x: x, y: y, t: 0 });
    }

    function cityHit(r) {
      explode(r.x, CITY_Y, 5);
      st.hits++;
      st.combo = 0;
      st.shake = P.REDUCED ? 0 : 12;
      P.Sfx.hurt();
      syncHud();
      if (st.hits >= 3) finish();
    }

    function step(dt) {
      // Spawn pacing tightens every wave.
      st.spawnT -= dt;
      var alive = st.rocks.length;
      var cap = Math.min(6, 2 + Math.floor(st.wave / 2));
      if (st.spawnT <= 0 && alive < cap) {
        spawnRock();
        st.spawnT = Math.max(0.35, 1.6 - st.wave * 0.16);
      }
      for (var i = st.rocks.length - 1; i >= 0; i--) {
        var r = st.rocks[i];
        r.x += r.vx * dt;
        r.y += r.vy * dt;
        r.trail += dt * 20;
        if (!P.REDUCED && r.trail > 1) {
          r.trail = 0;
          parts.burst(r.x, r.y - 8, Math.random() < 0.5 ? '#f97316' : '#fbbf24', 1, 20);
        }
        if (r.x < 16) { r.x = 16; r.vx = Math.abs(r.vx); }
        if (r.x > W - 16) { r.x = W - 16; r.vx = -Math.abs(r.vx); }
        if (r.y >= CITY_Y) {
          st.rocks.splice(i, 1);
          cityHit(r);
          if (st.over) return;
        }
      }
      // Wave clears when the sky is empty: bonus + faster sky.
      if (!st.rocks.length && !st.over) {
        st.wave++;
        st.score += st.wave * 40;
        P.Sfx.wave();
        syncHud();
      }
      // Interceptor bolts home in.
      for (var j = st.bolts.length - 1; j >= 0; j--) {
        var p = st.bolts[j];
        var dx = p.tx - p.x, dy = p.ty - p.y;
        var d = Math.sqrt(dx * dx + dy * dy);
        if (d < 16) { st.bolts.splice(j, 1); continue; }
        p.x += (dx / d) * p.v * dt;
        p.y += (dy / d) * p.v * dt;
      }
      var t = target();
      if (t) st.cannonX += (t.x - st.cannonX) * Math.min(1, dt * 8);
      if (st.shake > 0) st.shake = Math.max(0, st.shake - dt * 30);
      parts.step(dt);
      if (st.booms) {
        for (var b = st.booms.length - 1; b >= 0; b--) {
          st.booms[b].t += dt;
          if (st.booms[b].t > 0.4) st.booms.splice(b, 1);
        }
      }
      if ((Date.now() - st.startT) / 1000 > 300) finish();
    }

    function drawRock(r) {
      var s = r.size; // pixel radius-ish
      var x = Math.round(r.x), y = Math.round(r.y);
      // Fiery glow.
      ctx.globalAlpha = 0.25;
      ctx.fillStyle = '#f97316';
      ctx.fillRect(x - s * 3, y - s * 3, s * 6, s * 6);
      ctx.globalAlpha = 1;
      // Rocky body.
      ctx.fillStyle = '#92400e';
      ctx.fillRect(x - s * 2, y - s, s * 4, s * 2);
      ctx.fillRect(x - s, y - s * 2, s * 2, s * 4);
      ctx.fillStyle = '#451a03';
      ctx.fillRect(x - s, y - s, s, s);
      ctx.fillRect(x + 1, y + 1, s - 1, s - 1);
      // Hot rim.
      ctx.fillStyle = '#fdba74';
      ctx.fillRect(x - s * 2, y - s * 2, s * 4, 1);
      ctx.fillRect(x - s * 2, y - s * 2, 1, s);
    }

    function drawSprite(name, x, y) {
      var sp = atlas[name];
      ctx.drawImage(sp.cv, Math.round(x - (sp.w * PX) / 2), Math.round(y - (sp.h * PX) / 2), sp.w * PX, sp.h * PX);
    }

    function draw() {
      ctx.save();
      if (st.shake > 0) ctx.translate((Math.random() - 0.5) * st.shake, (Math.random() - 0.5) * st.shake);
      // Night sky gradient.
      var g = ctx.createLinearGradient(0, 0, 0, H);
      g.addColorStop(0, '#020617');
      g.addColorStop(0.75, '#0b1020');
      g.addColorStop(1, '#06090e');
      ctx.fillStyle = g;
      ctx.fillRect(-10, -10, W + 20, H + 20);
      // Stars.
      ctx.fillStyle = '#334155';
      for (var i = 0; i < 40; i++) {
        var sx = (i * 97) % W, sy = (i * 61) % 420;
        ctx.fillRect(sx, sy, 2, 2);
      }
      // City skyline.
      ctx.fillStyle = '#111c2e';
      var bw = 34;
      for (var b = 0; b * bw < W; b++) {
        var bh = 26 + ((b * 53) % 30);
        ctx.fillRect(b * bw + 4, CITY_Y + 46 - bh, bw - 8, bh);
        ctx.fillStyle = 'rgba(251,191,36,0.5)';
        ctx.fillRect(b * bw + 12, CITY_Y + 46 - bh + 6, 3, 3);
        ctx.fillRect(b * bw + 20, CITY_Y + 46 - bh + 12, 3, 3);
        ctx.fillStyle = '#111c2e';
      }
      // Impact line.
      ctx.strokeStyle = 'rgba(248,113,113,.5)';
      ctx.setLineDash([6, 6]);
      ctx.beginPath();
      ctx.moveTo(16, CITY_Y);
      ctx.lineTo(W - 16, CITY_Y);
      ctx.stroke();
      ctx.setLineDash([]);
      // Meteors + labels.
      ctx.textAlign = 'center';
      for (var j = 0; j < st.rocks.length; j++) {
        var r = st.rocks[j];
        drawRock(r);
        var isT = (target() === r);
        ctx.font = 'bold 13px "JetBrains Mono", monospace';
        ctx.fillStyle = isT ? '#fbbf24' : '#cbd5e1';
        ctx.fillText(r.word, r.x, r.y + 26);
        if (r.buf.length) {
          var wFull = ctx.measureText(r.word).width;
          ctx.textAlign = 'left';
          ctx.fillStyle = '#34d399';
          ctx.fillText(r.buf, r.x - wFull / 2, r.y + 26);
          ctx.textAlign = 'center';
        }
      }
      // Bolts.
      ctx.fillStyle = '#fecaca';
      for (var k = 0; k < st.bolts.length; k++) {
        ctx.fillRect(Math.round(st.bolts[k].x) - 1, Math.round(st.bolts[k].y) - 5, 3, 10);
      }
      // Blasts.
      if (st.booms) {
        for (var m = 0; m < st.booms.length; m++) {
          var bm = st.booms[m];
          drawSprite(bm.t < 0.2 ? 'boom1' : 'boom2', bm.x, bm.y);
        }
      }
      parts.draw(ctx);
      drawSprite('cannon', st.cannonX, 566);
      ctx.restore();
    }

    var stop = P.runLoop(step, draw, function () { return st.over; });
    input.focus();

    async function finish() {
      if (st.over) return;
      st.over = true;
      try { stop(); } catch (e) { /* noop */ }
      input.blur();
      P.Sfx.win();
      var out = await P.submitRun({ mode: 'meteors', mode_value: P.elapsedSec(st.startT), game_score: st.score, duration_seconds: P.elapsedSec(st.startT), total_keystrokes: Math.max(1, st.keys), error_count: st.errs, text_id: st.textId });
      P.celebrate(out, '<div class="text-4xl font-extrabold">' + st.score + ' <span class="text-base font-normal">pts</span></div><div class="text-xs">Wave ' + st.wave + ' · ' + st.kills + ' intercepted · best combo x' + st.bestCombo + '</div>');
    }
  }
})();
