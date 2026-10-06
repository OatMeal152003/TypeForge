// TypeForge sidebar hamburger: collapse desktop sidebar to an icon rail.
// State persists per sidebar (user vs admin) in localStorage. Mobile untouched.
document.addEventListener('DOMContentLoaded', () => {
    const aside = document.querySelector('aside');
    const btn = document.getElementById('sidebar-hamburger');
    if (!aside || !btn) return;

    const key = aside.classList.contains('admin-nav') ? 'tf_nav_admin' : 'tf_nav_user';

    const apply = (collapsed) => {
        aside.classList.toggle('sidebar-collapsed', collapsed);
        btn.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
        try { localStorage.setItem(key, collapsed ? '1' : '0'); } catch (e) { /* private mode */ }
    };

    // Rail tooltips carry the hidden labels.
    aside.querySelectorAll('nav a').forEach((a) => {
        if (!a.getAttribute('title')) a.title = (a.textContent || '').trim();
    });

    let stored = null;
    try { stored = localStorage.getItem(key); } catch (e) { /* private mode */ }
    apply(stored === '1');

    btn.addEventListener('click', () => apply(!aside.classList.contains('sidebar-collapsed')));
});
