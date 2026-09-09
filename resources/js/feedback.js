window.showAlphaToast = (message, type = 'success') => {
    let toast = document.getElementById('alpha-feedback');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'alpha-feedback';
        toast.className = 'alpha-feedback';
        document.body.append(toast);
    }
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.dataset.type = type;
    toast.textContent = message;
    toast.hidden = false;
    clearTimeout(window.alphaFeedbackTimer);
    window.alphaFeedbackTimer = setTimeout(() => { toast.hidden = true; }, type === 'error' ? 10000 : 4000);
};

document.addEventListener('submit', event => {
    const form = event.target;
    if (event.defaultPrevented || form.method.toLowerCase() === 'get') return;
    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) { event.preventDefault(); return; }
    if (form.dataset.submitting) { event.preventDefault(); return; }
    form.dataset.submitting = 'true';
    form.setAttribute('aria-busy', 'true');
    form.querySelectorAll('button[type="submit"], button:not([type])').forEach(button => {
        button.disabled = true;
        button.dataset.pending = 'true';
    });
});
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-submitting]').forEach(form => { delete form.dataset.submitting; form.removeAttribute('aria-busy'); });
    document.querySelectorAll('button[data-pending]').forEach(button => { button.disabled = false; delete button.dataset.pending; });
});
