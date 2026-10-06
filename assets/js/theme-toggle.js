/* TypeForge light/dark toggle - localStorage instant + server persist for members. */
(function () {
  'use strict';

  function isLight() {
    return document.documentElement.getAttribute('data-theme') === 'light';
  }

  function paint() {
    var light = isLight();
    document.querySelectorAll('.tf-theme-toggle').forEach(function (btn) {
      btn.setAttribute('aria-checked', light ? 'true' : 'false');
      btn.setAttribute('aria-label', light ? 'Switch to dark mode' : 'Switch to light mode');
      btn.setAttribute('title', light ? 'Switch to dark mode' : 'Switch to light mode');
      var icon = btn.querySelector('.tf-mat');
      if (icon) icon.textContent = light ? 'dark_mode' : 'light_mode';
    });
  }

  function persistServer(btn, theme) {
    if (btn.getAttribute('data-logged') !== '1') return;
    var api = btn.getAttribute('data-api') || 'api/update-settings.php';
    var csrf = btn.getAttribute('data-csrf') || '';
    var sound = btn.getAttribute('data-sound') === '1' ? 1 : 0;
    try {
      fetch(api, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'update_preferences',
          theme: theme,
          sound_enabled: sound,
          csrf_token: csrf
        })
      }).catch(function () { /* local theme still applies */ });
    } catch (e) { /* noop */ }
  }

  document.addEventListener('DOMContentLoaded', function () {
    paint();
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var veil = null;
    var veiling = false;

    function getVeil() {
      if (veil) return veil;
      veil = document.createElement('div');
      veil.id = 'theme-veil';
      veil.setAttribute('aria-hidden', 'true');
      veil.innerHTML = '<span class="material-symbols-rounded tf-mat veil-icon" aria-hidden="true">light_mode</span>';
      document.body.appendChild(veil);
      return veil;
    }

    function applyTheme(btn, next) {
      document.documentElement.setAttribute('data-theme', next);
      try { window.localStorage.setItem('tf_theme', next); } catch (e) { /* noop */ }
      paint();
      persistServer(btn, next);
    }

    document.querySelectorAll('.tf-theme-toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (veiling) return;
        var next;
        if (isLight()) {
          try {
            next = window.localStorage.getItem('tf_theme_dark') || 'dark';
          } catch (e) { next = 'dark'; }
          if (next === 'light') next = 'dark';
        } else {
          try {
            window.localStorage.setItem('tf_theme_dark', document.documentElement.getAttribute('data-theme') || 'dark');
          } catch (e) { /* noop */ }
          next = 'light';
        }
        /* Reduced motion: instant swap, no swipe. */
        if (reduced) { applyTheme(btn, next); return; }
        /* Swipe: veil covers, theme swaps mid-cover, veil exits downward. */
        veiling = true;
        var v = getVeil();
        var icon = v.querySelector('.veil-icon');
        if (icon) icon.textContent = next === 'light' ? 'light_mode' : 'dark_mode';
        v.classList.remove('veil-light', 'veil-dark', 'veil-leave');
        v.classList.add(next === 'light' ? 'veil-light' : 'veil-dark');
        void v.offsetWidth;
        v.classList.add('veil-cover');
        window.setTimeout(function () {
          applyTheme(btn, next);
          v.classList.remove('veil-cover');
          v.classList.add('veil-leave');
          window.setTimeout(function () {
            v.classList.remove('veil-light', 'veil-dark', 'veil-leave');
            veiling = false;
          }, 480);
        }, 380);
      });
    });
  });
})();
