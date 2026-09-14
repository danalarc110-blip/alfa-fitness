/* Shared by the early head script and settings. Storage never becomes CSS unchecked. */
(() => {
    const keys = ['primary', 'accent', 'background', 'surface', 'text'];
    const luminance = hex => {
        const rgb = hex.slice(1).match(/../g).map(c => {
            const v = parseInt(c, 16) / 255;
            return v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4;
        });
        return rgb[0] * .2126 + rgb[1] * .7152 + rgb[2] * .0722;
    };
    const contrast = (a, b) => (Math.max(luminance(a), luminance(b)) + .05) / (Math.min(luminance(a), luminance(b)) + .05);
    const valid = c => c && keys.every(k => /^#[\da-f]{6}$/i.test(c[k])) && contrast(c.text, c.background) >= 4.5 && contrast(c.text, c.surface) >= 4.5;
    const foreground = c => contrast(c, '#000000') > contrast(c, '#ffffff') ? '#000000' : '#ffffff';
    const paint = (element, c) => {
        const readable = color => contrast(color, c.background) >= 4.5 && contrast(color, c.surface) >= 4.5 ? color : c.text;
        const tokens = {...c, 'on-primary': foreground(c.primary), 'on-accent': foreground(c.accent),
            'danger': readable(luminance(c.background) > .179 ? '#a92323' : '#ffb4ab'),
            'success': readable(luminance(c.background) > .179 ? '#176b43' : '#8ce5b3'),
            'primary-text': contrast(c.primary, c.background) >= 4.5 && contrast(c.primary, c.surface) >= 4.5 ? c.primary : c.text,
            'accent-text': contrast(c.accent, c.background) >= 4.5 && contrast(c.accent, c.surface) >= 4.5 ? c.accent : c.text};
        Object.entries(tokens).forEach(([k, v]) => element.style.setProperty(`--alpha-${k}`, v));
        element.style.colorScheme = luminance(c.background) > .179 ? 'light' : 'dark';
    };
    const api = window.AlphaAppearance = {
        valid, contrast, paint,
        init(config) {
            this.config = config;
            let preference = config.preference;
            if (!config.authenticated) {
                try {
                    const stored = JSON.parse(localStorage.getItem('alphaAppearance'));
                    if (stored && ['light', 'dark', 'custom'].includes(stored.mode) && valid(stored.colors)) preference = stored;
                    else if (['light', 'dark'].includes(localStorage.getItem('alphaTema'))) preference = {mode: localStorage.getItem('alphaTema'), colors: config.palettes.light};
                } catch (_) { /* Storage is optional. */ }
            }
            this.apply(preference);
            if (config.authenticated) this.remember(preference);
        },
        apply(preference) {
            if (!['light', 'dark', 'custom'].includes(preference.mode) || !valid(preference.colors)) return;
            this.current = structuredClone(preference);
            const c = preference.mode === 'custom' ? preference.colors : this.config.palettes[preference.mode];
            paint(document.documentElement, c);
            document.documentElement.dataset.theme = preference.mode;
            document.documentElement.classList.toggle('light', luminance(c.background) > .179);
        },
        remember(preference) {
            try { localStorage.setItem('alphaAppearance', JSON.stringify(preference)); localStorage.removeItem('alphaTema'); } catch (_) {}
        },
        async save(preference) {
            if (this.config.authenticated) {
                const response = await fetch(this.config.url, {method: 'POST', headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.config.csrf}, body: JSON.stringify(preference)});
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(response.status === 419 || response.status === 401 ? 'Tu sesión expiró. Inicia sesión de nuevo.' : Object.values(data.errors || {}).flat()[0] || 'No se pudo guardar la apariencia. Intenta de nuevo.');
                preference = data.appearance;
            }
            this.apply(preference);
            this.remember(preference);
            window.dispatchEvent(new CustomEvent('alpha:appearance', {detail: preference}));
        },
    };
    window.alphaToggleTema = async () => {
        if (api.busy) return;
        api.busy = true;
        try { await api.save({...api.current, mode: api.current.mode === 'dark' ? 'light' : 'dark'}); }
        catch (error) { window.showAlphaToast?.(error.message, 'error'); }
        finally { api.busy = false; }
    };
})();
