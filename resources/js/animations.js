const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
const reducedMotion = () => motion.matches || window.AlphaAppearance?.current?.motion === 'reduced';
const running = new Set();
const modalSessions = new WeakMap();
const easing = 'cubic-bezier(.16,1,.3,1)';
let modalState;

function animate(element, frames, duration, owner, delay = 0) {
    if (reducedMotion() || !element?.animate) return;
    const animation = element.animate(frames, {duration, delay, easing, fill: 'none'});
    running.add(animation);
    owner?.animations.add(animation);
    animation.finished.catch(() => {}).finally(() => {
        running.delete(animation);
        owner?.animations.delete(animation);
        animation.cancel();
    });
    return animation;
}

const stopReducedMotion = () => {
    if (!reducedMotion()) return;
    running.forEach(animation => animation.cancel());
};
motion.addEventListener('change', stopReducedMotion);
window.addEventListener('alpha:appearance', stopReducedMotion);

function stopModalMotion(state) {
    state.animations.forEach(animation => animation.cancel());
    state.animations.clear();
}

function finishModalClose(state, restoreFocus = true) {
    // A reopened modal owns a new session; an old exit must not hide it.
    if (modalSessions.get(state.modal) !== state) return;
    modalSessions.delete(state.modal);
    state.modal.classList.add('hidden');
    stopModalMotion(state);
    if (modalState === state) {
        document.body.style.overflow = state.overflow;
        modalState = null;
        if (restoreFocus && state.trigger?.isConnected) state.trigger.focus({preventScroll: true});
    }
    state.callbacks.forEach(callback => callback());
    state.callbacks.clear();
}

window.alphaAnimateModalOpen = selector => {
    const modal = document.querySelector(selector);
    if (!modal) return;
    const previous = modalState;
    const existing = modalSessions.get(modal);
    if (existing && !existing.closing) return;
    const panel = modal.querySelector('.modal-caja') || modal.firstElementChild;
    const currentOpacity = existing ? getComputedStyle(modal).opacity : 0;
    const currentTransform = existing && panel ? getComputedStyle(panel).transform : 'translateY(8px) scale(.985)';
    if (existing) stopModalMotion(existing);
    if (previous && previous.modal !== modal) finishModalClose(previous, false);
    const state = {
        modal, panel,
        trigger: previous?.trigger || document.activeElement,
        overflow: previous?.overflow ?? document.body.style.overflow,
        animations: new Set(), callbacks: new Set(), closing: false,
    };
    modalSessions.set(modal, state);
    modalState = state;
    modal.classList.remove('hidden');
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    if (!modal.hasAttribute('aria-label') && !modal.hasAttribute('aria-labelledby')) {
        modal.setAttribute('aria-label', 'Ventana de información');
    }
    modal.tabIndex = -1;
    document.body.style.overflow = 'hidden';
    (modal.querySelector('button:not(:disabled), [href], input:not(:disabled)') || modal).focus({preventScroll: true});
    animate(modal, [{opacity: currentOpacity}, {opacity: 1}], 160, state);
    animate(panel, [{transform: currentTransform}, {transform: 'none'}], 160, state);
};

window.alphaAnimateModalClose = (selector, callback) => {
    const modal = document.querySelector(selector);
    if (!modal) return;
    const state = modalSessions.get(modal);
    if (!state) {
        modal.classList.add('hidden');
        callback?.();
        return;
    }
    if (typeof callback === 'function') state.callbacks.add(callback);
    if (state.closing) return state.closed;
    state.closing = true;
    if (reducedMotion() || !modal.animate) {
        finishModalClose(state);
        return;
    }
    const opacity = getComputedStyle(modal).opacity;
    const transform = state.panel ? getComputedStyle(state.panel).transform : 'none';
    stopModalMotion(state);
    const animations = [
        animate(modal, [{opacity}, {opacity: 0}], 100, state),
        animate(state.panel, [{transform}, {transform: 'translateY(4px) scale(.99)'}], 100, state),
    ].filter(Boolean);
    state.closed = Promise.allSettled(animations.map(animation => animation.finished))
        .then(() => finishModalClose(state));
    return state.closed;
};
document.addEventListener('keydown', event => {
    if (!modalState) return;
    if (event.key === 'Escape') { window.alphaAnimateModalClose('#' + modalState.modal.id); return; }
    if (event.key !== 'Tab') return;
    const items = [...modalState.modal.querySelectorAll('button:not(:disabled), a[href], input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])')].filter(el => el.tabIndex >= 0 && el.getClientRects().length);
    const first = items[0], last = items.at(-1);
    if (!first) { event.preventDefault(); modalState.modal.focus(); }
    else if (event.shiftKey && (document.activeElement === first || !modalState.modal.contains(document.activeElement))) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && (document.activeElement === last || !modalState.modal.contains(document.activeElement))) { event.preventDefault(); first.focus(); }
});
