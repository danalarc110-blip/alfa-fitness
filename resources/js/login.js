const tabs = ['usuarios', 'clientes'].map(id => document.getElementById('tab-btn-' + id));
if (tabs.every(Boolean)) {
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const reducedMotion = () => motion.matches || window.AlphaAppearance?.current?.motion === 'reduced';
    const transitions = new Map();
    const reveal = (panel, direction = 1) => {
        transitions.get(panel)?.cancel();
        if (reducedMotion() || !panel?.animate) return;
        const animation = panel.animate(
            [{opacity: .75, transform: `translateX(${direction * 6}px)`}, {opacity: 1, transform: 'translateX(0)'}],
            {duration: 160, easing: 'cubic-bezier(.16, 1, .3, 1)'}
        );
        transitions.set(panel, animation);
        animation.finished.catch(() => {}).finally(() => {
            if (transitions.get(panel) === animation) transitions.delete(panel);
            animation.cancel();
        });
    };
    const stopTransitions = () => {
        transitions.forEach(animation => animation.cancel());
        transitions.clear();
    };
    motion.addEventListener('change', () => { if (reducedMotion()) stopTransitions(); });
    window.addEventListener('alpha:appearance', () => { if (reducedMotion()) stopTransitions(); });
    window.addEventListener('pagehide', stopTransitions);
    const activate = index => {
        if (tabs[index].getAttribute('aria-selected') === 'true') return;
        stopTransitions();
        tabs.forEach((tab, i) => {
            tab.setAttribute('aria-selected', String(i === index));
            tab.tabIndex = i === index ? 0 : -1;
            document.getElementById(tab.getAttribute('aria-controls')).classList.toggle('hidden', i !== index);
        });
        document.getElementById('portal-subtitulo').textContent = index === 0
            ? 'Acceso para trabajadores, entrenadores y administración.'
            : 'Acceso a planes y entrenamientos para miembros.';
        reveal(document.getElementById(tabs[index].getAttribute('aria-controls')), index === 1 ? 1 : -1);
    };
    tabs.forEach((tab, i) => {
        tab.addEventListener('click', () => activate(i));
        tab.addEventListener('keydown', event => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const target = event.key === 'Home' ? 0 : event.key === 'End' ? 1 : 1 - i;
            activate(target); tabs[target].focus();
        });
    });
    const switchRegistration = register => {
        stopTransitions();
        document.getElementById('form-cliente-login').classList.toggle('hidden', register);
        document.getElementById('form-cliente-registro').classList.toggle('hidden', !register);
        reveal(document.getElementById(register ? 'form-cliente-registro' : 'form-cliente-login'), register ? 1 : -1);
        document.querySelector(register ? '#form-cliente-registro input[name=nombre]' : '#form-cliente-login input[name=correo]').focus();
    };
    document.getElementById('btnMostrarRegistro').addEventListener('click', () => switchRegistration(true));
    document.getElementById('btnMostrarLogin').addEventListener('click', () => switchRegistration(false));
}
document.querySelectorAll('[data-password]').forEach(button => button.addEventListener('click', () => {
    const input = document.getElementById(button.dataset.password);
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    button.textContent = show ? 'Ocultar' : 'Ver';
    button.setAttribute('aria-pressed', String(show));
    button.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
}));
