const drawer = document.getElementById('alpha-mobile-drawer');
const backdrop = document.getElementById('alpha-mobile-backdrop');
const toggle = document.getElementById('alpha-hamburger-btn');

if (drawer && backdrop && toggle) {
    const desktop = window.matchMedia('(min-width: 768px)');
    const background = [document.querySelector('.alpha-workspace'), document.getElementById('alpha-mobile-header')].filter(Boolean);
    let session;

    const focusable = () => [...drawer.querySelectorAll('a[href], button:not(:disabled)')]
        .filter(element => element.tabIndex >= 0 && element.getClientRects().length);

    window.alphaOpenMobileMenu = () => {
        if (session || desktop.matches) return;
        session = {overflow: document.body.style.overflow, inert: background.map(element => element.inert)};
        drawer.inert = false;
        drawer.setAttribute('aria-hidden', 'false');
        drawer.setAttribute('role', 'dialog');
        drawer.setAttribute('aria-modal', 'true');
        drawer.classList.replace('-translate-x-full', 'translate-x-0');
        backdrop.classList.remove('opacity-0', 'pointer-events-none');
        backdrop.classList.add('opacity-100', 'pointer-events-auto');
        toggle.classList.add('is-active');
        toggle.setAttribute('aria-expanded', 'true');
        (focusable()[0] || drawer).focus({preventScroll: true});
        background.forEach(element => { element.inert = true; });
        document.body.style.overflow = 'hidden';
    };

    window.alphaCloseMobileMenu = (restoreFocus = true) => {
        if (!session) return;
        const previous = session;
        session = null;
        background.forEach((element, index) => { element.inert = previous.inert[index]; });
        drawer.classList.replace('translate-x-0', '-translate-x-full');
        backdrop.classList.remove('opacity-100', 'pointer-events-auto');
        backdrop.classList.add('opacity-0', 'pointer-events-none');
        toggle.classList.remove('is-active');
        toggle.setAttribute('aria-expanded', 'false');
        if (restoreFocus && !desktop.matches) toggle.focus({preventScroll: true});
        drawer.inert = true;
        drawer.setAttribute('aria-hidden', 'true');
        drawer.removeAttribute('role');
        drawer.removeAttribute('aria-modal');
        document.body.style.overflow = previous.overflow;
    };

    window.alphaToggleMobileMenu = () => session ? window.alphaCloseMobileMenu() : window.alphaOpenMobileMenu();
    document.addEventListener('keydown', event => {
        if (!session) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            window.alphaCloseMobileMenu();
        } else if (event.key === 'Tab') {
            const items = focusable();
            const first = items[0], last = items.at(-1);
            if (!first) { event.preventDefault(); drawer.focus(); }
            else if (event.shiftKey && (document.activeElement === first || !drawer.contains(document.activeElement))) {
                event.preventDefault(); last.focus();
            } else if (!event.shiftKey && (document.activeElement === last || !drawer.contains(document.activeElement))) {
                event.preventDefault(); first.focus();
            }
        }
    });
    desktop.addEventListener('change', event => { if (event.matches) window.alphaCloseMobileMenu(false); });
    window.addEventListener('pagehide', () => window.alphaCloseMobileMenu(false));
}
