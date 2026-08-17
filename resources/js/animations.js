import gsap from 'gsap';

/**
 * Alpha Fitness - Animation & Micro-Interactions Engine
 * Powered by GSAP & Modern Web APIs
 */
export function initAnimations() {
    // 1. Page Entrance Animations (Staggered elements)
    animatePageEntrance();

    // 2. Interactive Tilt / Hover Glow on Cards
    initCardHoverEffects();

    // 3. Button Micro-Interactions & Ripples
    initButtonEffects();

    // 4. Modal / Dialog Smooth Entrance
    initModalAnimations();

    // 5. Dynamic Tab & Panel Transitions
    initTabTransitions();

    // 6. Floating / Breathing Accents
    initAmbientEffects();
}

/**
 * Animate elements with [data-animate] or standard page layout elements
 */
export function animatePageEntrance() {
    // Animate header and main elements if present
    const headers = document.querySelectorAll('header, [data-animate="header"]');
    if (headers.length > 0) {
        gsap.from(headers, {
            y: -20,
            opacity: 0,
            duration: 0.6,
            ease: 'power3.out',
            stagger: 0.1
        });
    }

    // Animate cards and content blocks
    const cards = document.querySelectorAll('[data-animate="card"], .alpha-card');
    if (cards.length > 0) {
        gsap.from(cards, {
            y: 25,
            opacity: 0,
            duration: 0.65,
            ease: 'power3.out',
            stagger: 0.08,
            clearProps: 'transform,opacity'
        });
    }

    // Animate generic fade-up elements
    const fadeUps = document.querySelectorAll('[data-animate="fade-up"]');
    if (fadeUps.length > 0) {
        gsap.from(fadeUps, {
            y: 20,
            opacity: 0,
            duration: 0.5,
            ease: 'power2.out',
            stagger: 0.06,
            clearProps: 'transform,opacity'
        });
    }

    // Animate sidebar links stagger
    const sidebarLinks = document.querySelectorAll('aside nav a');
    if (sidebarLinks.length > 0) {
        gsap.from(sidebarLinks, {
            x: -15,
            opacity: 0,
            duration: 0.45,
            ease: 'power2.out',
            stagger: 0.04,
            clearProps: 'transform,opacity'
        });
    }
}

/**
 * Interactive hover glow & scale on cards
 */
export function initCardHoverEffects() {
    const interactiveCards = document.querySelectorAll('[data-tilt], .alpha-card-interactive');
    
    interactiveCards.forEach((card) => {
        card.addEventListener('mouseenter', () => {
            gsap.to(card, {
                scale: 1.018,
                y: -3,
                duration: 0.28,
                ease: 'power2.out'
            });
        });

        card.addEventListener('mouseleave', () => {
            gsap.to(card, {
                scale: 1,
                y: 0,
                duration: 0.35,
                ease: 'power2.out'
            });
        });
    });
}

/**
 * Micro-interactions for buttons (elastic press & ripple)
 */
export function initButtonEffects() {
    const buttons = document.querySelectorAll('button:not([disabled]), .btn-anim, a[data-btn]');
    
    buttons.forEach((btn) => {
        btn.addEventListener('mousedown', () => {
            gsap.to(btn, { scale: 0.96, duration: 0.1, ease: 'power1.inOut' });
        });
        
        btn.addEventListener('mouseup', () => {
            gsap.to(btn, { scale: 1, duration: 0.2, ease: 'back.out(2)' });
        });

        btn.addEventListener('mouseleave', () => {
            gsap.to(btn, { scale: 1, duration: 0.2, ease: 'power1.out' });
        });
    });
}

/**
 * Smooth modal backdrop and dialog scaling
 */
export function initModalAnimations() {
    window.alphaAnimateModalOpen = function(modalSelector) {
        const modal = document.querySelector(modalSelector);
        if (!modal) return;
        
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        
        const backdrop = modal.querySelector('.modal-backdrop') || modal;
        const dialog = modal.querySelector('.modal-caja') || modal.querySelector('.modal-dialog') || modal.children[0];

        if (backdrop) {
            gsap.fromTo(backdrop, { opacity: 0 }, { opacity: 1, duration: 0.25, ease: 'power2.out' });
        }
        if (dialog) {
            gsap.fromTo(dialog, {
                scale: 0.92,
                y: 20,
                opacity: 0
            }, {
                scale: 1,
                y: 0,
                opacity: 1,
                duration: 0.35,
                ease: 'back.out(1.4)'
            });
        }
    };

    window.alphaAnimateModalClose = function(modalSelector, callback) {
        const modal = document.querySelector(modalSelector);
        if (!modal) return;

        const dialog = modal.querySelector('.modal-caja') || modal.querySelector('.modal-dialog') || modal.children[0];
        
        if (dialog) {
            gsap.to(dialog, {
                scale: 0.94,
                y: 15,
                opacity: 0,
                duration: 0.2,
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

/**
 * Smooth transition for tab changes
 */
export function initTabTransitions() {
    window.alphaAnimateTabSwitch = function(outgoingEl, incomingEl) {
        if (outgoingEl) {
            gsap.to(outgoingEl, {
                opacity: 0,
                y: 10,
                duration: 0.18,
                ease: 'power2.in',
                onComplete: () => {
                    outgoingEl.classList.add('hidden');
                    outgoingEl.classList.remove('active');
                    if (incomingEl) {
                        incomingEl.classList.remove('hidden');
                        incomingEl.classList.add('active');
                        gsap.fromTo(incomingEl, {
                            opacity: 0,
                            y: 12
                        }, {
                            opacity: 1,
                            y: 0,
                            duration: 0.28,
                            ease: 'power2.out'
                        });
                    }
                }
            });
        } else if (incomingEl) {
            incomingEl.classList.remove('hidden');
            incomingEl.classList.add('active');
            gsap.fromTo(incomingEl, {
                opacity: 0,
                y: 12
            }, {
                opacity: 1,
                y: 0,
                duration: 0.28,
                ease: 'power2.out'
            });
        }
    };
}

/**
 * Subtle pulse and glow on badges and decorative icons
 */
export function initAmbientEffects() {
    const pulseIcons = document.querySelectorAll('.anim-icono, [data-glow]');
    if (pulseIcons.length > 0) {
        gsap.to(pulseIcons, {
            filter: 'drop-shadow(0 0 12px rgba(250, 204, 21, 0.45))',
            repeat: -1,
            yoyo: true,
            duration: 2.2,
            ease: 'sine.inOut'
        });
    }
}

// Quick visual toast / feedback indicator
export function showAlphaToast(message, type = 'success') {
    let toastContainer = document.getElementById('alpha-toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'alpha-toast-container';
        toastContainer.className = 'fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    const isSuccess = type === 'success';
    toast.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl border text-sm font-medium shadow-2xl backdrop-blur-md transition-all ${
        isSuccess 
            ? 'bg-[#141414]/95 border-yellow-400/40 text-yellow-400 shadow-yellow-400/10' 
            : 'bg-[#141414]/95 border-red-500/40 text-red-400 shadow-red-500/10'
    }`;
    
    toast.innerHTML = `
        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            ${isSuccess 
                ? '<polyline points="20 6 9 17 4 12"></polyline>' 
                : '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>'}
        </svg>
        <span>${message}</span>
    `;

    toastContainer.appendChild(toast);

    gsap.fromTo(toast, {
        opacity: 0,
        y: 20,
        scale: 0.95
    }, {
        opacity: 1,
        y: 0,
        scale: 1,
        duration: 0.35,
        ease: 'back.out(1.5)',
        onComplete: () => {
            gsap.to(toast, {
                opacity: 0,
                y: -15,
                scale: 0.9,
                delay: 2.8,
                duration: 0.3,
                ease: 'power2.in',
                onComplete: () => toast.remove()
            });
        }
    });
}

window.showAlphaToast = showAlphaToast;
window.initAnimations = initAnimations;
