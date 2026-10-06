/* TypeForge page transition - brand curtain wipe on internal GET navigation.
 * Progressive enhancement: no-JS still navigates. POST forms, modified clicks,
 * new-tab, downloads and same-page anchors bypass. Failsafe forces navigation. */
(function () {
  'use strict';

  function samePage(url) {
    return url.pathname === window.location.pathname &&
      url.search === window.location.search;
  }

  document.addEventListener('DOMContentLoaded', function () {
    var curtain = document.getElementById('page-curtain');
    if (!curtain) return;

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var flag = null;
    try { flag = window.sessionStorage.getItem('tf_ptrans'); } catch (e) { flag = null; }

    /* Exit half on the destination page. */
    if (flag && !reduced) {
      try { window.sessionStorage.removeItem('tf_ptrans'); } catch (e) { /* noop */ }
      curtain.classList.add('pt-cover', 'pt-noanim');
      window.requestAnimationFrame(function () {
        window.requestAnimationFrame(function () {
          curtain.classList.remove('pt-noanim');
          curtain.classList.add('pt-leave');
          window.setTimeout(function () {
            curtain.classList.remove('pt-cover', 'pt-leave');
          }, 520);
        });
      });
    } else {
      curtain.classList.add('pt-idle');
    }

    if (reduced) return;

    var navigating = false;
    function go(href) {
      if (navigating) return;
      navigating = true;
      try { window.sessionStorage.setItem('tf_ptrans', '1'); } catch (e) { /* noop */ }
      curtain.classList.remove('pt-idle');
      curtain.classList.add('pt-enter');
      window.setTimeout(function () { window.location.href = href; }, 400);
      /* Failsafe: never trap the user even if animation stalls. */
      window.setTimeout(function () { window.location.href = href; }, 1400);
    }

    document.addEventListener('click', function (e) {
      if (e.defaultPrevented || navigating) return;
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
      if (!a || a.hasAttribute('download') || a.hasAttribute('data-no-transition')) return;
      if (a.target && a.target.toLowerCase() === '_blank') return;
      var raw = a.getAttribute('href') || '';
      if (raw === '' || raw.charAt(0) === '#' ||
        raw.indexOf('mailto:') === 0 || raw.indexOf('tel:') === 0 ||
        raw.indexOf('javascript:') === 0) return;
      var url;
      try { url = new URL(a.href, window.location.href); } catch (err) { return; }
      if (url.origin !== window.location.origin) return;
      if (samePage(url) && url.hash !== '') return;
      e.preventDefault();
      go(url.href);
    });
  });
})();
