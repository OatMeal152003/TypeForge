/* TypeForge intro loader - zero assets, vanilla JS. Shows once per tab session. */
(function () {
  function dismiss(loader, instant) {
    if (!loader || loader.dataset.done === '1') return;
    loader.dataset.done = '1';
    document.documentElement.classList.remove('intro-locked');
    document.body.classList.remove('intro-locked');
    if (instant) {
      loader.remove();
      focusHero();
      return;
    }
    loader.classList.add('intro-leaving');
    window.setTimeout(function () {
      loader.remove();
      focusHero();
    }, 450);
  }

  function focusHero() {
    var h1 = document.querySelector('main h1, section h1, h1');
    if (h1) {
      if (!h1.hasAttribute('tabindex')) h1.setAttribute('tabindex', '-1');
      try { h1.focus({ preventScroll: true }); } catch (e) { /* noop */ }
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var loader = document.getElementById('intro-loader');
    if (!loader) return;

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var seen = null;
    try { seen = window.sessionStorage.getItem('tf_intro'); } catch (e) { seen = null; }

    if (seen === '1' || reduced) {
      dismiss(loader, true);
      return;
    }

    document.documentElement.classList.add('intro-locked');
    document.body.classList.add('intro-locked');

    var bar = loader.querySelector('.intro-bar-fill');
    var pct = loader.querySelector('.intro-pct');
    var typeEl = loader.querySelector('.intro-type-text');
    var fullText = '> forging keys... 118 WPM';
    var start = null;
    var duration = 1600;
    var typed = 0;

    function frame(ts) {
      if (loader.dataset.done === '1') return;
      if (!start) start = ts;
      var p = Math.min(1, (ts - start) / duration);
      var eased = 1 - Math.pow(1 - p, 3);
      if (bar) bar.style.width = Math.round(eased * 100) + '%';
      if (pct) pct.textContent = Math.round(eased * 100) + '%';
      if (typeEl) {
        var want = Math.floor(eased * fullText.length);
        if (want > typed) {
          typed = want;
          typeEl.textContent = fullText.slice(0, typed);
        }
      }
      if (p < 1) {
        window.requestAnimationFrame(frame);
      } else {
        try { window.sessionStorage.setItem('tf_intro', '1'); } catch (e) { /* noop */ }
        dismiss(loader, false);
      }
    }
    window.requestAnimationFrame(frame);

    document.addEventListener('keydown', function onKey(e) {
      if (e.key === 'Escape') {
        try { window.sessionStorage.setItem('tf_intro', '1'); } catch (e2) { /* noop */ }
        dismiss(loader, true);
        document.removeEventListener('keydown', onKey);
      }
    });
    loader.addEventListener('click', function (e) {
      if (e.target === loader) {
        try { window.sessionStorage.setItem('tf_intro', '1'); } catch (err) { /* noop */ }
        dismiss(loader, true);
      }
    });
  });
})();
