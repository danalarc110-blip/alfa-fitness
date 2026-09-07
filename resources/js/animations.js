/**
 * Alpha Fitness — Animation Engine (Optimized & High Performance)
 * Rápido, fluido, sin saltos visuales ni retrasos en la carga de módulos.
 */
import gsap from 'gsap';

/* =========================================================
   1. INICIALIZACIÓN GLOBAL
   ========================================================= */
export function initAnimations() {
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
export function showAlphaToast(message, type = 'success') {
    let container = document.getElementById('alpha-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'alpha-toast-container';
        container.className = 'fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const isSuccess = type === 'success';
    toast.className = `pointer-events-auto flex items-center gap-3 px-5 py-3.5 rounded-2xl border text-sm font-semibold shadow-2xl backdrop-blur-xl transition-all ${
        isSuccess 
            ? 'bg-[#141414]/95 border-yellow-400/40 text-yellow-400 shadow-yellow-400/10' 
            : 'bg-[#141414]/95 border-red-500/40 text-red-400 shadow-red-500/10'
    }`;

    toast.innerHTML = `
        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            ${isSuccess 
                ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>' 
                : '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'}
        </svg>
        <span>${message}</span>
    `;

    container.appendChild(toast);

    gsap.fromTo(toast, {
        opacity: 0,
        x: 40,
        scale: 0.95
    }, {
        opacity: 1,
        x: 0,
        scale: 1,
        duration: 0.25,
        ease: 'power2.out',
        onComplete: () => {
            gsap.to(toast, {
                opacity: 0,
                x: 20,
                scale: 0.95,
                delay: 2.2,
                duration: 0.25,
                ease: 'power2.in',
                onComplete: () => toast.remove()
            });
        }
    });
}

window.showAlphaToast = showAlphaToast;
window.initAnimations = initAnimations;
