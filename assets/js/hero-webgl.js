/* TypeForge hero WebGL - real geometric warp + cursor chroma, shadertoy-style.
 * Safest-first: half-res, DPR cap 1, weak-device gate, frame watchdog auto-kill,
 * pause offscreen/hidden, frozen frame for reduced-motion. CSS stack underneath
 * stays as the fallback - worst case is today's look, never blank or crash. */
(function () {
  'use strict';

  var VERT =
    'attribute vec2 a_pos;\n' +
    'void main(){ gl_Position = vec4(a_pos, 0.0, 1.0); }';

  var FRAG =
    'precision mediump float;\n' +
    'uniform vec2 u_res;\n' +
    'uniform float u_time;\n' +
    'uniform vec2 u_mouse;\n' + // uv space, y up
    'float hash(vec2 p){ return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453); }\n' +
    'float noise(vec2 p){\n' +
    '  vec2 i = floor(p); vec2 f = fract(p);\n' +
    '  vec2 u = f * f * (3.0 - 2.0 * f);\n' +
    '  return mix(mix(hash(i), hash(i + vec2(1.0, 0.0)), u.x),\n' +
    '             mix(hash(i + vec2(0.0, 1.0)), hash(i + vec2(1.0, 1.0)), u.x), u.y);\n' +
    '}\n' +
    'float fbm(vec2 p){\n' +
    '  float v = 0.0; float a = 0.5;\n' +
    '  for(int i = 0; i < 3; i++){ v += a * noise(p); p *= 2.03; a *= 0.5; }\n' +
    '  return v;\n' +
    '}\n' +
    // Cheap geometric grid intensity at a warped coordinate. Real warp, cheap eval.
    'float gridAt(vec2 guv, float dist){\n' +
    '  float gx = abs(fract(guv.x) - 0.5);\n' +
    '  float gy = abs(fract(guv.y) - 0.5);\n' +
    '  float lx = 1.0 - smoothstep(0.42, 0.5, gx);\n' +
    '  float ly = 1.0 - smoothstep(0.42, 0.5, gy);\n' +
    '  return max(lx, ly) * exp(-dist * 2.4);\n' +
    '}\n' +
    'void main(){\n' +
    '  vec2 uv = gl_FragCoord.xy / u_res;\n' +
    '  float aspect = u_res.x / u_res.y;\n' +
    '  vec2 p = uv; p.x *= aspect;\n' +
    '  vec2 m = u_mouse; m.x *= aspect;\n' +
    '  float t = u_time * 0.06;\n' +
    // Aurora base (single fbm eval - the expensive part, done once).
    '  float n = fbm(p * 1.7 + vec2(t * 0.7, -t * 0.35) + fbm(p * 2.1 + t) * 0.55);\n' +
    '  vec3 col = mix(vec3(0.024, 0.035, 0.055), vec3(0.02, 0.10, 0.09), smoothstep(0.2, 0.8, n));\n' +
    '  col += vec3(0.06, 0.42, 0.30) * pow(smoothstep(0.45, 0.95, n), 2.0) * 0.55;\n' +
    '  col += vec3(0.09, 0.32, 0.42) * pow(smoothstep(0.5, 1.0, fbm(p * 2.3 - vec2(t, 0.0))), 2.0) * 0.30;\n' +
    // Real geometric warp: grid pulled toward the cursor.
    '  vec2 gp = p * 9.0;\n' +
    '  vec2 d = p - m;\n' +
    '  float dist = max(length(d), 1e-4);\n' +
    '  vec2 pull = -d / dist * exp(-dist * 4.2) * 1.5;\n' +
    '  vec2 guv = gp + pull + vec2(t * 0.15, -t * 0.1);\n' +
    // Chroma: grid re-evaluated with radial channel offsets around cursor.
    '  float dd = length(uv - u_mouse);\n' +
    '  vec2 dirr = (uv - u_mouse) / max(dd, 1e-3);\n' +
    '  float chr = 0.010 * exp(-dd * 3.0) + 0.0012;\n' +
    '  vec2 asp = vec2(aspect, 1.0);\n' +
    '  float gr = gridAt(guv + dirr * chr * asp * 9.0, dist);\n' +
    '  float gg = gridAt(guv, dist);\n' +
    '  float gb = gridAt(guv - dirr * chr * asp * 9.0, dist);\n' +
    '  vec3 lineCol = vec3(0.20, 0.83, 0.60) * 0.55;\n' +
    '  col += vec3(gr, gg, gb) * lineCol;\n' +
    // Cursor bloom.
    '  col += vec3(0.10, 0.55, 0.42) * exp(-dd * 13.0) * 0.55;\n' +
    // Vignette keeps headline readable (medium intensity).
    '  vec2 vc = uv - 0.5;\n' +
    '  col *= 1.0 - dot(vc, vc) * 0.85;\n' +
    '  gl_FragColor = vec4(col, 1.0);\n' +
    '}';

  function abort(hero) {
    if (hero) hero.classList.remove('gl-on');
  }

  document.addEventListener('DOMContentLoaded', function () {
    var hero = document.querySelector('.hero-shader');
    var canvas = document.getElementById('hero-gl');
    if (!hero || !canvas) return;

    var booted = false;
    var killActive = null;
    function isLightTheme() { return document.documentElement.getAttribute('data-theme') === 'light'; }
    function boot() {
      if (booted) return;
      booted = true;
    try {
      // Weak-device gate: integrated/low-RAM hardware keeps the CSS look.
      var mem = navigator.deviceMemory || 8;
      var cores = navigator.hardwareConcurrency || 8;
      if (mem <= 4 || cores <= 4) { abort(hero); return; }

      var gl = canvas.getContext('webgl', {
        antialias: false, depth: false, stencil: false, alpha: false,
        powerPreference: 'low-power', failIfMajorPerformanceCaveat: true
      });
      if (!gl) { abort(hero); return; }

      var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      var coarse = window.matchMedia('(pointer: coarse)').matches;

      function compile(type, src) {
        var s = gl.createShader(type);
        gl.shaderSource(s, src);
        gl.compileShader(s);
        if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) {
          gl.deleteShader(s);
          return null;
        }
        return s;
      }
      var vs = compile(gl.VERTEX_SHADER, VERT);
      var fs = compile(gl.FRAGMENT_SHADER, FRAG);
      if (!vs || !fs) { abort(hero); return; }
      var prog = gl.createProgram();
      gl.attachShader(prog, vs);
      gl.attachShader(prog, fs);
      gl.linkProgram(prog);
      if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) { abort(hero); return; }
      gl.useProgram(prog);

      var buf = gl.createBuffer();
      gl.bindBuffer(gl.ARRAY_BUFFER, buf);
      gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW);
      var loc = gl.getAttribLocation(prog, 'a_pos');
      gl.enableVertexAttribArray(loc);
      gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);

      var uRes = gl.getUniformLocation(prog, 'u_res');
      var uTime = gl.getUniformLocation(prog, 'u_time');
      var uMouse = gl.getUniformLocation(prog, 'u_mouse');

      var SCALE = 0.5;
      var dpr = Math.min(window.devicePixelRatio || 1, 1);
      function resize() {
        var r = hero.getBoundingClientRect();
        var w = Math.max(2, Math.floor(r.width * SCALE * dpr));
        var h = Math.max(2, Math.floor(r.height * SCALE * dpr));
        if (canvas.width !== w || canvas.height !== h) {
          canvas.width = w;
          canvas.height = h;
          gl.viewport(0, 0, w, h);
        }
      }
      resize();
      window.addEventListener('resize', resize);

      // Eased cursor in uv space (y up). Rests center on touch devices.
      var tx = 0.5, ty = 0.45, mx = 0.5, my = 0.45;
      if (!coarse) {
        document.addEventListener('pointermove', function (e) {
          var r = hero.getBoundingClientRect();
          if (r.width < 1 || r.height < 1) return;
          tx = (e.clientX - r.left) / r.width;
          ty = 1 - (e.clientY - r.top) / r.height;
        }, { passive: true });
      }

      var raf = null;
      var running = true;
      var firstFrame = false;
      var startT = performance.now();
      var slowFrames = 0;

      function draw(now) {
        if (!running) return;
        // Ease cursor toward target (frame-rate independent-ish).
        mx += (tx - mx) * 0.08;
        my += (ty - my) * 0.08;
        gl.uniform2f(uRes, canvas.width, canvas.height);
        gl.uniform1f(uTime, (now - startT) / 1000);
        gl.uniform2f(uMouse, mx, my);
        gl.drawArrays(gl.TRIANGLES, 0, 3);
        if (!firstFrame) {
          firstFrame = true;
          hero.classList.add('gl-on');
        }
      }

      if (reduced) {
        // One frozen frame, no loop.
        draw(performance.now());
        return;
      }

      var last = performance.now();
      var visible = true;
      function frame(now) {
        if (!running) return;
        var dt = now - last;
        last = now;
        if (!document.hidden && visible) {
          draw(now);
          // Watchdog: sustained slow frames -> kill WebGL, CSS takes over.
          if (dt > 26) {
            slowFrames++;
            if (slowFrames >= 45) { kill(); return; }
          } else if (slowFrames > 0) {
            slowFrames--;
          }
        }
        raf = window.requestAnimationFrame(frame);
      }

      killActive = kill;
      function kill() {
        booted = false;
        killActive = null;
        running = false;
        if (raf) window.cancelAnimationFrame(raf);
        try {
          var ext = gl.getExtension('WEBGL_lose_context');
          if (ext) ext.loseContext();
        } catch (e) { /* noop */ }
        abort(hero);
      }

      if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (entries) {
          visible = entries[0].isIntersecting;
          if (visible) last = performance.now();
        }, { threshold: 0 }).observe(hero.closest('section') || hero);
      }
      document.addEventListener('visibilitychange', function () {
        if (!document.hidden) last = performance.now();
      });

      raf = window.requestAnimationFrame(frame);
    } catch (e) {
      abort(hero);
    }
    } // end boot
    // Light theme hides the dark WebGL scene: shut it down (and reboot on return to dark).
    if ('MutationObserver' in window) {
      new MutationObserver(function () {
        if (isLightTheme()) { if (killActive) killActive(); }
        else { boot(); }
      }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    }
    if (!isLightTheme()) { boot(); }
  });
})();
