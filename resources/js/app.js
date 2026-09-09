import './bootstrap';
import './feedback';

// Global Theme Switcher
export function alphaToggleTema() {
    const isLight = document.documentElement.classList.toggle('light');
    try { localStorage.setItem('alphaTema', isLight ? 'light' : 'dark'); } catch (_) {}
}

window.alphaToggleTema = alphaToggleTema;

// Immediate theme application on page load
try { if (localStorage.getItem('alphaTema') === 'light') {
    document.documentElement.classList.add('light');
} } catch (_) {}

document.addEventListener('DOMContentLoaded', () => {
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        import('./animations').then(({ initAnimations }) => initAnimations()).catch(() => {});
    }
});
