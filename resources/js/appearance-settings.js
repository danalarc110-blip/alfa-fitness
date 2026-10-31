const form = document.getElementById('appearance-form');
if (form) {
    const api = window.AlphaAppearance;
    const colors = [...form.querySelectorAll('input[type="color"]')];
    const message = document.getElementById('appearance-message');
    const save = form.querySelector('[type="submit"]');
    const preference = () => ({design: form.elements.design.value, mode: form.elements.mode.value, motion: form.elements.motion.value, colors: Object.fromEntries(colors.map(i => [i.id.replace('color-', ''), i.value]))});
    const canSave = () => preference().mode !== 'custom' || api.valid(preference().colors);
    const preview = () => {
        const pref = preference();
        colors.forEach(input => { input.nextElementSibling.value = input.value.toUpperCase(); });
        const valid = api.valid(pref.colors);
        const sample = document.getElementById('appearance-preview');
        sample.dataset.design = pref.design;
        sample.dataset.theme = pref.mode;
        if (valid || pref.mode !== 'custom') api.paint(sample, api.palette(pref));
        const readable = valid || pref.mode !== 'custom';
        save.disabled = api.busy || !readable;
        message.textContent = readable ? 'Los cambios se muestran en la vista previa hasta que los guardes.' : 'Contraste insuficiente. Ajusta el texto, el fondo o los paneles (mínimo 4.5:1).';
        message.setAttribute('role', readable ? 'status' : 'alert');
        document.getElementById('appearance-colors').classList.toggle('appearance-inactive', pref.mode !== 'custom');
    };
    form.addEventListener('input', preview);
    document.getElementById('appearance-reset').addEventListener('click', () => {
        form.elements.design.value = 'elegant';
        form.elements.mode.value = 'light';
        form.elements.motion.value = 'system';
        colors.forEach(input => { input.value = api.config.palettes.light[input.id.replace('color-', '')]; });
        preview();
        message.textContent = 'Paleta predeterminada restaurada. Pulsa Guardar apariencia para conservarla.';
    });
    window.addEventListener('alpha:appearance', event => {
        form.elements.design.value = event.detail.design;
        form.elements.mode.value = event.detail.mode;
        form.elements.motion.value = event.detail.motion;
        colors.forEach(input => { input.value = event.detail.colors[input.id.replace('color-', '')]; });
        preview();
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (api.busy || !canSave()) return;
        const submitted = preference();
        const controls = [...form.querySelectorAll('input, button, select')].map(control => [control, control.disabled]);
        api.busy = true;
        controls.forEach(([control]) => { control.disabled = true; });
        form.setAttribute('aria-busy', 'true');
        message.setAttribute('role', 'status');
        message.textContent = 'Guardando apariencia…';
        try { await api.save(submitted); message.textContent = 'Apariencia guardada en tu cuenta.'; }
        catch (error) { message.textContent = error.message; message.setAttribute('role', 'alert'); }
        finally {
            api.busy = false;
            controls.forEach(([control, disabled]) => { control.disabled = disabled; });
            form.removeAttribute('aria-busy');
            save.disabled = !canSave();
        }
    });
    preview();
}
