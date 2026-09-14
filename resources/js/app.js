
const menuToggle = document.querySelector('.menu-toggle');
if (menuToggle) {
    const header = menuToggle.closest('.site-header');
    const navigation = document.querySelector('#main-navigation');
    const mobileMenu = window.matchMedia('(max-width: 700px)');
    const setMenuOpen = open => {
        menuToggle.setAttribute('aria-expanded', String(open));
        menuToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    };

    header.dataset.menuReady = '';
    menuToggle.hidden = false;
    menuToggle.addEventListener('click', () => {
        setMenuOpen(menuToggle.getAttribute('aria-expanded') !== 'true');
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
            setMenuOpen(false);
            menuToggle.focus();
        }
    });
    document.addEventListener('click', event => {
        if (!header.contains(event.target)) setMenuOpen(false);
    });
    header.addEventListener('focusout', event => {
        if (!header.contains(event.relatedTarget)) setMenuOpen(false);
    });
    navigation.addEventListener('click', event => {
        if (event.target.closest('a')) setMenuOpen(false);
    });
    mobileMenu.addEventListener('change', () => setMenuOpen(false));
}

document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => { if (!confirm(form.dataset.confirm)) event.preventDefault(); });
});
const summary = document.querySelector('#seo_summary');
if (summary) {
    const count = () => document.querySelector('#summary-count').textContent = Array.from(summary.value).length;
    summary.addEventListener('input', count); count();
}
const imageInput = document.querySelector('#image');
if (imageInput) {
    let previewUrl;
    imageInput.addEventListener('change', () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        const preview = document.querySelector('#image-preview');
        preview.hidden = !imageInput.files.length;
        if (imageInput.files.length) preview.src = previewUrl = URL.createObjectURL(imageInput.files[0]);
    });
}
if (document.querySelector('[data-editor]')) import('./editor');
