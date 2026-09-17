
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


document.querySelectorAll('[data-post-order]').forEach(list => {
    const selectedRows = () => Array.from(list.children).filter(row => row.querySelector('input').checked);
    const refresh = () => {
        const selected = selectedRows();
        Array.from(list.children).forEach(row => {
            const index = selected.indexOf(row);
            row.querySelector('.post-order-actions').hidden = index === -1;
            row.querySelector('[data-move="up"]').disabled = index <= 0;
            row.querySelector('[data-move="down"]').disabled = index === -1 || index === selected.length - 1;
        });
    };
    list.addEventListener('change', event => {
        if (!event.target.matches('input[type="checkbox"]')) return;
        const row = event.target.closest('[data-post-option]');
        if (event.target.checked) {
            const last = selectedRows().filter(item => item !== row).at(-1);
            if (last) last.after(row);
            else list.prepend(row);
        } else {
            list.append(row);
        }
        refresh();
    });
    list.addEventListener('click', event => {
        const button = event.target.closest('[data-move]');
        if (!button) return;
        const row = button.closest('[data-post-option]');
        const selected = selectedRows();
        const index = selected.indexOf(row);
        const target = selected[index + (button.dataset.move === 'up' ? -1 : 1)];
        if (!target) return;
        if (button.dataset.move === 'up') target.before(row);
        else target.after(row);
        refresh();
        if (button.disabled) {
            row.querySelector(`[data-move="${button.dataset.move === 'up' ? 'down' : 'up'}"]`).focus();
        } else {
            button.focus();
        }
    });
    refresh();
});
