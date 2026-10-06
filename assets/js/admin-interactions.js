/* TypeForge admin interactions - scroll reveal + KPI count-up. Subtle, reduced-motion safe. */
(function () {
  'use strict';

  function formatNum(n, d) {
    return d > 0 ? n.toFixed(d) : Math.round(n).toLocaleString('en-US');
  }

  document.addEventListener('DOMContentLoaded', function () {
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Reveal */
    var reveals = document.querySelectorAll('.tf-reveal');
    if (reduced || !('IntersectionObserver' in window)) {
      reveals.forEach(function (el) { el.classList.add('tf-revealed'); });
    } else {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (en.isIntersecting) {
            en.target.classList.add('tf-revealed');
            io.unobserve(en.target);
          }
        });
      }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });
      reveals.forEach(function (el) { io.observe(el); });
    }

    /* Counters */
    function animate(el) {
      var target = parseFloat(el.getAttribute('data-count') || '0');
      var decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
      if (reduced) { el.textContent = formatNum(target, decimals); return; }
      var dur = 1100;
      var start = null;
      function frame(ts) {
        if (!start) start = ts;
        var p = Math.min(1, (ts - start) / dur);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = formatNum(target * eased, decimals);
        if (p < 1) window.requestAnimationFrame(frame);
      }
      window.requestAnimationFrame(frame);
    }
    var counters = document.querySelectorAll('[data-count]');
    if ('IntersectionObserver' in window && !reduced) {
      var cio = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (en.isIntersecting) {
            animate(en.target);
            cio.unobserve(en.target);
          }
        });
      }, { threshold: 0.4 });
      counters.forEach(function (el) { cio.observe(el); });
    } else {
      counters.forEach(animate);
    }
  });
})();
