import './bootstrap';
import { initAnimations } from './animations';

// Global Theme Switcher
export function alphaToggleTema() {
    const isLight = document.documentElement.classList.toggle('light');
    localStorage.setItem('alphaTema', isLight ? 'light' : 'dark');
}

window.alphaToggleTema = alphaToggleTema;

// Immediate theme application on page load
if (localStorage.getItem('alphaTema') === 'light') {
    document.documentElement.classList.add('light');
}

document.addEventListener('DOMContentLoaded', () => {
    initAnimations();
});
