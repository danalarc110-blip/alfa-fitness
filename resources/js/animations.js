const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
const running = new Set();
let entranceObserver;

function entrance(element, delay = 0) {
    if (motion.matches || !element?.animate) return;
    const animation = element.animate(
        [{opacity: .86, transform: 'translateY(5px)'}, {opacity: 1, transform: 'translateY(0)'}],
        {duration: 180, delay, easing: 'cubic-bezier(.2,.7,.3,1)', fill: 'both'}
    );
    running.add(animation);
    animation.finished.catch(() => {}).finally(() => running.delete(animation));
}

motion.addEventListener('change', () => {
    if (!motion.matches) return;
    entranceObserver?.disconnect();
    running.forEach(animation => animation.cancel());
});

document.querySelectorAll('[data-animate="header"], [data-animate="fade-up"]').forEach((element, index) => entrance(element, Math.min(index * 20, 60)));

const cards = [...document.querySelectorAll('[data-animate="card"]')];
if (!motion.matches && cards.length) {
    if ('IntersectionObserver' in window) {
        entranceObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                entranceObserver.unobserve(entry.target);
                entrance(entry.target);
            });
        }, {rootMargin: '24px 0px', threshold: .04});
        cards.forEach(card => entranceObserver.observe(card));
    } else {
        cards.forEach(entrance);
    }
}
let modalState;
window.alphaAnimateModalOpen = selector => {
    const modal = document.querySelector(selector);
    if (!modal) return;
    modalState = {modal, trigger: document.activeElement, overflow: document.body.style.overflow};
    modal.classList.remove('hidden');
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    if (!modal.hasAttribute('aria-label') && !modal.hasAttribute('aria-labelledby')) {
        modal.setAttribute('aria-label', 'Ventana de información');
    }
    modal.tabIndex = -1;
    document.body.style.overflow = 'hidden';
    (modal.querySelector('button') || modal).focus();
    entrance(modal.querySelector('.modal-caja'));
};
window.alphaAnimateModalClose = (selector, callback) => {
    const modal = document.querySelector(selector);
    if (!modal) return;
    modal.classList.add('hidden');
    if (modalState?.modal === modal) {
        document.body.style.overflow = modalState.overflow;
        modalState.trigger?.focus();
        modalState = null;
    }
    callback?.();
};
document.addEventListener('keydown', event => {
    if (!modalState) return;
    if (event.key === 'Escape') { window.alphaAnimateModalClose('#' + modalState.modal.id); return; }
    if (event.key !== 'Tab') return;
    const items = [...modalState.modal.querySelectorAll('button:not(:disabled), a[href], input:not(:disabled)')].filter(el => el.getClientRects().length);
    const first = items[0], last = items.at(-1);
    if (!first) { event.preventDefault(); modalState.modal.focus(); }
    else if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
});
