import tinymce from 'tinymce';
import 'tinymce/models/dom';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/image';
import 'tinymce/plugins/table';
import 'tinymce/plugins/code';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/wordcount';
import 'tinymce/skins/ui/oxide-dark/skin.min.css';
import contentUi from 'tinymce/skins/ui/oxide-dark/content.min.css?inline';
import contentCss from 'tinymce/skins/content/dark/content.min.css?inline';

const element = document.querySelector('[data-editor]');
const status = document.querySelector('#editor-status');
tinymce.init({
    target: element, license_key: 'gpl', skin: false, content_css: false,
    content_style: `${contentUi}\n${contentCss}\nbody{font-family:Arial,sans-serif;font-size:17px;line-height:1.7;margin:24px}img{max-width:100%;height:auto}`,
    height: 560, menubar: false, promotion: false,
    plugins: 'link lists image table code fullscreen wordcount',
    toolbar: 'undo redo | blocks | bold italic | bullist numlist blockquote | link image table | code fullscreen',
    branding: true, image_caption: true, automatic_uploads: true,
    images_file_types: 'jpg,jpeg,png,webp',
    images_upload_handler: async blobInfo => {
        const data = new FormData(); data.append('file', blobInfo.blob(), blobInfo.filename());
        const response = await fetch(element.dataset.uploadUrl, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' }, body: data
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.errors?.file?.[0] || result.message || 'Image upload failed. Please try again.');
        return result.location;
    },
    setup(editor) {
        editor.on('init', () => status.textContent = 'TinyMCE · Images are resized automatically. Save your draft to keep changes.');
    }
}).catch(() => status.textContent = 'The editor could not load. Reload this page before editing rich text.');
const form = document.querySelector('#post-form');
form.addEventListener('submit', async event => {
    const editor = tinymce.get(element.id);
    if (!editor) return;
    event.preventDefault();
    const action = event.submitter?.value || 'draft';
    const buttons = form.querySelectorAll('button[type="submit"]');
    buttons.forEach(button => button.disabled = true);
    try {
        const uploads = await editor.uploadImages();
        if (uploads.some(upload => !upload.status)) throw new Error('An image has not finished uploading. Please retry.');
        editor.save();
        const input = document.createElement('input'); input.type = 'hidden'; input.name = 'action'; input.value = action; form.append(input);
        HTMLFormElement.prototype.submit.call(form);
    } catch (error) {
        status.textContent = error.message || 'Could not save images. Please try again.';
        buttons.forEach(button => button.disabled = false);
    }
});
