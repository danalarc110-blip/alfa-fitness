const previewUrls = new Map();

function clearPreview(input, image) {
    const previous = previewUrls.get(input);
    if (previous) URL.revokeObjectURL(previous);
    previewUrls.delete(input);
    image.removeAttribute('src');
    image.classList.add('hidden');
}

document.addEventListener('change', event => {
    const input = event.target.closest?.('input[type="file"][data-image-preview]');
    if (!input) return;

    const image = document.getElementById(input.dataset.imagePreview);
    if (!image) return;
    const file = input.files?.[0];
    if (!file || !file.type.startsWith('image/')) {
        clearPreview(input, image);
        return;
    }

    clearPreview(input, image);
    const url = URL.createObjectURL(file);
    previewUrls.set(input, url);
    image.src = url;
    image.classList.remove('hidden');
});

window.addEventListener('pagehide', () => {
    previewUrls.forEach(url => URL.revokeObjectURL(url));
    previewUrls.clear();
});
