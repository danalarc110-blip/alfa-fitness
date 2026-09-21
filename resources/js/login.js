const tabs = ['usuarios', 'clientes'].map(id => document.getElementById('tab-btn-' + id));
if (tabs.every(Boolean)) {
    const activate = index => {
        tabs.forEach((tab, i) => {
            tab.setAttribute('aria-selected', String(i === index));
            tab.tabIndex = i === index ? 0 : -1;
            document.getElementById(tab.getAttribute('aria-controls')).classList.toggle('hidden', i !== index);
        });
        document.getElementById('portal-subtitulo').textContent = index === 0
            ? 'Acceso para trabajadores, entrenadores y administración.'
            : 'Acceso a planes y entrenamientos para miembros.';
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
        document.getElementById('form-cliente-login').classList.toggle('hidden', register);
        document.getElementById('form-cliente-registro').classList.toggle('hidden', !register);
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
