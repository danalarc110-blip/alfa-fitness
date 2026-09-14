const form = document.getElementById('appearance-form');
if (form) {
    const api = window.AlphaAppearance;
    const colors = [...form.querySelectorAll('input[type="color"]')];
    const message = document.getElementById('appearance-message');
    const save = form.querySelector('[type="submit"]');
    const preference = () => ({mode: form.elements.mode.value, colors: Object.fromEntries(colors.map(i => [i.id.replace('color-', ''), i.value]))});
    const preview = () => {
        const pref = preference();
        colors.forEach(input => { input.nextElementSibling.value = input.value.toUpperCase(); });
        const valid = api.valid(pref.colors);
        if (valid || pref.mode !== 'custom') api.paint(document.getElementById('appearance-preview'), pref.mode === 'custom' ? pref.colors : api.config.palettes[pref.mode]);
        save.disabled = !valid;
        message.textContent = valid ? 'Los cambios se muestran en la vista previa hasta que los guardes.' : 'Contraste insuficiente. Ajusta el texto, el fondo o los paneles (mínimo 4.5:1).';
        message.setAttribute('role', valid ? 'status' : 'alert');
        document.getElementById('appearance-colors').classList.toggle('appearance-inactive', pref.mode !== 'custom');
    };
    form.addEventListener('input', preview);
    document.getElementById('appearance-reset').addEventListener('click', () => {
        form.elements.mode.value = 'light';
        colors.forEach(input => { input.value = api.config.palettes.light[input.id.replace('color-', '')]; });
        preview();
        message.textContent = 'Paleta predeterminada restaurada. Pulsa Guardar apariencia para conservarla.';
    });
    window.addEventListener('alpha:appearance', event => {
        form.elements.mode.value = event.detail.mode;
        colors.forEach(input => { input.value = event.detail.colors[input.id.replace('color-', '')]; });
        preview();
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (api.busy || !api.valid(preference().colors)) return;
        api.busy = true;
        save.disabled = true;
        form.setAttribute('aria-busy', 'true');
        try { await api.save(preference()); message.textContent = 'Apariencia guardada en tu cuenta.'; }
        catch (error) { message.textContent = error.message; message.setAttribute('role', 'alert'); }
        finally { api.busy = false; form.removeAttribute('aria-busy'); save.disabled = !api.valid(preference().colors); }
    });
    preview();
}
