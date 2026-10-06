/* TypeForge landing interactions - vanilla, rAF-throttled, reduced-motion safe. */
(function () {
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function revealAll() {
    document.querySelectorAll('.tf-reveal').forEach(function (el) {
      el.classList.add('tf-revealed');
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    /* 1. Scroll reveal */
    var revealEls = document.querySelectorAll('.tf-reveal');
    if (reduced || !('IntersectionObserver' in window)) {
      revealAll();
    } else {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (en.isIntersecting) {
            en.target.classList.add('tf-revealed');
            io.unobserve(en.target);
          }
        });
      }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
      revealEls.forEach(function (el) { io.observe(el); });
    }

    /* 2. Animated stat counters */
    var counters = document.querySelectorAll('[data-count]');
    function animateCount(el) {
      var target = parseFloat(el.getAttribute('data-count') || '0');
      var decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
      var suffix = el.getAttribute('data-suffix') || '';
      if (reduced) {
        el.textContent = formatNum(target, decimals) + suffix;
        return;
      }
      var dur = 1400;
      var start = null;
      function frame(ts) {
        if (!start) start = ts;
        var p = Math.min(1, (ts - start) / dur);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = formatNum(target * eased, decimals) + suffix;
        if (p < 1) window.requestAnimationFrame(frame);
      }
      window.requestAnimationFrame(frame);
    }
    function formatNum(n, d) {
      return d > 0
        ? n.toFixed(d)
        : Math.round(n).toLocaleString('en-US');
    }
    if ('IntersectionObserver' in window && !reduced) {
      var cio = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (en.isIntersecting) {
            animateCount(en.target);
            cio.unobserve(en.target);
          }
        });
      }, { threshold: 0.4 });
      counters.forEach(function (el) { cio.observe(el); });
    } else {
      counters.forEach(animateCount);
    }

    /* 3. Hero code panel tilt (fine pointers only) */
    var demo = document.getElementById('code-demo');
    if (demo && !reduced && window.matchMedia('(pointer: fine)').matches) {
      var raf = null;
      demo.parentElement.classList.add('tf-tilt');
      demo.addEventListener('mousemove', function (e) {
        if (raf) return;
        raf = window.requestAnimationFrame(function () {
          raf = null;
          var r = demo.getBoundingClientRect();
          var x = (e.clientX - r.left) / r.width - 0.5;
          var y = (e.clientY - r.top) / r.height - 0.5;
          demo.style.transform = 'rotateX(' + (-y * 6).toFixed(2) + 'deg) rotateY(' + (x * 8).toFixed(2) + 'deg)';
        });
      });
      demo.addEventListener('mouseleave', function () {
        if (raf) { window.cancelAnimationFrame(raf); raf = null; }
        demo.style.transform = '';
      });
    }

    /* 4. Terminal typing loop */
    var typed = document.getElementById('demo-typed');
    if (typed && !reduced) {
      var lines = [
        '$forge->run(speed: 118, accuracy: 99.4);',
        '$forge->streak(days: 12, best: 142);',
        '$forge->rank(global: 37, delta: +5);'
      ];
      var li = 0, ci = 0, deleting = false;
      function tick() {
        var line = lines[li];
        if (!deleting) {
          ci++;
          typed.textContent = line.slice(0, ci);
          if (ci >= line.length) {
            deleting = true;
            window.setTimeout(tick, 1800);
            return;
          }
          window.setTimeout(tick, 34 + Math.random() * 46);
        } else {
          ci -= 3;
          if (ci <= 0) {
            ci = 0; deleting = false;
            li = (li + 1) % lines.length;
            window.setTimeout(tick, 400);
            return;
          }
          typed.textContent = line.slice(0, ci);
          window.setTimeout(tick, 18);
        }
      }
      tick();
    }

    /* 5. Navbar intensify on scroll */
    var nav = document.querySelector('nav.sticky');
    if (nav) {
      var onScroll = function () {
        nav.classList.toggle('scrolled', window.scrollY > 24);
      };
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();
    }

    /* 6. Hero shader grid parallax (rAF-throttled custom props, crash-safe) */
    try {
      var hero = document.querySelector('.hero-shader');
      if (hero && !reduced && window.matchMedia('(pointer: fine)').matches) {
        var heroRaf = null, hx = 0, hy = 0;
        var heroSection = hero.closest('section');
        var heroVisible = true;
        if ('IntersectionObserver' in window && heroSection) {
          new IntersectionObserver(function (entries) {
            heroVisible = entries[0].isIntersecting;
          }, { threshold: 0 }).observe(heroSection);
        }
        document.addEventListener('pointermove', function (e) {
          if (!heroVisible || heroRaf) return;
          heroRaf = window.requestAnimationFrame(function () {
            heroRaf = null;
            if (!heroVisible) return;
            var r = hero.getBoundingClientRect();
            hx = (e.clientX - r.left) / Math.max(1, r.width) - 0.5;
            hy = (e.clientY - r.top) / Math.max(1, r.height) - 0.5;
            hero.style.setProperty('--mx', hx.toFixed(3));
            hero.style.setProperty('--my', hy.toFixed(3));
          });
        }, { passive: true });
      }
    } catch (e) { /* static fallback stays */ }
  });
})();
