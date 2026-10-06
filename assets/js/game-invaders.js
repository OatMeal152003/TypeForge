/* Pixel Invaders - type-to-shoot alien defense. Canvas + procedural sprites. */
(function () {
  'use strict';
  var P = window.Pixel;
  var W = 480, H = 600;
  var PX = 3;

  document.addEventListener('DOMContentLoaded', function () {
    var canvas = document.getElementById('game-canvas');
    if (!canvas || (window.GAME || '') !== 'invaders') return;
    var startBtn = document.getElementById('game-start-btn');
    startBtn.addEventListener('click', function () {
      document.getElementById('game-start').classList.add('hidden');
      play(canvas);
    });
  });

  async function play(canvas) {
    var ctx = P.fitCanvas(canvas, W, H);
    var atlas = P.buildAtlas();
    var parts = new P.Particles(160);
    var pack = await P.fetchWords(100);
    var pool = pack.words.slice();
    var pi = 0;
    function drawWord() {
      var w = pool[pi % pool.length];
      pi++;
      return w.toLowerCase().slice(0, 12);
    }

    var st = {
      over: false, startT: Date.now(), keys: 0, errs: 0,
      lives: 3, wave: 1, score: 0, combo: 0, bestCombo: 0,
      aliens: [], pellets: [], cannonX: W / 2, shake: 0, textId: pack.textId,
      stars: []
    };
    for (var s = 0; s < 60; s++) {
      st.stars.push({ x: Math.random() * W, y: Math.random() * H, v: 12 + Math.random() * 30 });
    }

    var hudScore = document.getElementById('hz-score');
    var hudWave = document.getElementById('hz-wave');
    var hudLives = document.getElementById('hz-lives');
    document.getElementById('game-hud').innerHTML =
      '<div><span class="hud-label">Score</span><span id="hz-score" class="hud-big text-emerald-400">0</span></div>'
      + '<div><span class="hud-label">Wave</span><span id="hz-wave" class="hud-big text-amber-400">1</span></div>'
      + '<div><span class="hud-label">Lives</span><span id="hz-lives" class="hud-big text-red-400">3</span></div>'
      + '<div><span class="hud-label">Combo</span><span id="hz-combo" class="hud-big">x0</span></div>';
    hudScore = document.getElementById('hz-score');
    hudWave = document.getElementById('hz-wave');
    hudLives = document.getElementById('hz-lives');
    var hudCombo = document.getElementById('hz-combo');

    function syncHud() {
      hudScore.textContent = st.score;
      hudWave.textContent = st.wave;
      hudLives.textContent = st.lives;
      hudCombo.textContent = 'x' + st.combo;
    }

    var SPR = ['alien1', 'alien2', 'alien3'];
    function spawnWave() {
      var cols = Math.min(6, 4 + Math.floor(st.wave / 2));
      var rows = Math.min(4, 2 + Math.floor(st.wave / 3));
      for (var r = 0; r < rows; r++) {
        for (var c = 0; c < cols; c++) {
          var maxLen = st.wave < 3 ? 4 : (st.wave < 5 ? 7 : 12);
          var w = drawWord();
          if (w.length > maxLen) w = w.slice(0, maxLen);
          st.aliens.push({
            x: 50 + c * ((W - 100) / Math.max(1, cols - 1)),
            y: 60 + r * 52,
            word: w, buf: '',
            spr: SPR[r % 3],
            wob: Math.random() * 6.28
          });
        }
      }
      P.Sfx.wave();
    }
    spawnWave();

    function target() {
      var best = null;
      for (var i = 0; i < st.aliens.length; i++) {
        if (!best || st.aliens[i].y > best.y) best = st.aliens[i];
      }
      return best;
    }

    var input = document.getElementById('game-input');
    P.rigInput(input, document.getElementById('game-arena'), function (ch) {
      if (st.over) return;
      ch = ch.toLowerCase();
      st.keys++;
      var t = target();
      if (!t) return;
      if (ch === t.word[t.buf.length]) {
        t.buf += ch;
        st.pellets.push({ x: st.cannonX, y: 548, tx: t.x, ty: t.y + 10, v: 620 });
        P.Sfx.shoot();
        if (t.buf === t.word) {
          var gain = 10 + st.combo * 2;
          st.score += gain;
          st.combo++;
          st.bestCombo = Math.max(st.bestCombo, st.combo);
          explode(t.x, t.y, t.spr);
          st.aliens.splice(st.aliens.indexOf(t), 1);
          P.Sfx.pop();
          syncHud();
          if (!st.aliens.length) {
            st.wave++;
            st.score += st.wave * 50;
            syncHud();
            spawnWave();
          }
        }
      } else {
        st.errs++;
        st.combo = 0;
        st.shake = P.REDUCED ? 0 : 7;
        P.Sfx.hurt();
        syncHud();
      }
    }, function () { /* letters only: ignore backspace */ });

    function explode(x, y, spr) {
      parts.burst(x, y, '#34d399', 14, 130);
      parts.burst(x, y, '#fbbf24', 8, 90);
      st.booms = st.booms || [];
      st.booms.push({ x: x, y: y, t: 0, spr: spr });
    }

    function step(dt) {
      var speed = 9 + st.wave * 2.2;
      for (var i = st.aliens.length - 1; i >= 0; i--) {
        var a = st.aliens[i];
        a.y += speed * dt;
        a.wob += dt * 3;
        if (a.y > 512) {
          explode(a.x, 520, a.spr);
          st.aliens.splice(i, 1);
          st.lives--;
          st.combo = 0;
          P.Sfx.hurt();
          syncHud();
          if (st.lives <= 0) { finish(); return; }
          if (!st.aliens.length) { st.wave++; spawnWave(); syncHud(); }
        }
      }
      // Pellets home toward their (possibly moved) target point.
      for (var j = st.pellets.length - 1; j >= 0; j--) {
        var p = st.pellets[j];
        var dx = p.tx - p.x, dy = p.ty - p.y;
        var d = Math.sqrt(dx * dx + dy * dy);
        if (d < 14) { st.pellets.splice(j, 1); continue; }
        p.x += (dx / d) * p.v * dt;
        p.y += (dy / d) * p.v * dt;
      }
      var t = target();
      if (t) st.cannonX += (t.x - st.cannonX) * Math.min(1, dt * 8);
      if (st.shake > 0) st.shake = Math.max(0, st.shake - dt * 30);
      // Starfield drift.
      for (var k = 0; k < st.stars.length; k++) {
        var s2 = st.stars[k];
        s2.y += s2.v * dt;
        if (s2.y > H) { s2.y = -2; s2.x = Math.random() * W; }
      }
      parts.step(dt);
      if (st.booms) {
        for (var b = st.booms.length - 1; b >= 0; b--) {
          st.booms[b].t += dt;
          if (st.booms[b].t > 0.35) st.booms.splice(b, 1);
        }
      }
      if ((Date.now() - st.startT) / 1000 > 300) finish();
    }

    function drawSprite(name, x, y) {
      var s = atlas[name];
      ctx.drawImage(s.cv, Math.round(x - (s.w * PX) / 2), Math.round(y - (s.h * PX) / 2), s.w * PX, s.h * PX);
    }

    function draw() {
      ctx.save();
      if (st.shake > 0) ctx.translate((Math.random() - 0.5) * st.shake, (Math.random() - 0.5) * st.shake);
      ctx.fillStyle = '#06090e';
      ctx.fillRect(-10, -10, W + 20, H + 20);
      // Stars.
      ctx.fillStyle = '#334155';
      for (var i = 0; i < st.stars.length; i++) {
        ctx.fillRect(Math.round(st.stars[i].x), Math.round(st.stars[i].y), 2, 2);
      }
      // Defense line.
      ctx.strokeStyle = 'rgba(248,113,113,.45)';
      ctx.setLineDash([6, 6]);
      ctx.beginPath();
      ctx.moveTo(16, 528);
      ctx.lineTo(W - 16, 528);
      ctx.stroke();
      ctx.setLineDash([]);
      // Aliens + labels.
      ctx.textAlign = 'center';
      for (var j = 0; j < st.aliens.length; j++) {
        var a = st.aliens[j];
        var ox = P.REDUCED ? 0 : Math.sin(a.wob) * 4;
        drawSprite(a.spr, a.x + ox, a.y);
        ctx.font = 'bold 13px "JetBrains Mono", monospace';
        var isT = (target() === a);
        ctx.textAlign = 'center';
        ctx.fillStyle = isT ? '#fbbf24' : '#94a3b8';
        ctx.fillText(a.word, a.x + ox, a.y + 34);
        if (a.buf.length) {
          var wFull = ctx.measureText(a.word).width;
          ctx.textAlign = 'left';
          ctx.fillStyle = '#34d399';
          ctx.fillText(a.buf, a.x + ox - wFull / 2, a.y + 34);
          ctx.textAlign = 'center';
        }
      }
      // Pellets.
      ctx.fillStyle = '#a7f3d0';
      for (var k = 0; k < st.pellets.length; k++) {
        ctx.fillRect(Math.round(st.pellets[k].x) - 1, Math.round(st.pellets[k].y) - 4, 3, 9);
      }
      // Explosions.
      if (st.booms) {
        for (var b = 0; b < st.booms.length; b++) {
          var bm = st.booms[b];
          drawSprite(bm.t < 0.18 ? 'boom1' : 'boom2', bm.x, bm.y);
        }
      }
      parts.draw(ctx);
      // Cannon.
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
      var out = await P.submitRun({ mode: 'invaders', mode_value: P.elapsedSec(st.startT), game_score: st.score, duration_seconds: P.elapsedSec(st.startT), total_keystrokes: Math.max(1, st.keys), error_count: st.errs, text_id: st.textId });
      P.celebrate(out, '<div class="text-4xl font-extrabold">' + st.score + ' <span class="text-base font-normal">pts</span></div><div class="text-xs">Wave ' + st.wave + ' · best combo x' + st.bestCombo + '</div>');
    }
  }
})();
