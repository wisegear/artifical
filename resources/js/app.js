
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
