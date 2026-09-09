/**
 * Alpha Fitness — Animation Engine (Optimized & High Performance)
 * Rápido, fluido, sin saltos visuales ni retrasos en la carga de módulos.
 */
import gsap from 'gsap';

/* =========================================================
   1. INICIALIZACIÓN GLOBAL
   ========================================================= */
export function initAnimations() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    initSmoothPageEntrance();
    initCardHoverEffects();
    initButtonEffects();
    initModalAnimations();
    initTabTransitions();
    initRippleEffect();
    initCounterAnimations();
}

/* =========================================================
   2. ENTRADA DE PÁGINA ULTRA FLUIDA (SIN PARPADEO NI RETRASO)
   ========================================================= */
export function initSmoothPageEntrance() {
    // Micro-animación suave y no intrusiva de 0.18s
    const elements = document.querySelectorAll('[data-animate="fade-up"], [data-animate="header"]');
    if (elements.length) {
        gsap.fromTo(elements, 
            { opacity: 0.85, y: 6 },
            { 
                opacity: 1, 
                y: 0, 
                duration: 0.18, 
                ease: 'power1.out',
                clearProps: 'transform,opacity'
            }
        );
    }
}

/* =========================================================
   3. HOVER CARDS (LIVIANO Y OPTIMIZADO)
   ========================================================= */
export function initCardHoverEffects() {
    const cards = document.querySelectorAll('[data-tilt], .alpha-card-interactive');

    cards.forEach((card) => {
        card.addEventListener('mouseenter', () => {
            gsap.to(card, {
                y: -3,
                duration: 0.2,
                ease: 'power1.out'
            });
        });

        card.addEventListener('mouseleave', () => {
            gsap.to(card, {
                y: 0,
                duration: 0.25,
                ease: 'power1.out'
            });
        });
    });
}

/* =========================================================
   4. BOTONES Y RIPPLE SNAPPY
   ========================================================= */
export function initButtonEffects() {
    const buttons = document.querySelectorAll('button:not([disabled]), .btn-anim, .alpha-btn-primary, .alpha-btn-secondary');

    buttons.forEach((btn) => {
        btn.addEventListener('mousedown', () => {
            gsap.to(btn, { scale: 0.96, duration: 0.08, ease: 'power1.inOut' });
        });
        btn.addEventListener('mouseup', () => {
            gsap.to(btn, { scale: 1, duration: 0.15, ease: 'power1.out' });
        });
        btn.addEventListener('mouseleave', () => {
            gsap.to(btn, { scale: 1, duration: 0.12, ease: 'power1.out' });
        });
    });
}

export function initRippleEffect() {
    document.querySelectorAll('.alpha-btn-primary, .alpha-btn-secondary, [data-ripple]').forEach(btn => {
        btn.style.position = 'relative';
        btn.style.overflow = 'hidden';
        btn.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const ripple = document.createElement('span');
            const size = Math.max(rect.width, rect.height);
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(250, 204, 21, 0.3);
                width: ${size}px;
                height: ${size}px;
                left: ${e.clientX - rect.left - size/2}px;
                top: ${e.clientY - rect.top - size/2}px;
                pointer-events: none;
                transform: scale(0);
                opacity: 1;
            `;
            this.appendChild(ripple);
            gsap.to(ripple, {
                scale: 2.2,
                opacity: 0,
                duration: 0.4,
                ease: 'power2.out',
                onComplete: () => ripple.remove()
            });
        });
    });
}

/* =========================================================
   5. MODALES (ÁGILES Y LIMPIOS)
   ========================================================= */
export function initModalAnimations() {
    window.alphaAnimateModalOpen = function(modalSelector) {
        const modal = document.querySelector(modalSelector);
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.style.display = 'flex';

        const dialog = modal.querySelector('.modal-caja') || modal.querySelector('.modal-dialog') || modal.children[0];
        if (dialog) {
            gsap.fromTo(dialog, {
                scale: 0.94,
                opacity: 0
            }, {
                scale: 1,
                opacity: 1,
                duration: 0.2,
                ease: 'power2.out'
            });
        }
    };

    window.alphaAnimateModalClose = function(modalSelector, callback) {
        const modal = document.querySelector(modalSelector);
        if (!modal) return;
        const dialog = modal.querySelector('.modal-caja') || modal.querySelector('.modal-dialog') || modal.children[0];
        if (dialog) {
            gsap.to(dialog, {
                scale: 0.96,
                opacity: 0,
                duration: 0.15,
                ease: 'power2.in',
                onComplete: () => {
                    modal.classList.add('hidden');
                    modal.style.display = '';
                    if (typeof callback === 'function') callback();
                }
            });
        } else {
            modal.classList.add('hidden');
            modal.style.display = '';
            if (typeof callback === 'function') callback();
        }
    };
}

/* =========================================================
   6. TRANSICIÓN DE TABS
   ========================================================= */
export function initTabTransitions() {
    window.alphaAnimateTabSwitch = function(outgoingEl, incomingEl) {
        if (outgoingEl) {
            outgoingEl.classList.add('hidden');
            outgoingEl.classList.remove('active');
        }
        if (incomingEl) {
            incomingEl.classList.remove('hidden');
            incomingEl.classList.add('active');
            gsap.fromTo(incomingEl, { opacity: 0.7 }, { opacity: 1, duration: 0.15, ease: 'power1.out' });
        }
    };
}

/* =========================================================
   7. CONTADORES ANIMADOS RÁPIDOS
   ========================================================= */
export function initCounterAnimations() {
    document.querySelectorAll('[data-counter]').forEach(el => {
        const target = parseInt(el.dataset.counter, 10);
        if (isNaN(target)) return;
        const obj = { val: 0 };
        gsap.to(obj, {
            val: target,
            duration: 0.8,
            ease: 'power1.out',
            onUpdate: () => {
                el.textContent = Math.round(obj.val).toLocaleString();
            }
        });
    });
}

/* =========================================================
   8. TOAST NOTIFICACIONES
   ========================================================= */
// Feedback is initialized separately so it also works with reduced motion.
window.initAnimations = initAnimations;
