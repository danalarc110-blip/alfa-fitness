<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de privacidad · Alpha Fitness</title>
    @include('partials.appearance')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="alpha-app font-sans min-h-screen">
    <a href="#contenido" class="alpha-skip">Saltar a la política</a>
    <main class="alpha-privacy-page" id="contenido">
        <nav class="mb-8 flex flex-wrap gap-5" aria-label="Navegación">
            <a href="{{ route('login') }}" class="underline">Acceso</a>
            <a href="{{ route('informacion') }}" class="underline">Información del gimnasio</a>
        </nav>
        <h1 class="text-3xl font-bold mb-6">Política de privacidad</h1>
        <p class="alpha-privacy-version">Versión {{ config('privacy.version') }} · Actualizada el 28 de octubre de 2026</p>
        @if (config('privacy.demo'))
            <p class="alpha-privacy-demo"><strong>Proyecto estudiantil.</strong> Alpha Fitness es una demostración académica. El responsable y el correo indicados son ejemplos ficticios; el correo no recibe mensajes. Usa datos e imágenes de prueba, nunca información sensible real. Para consultas, dirígete al equipo o docente que te proporcionó el acceso.</p>
        @endif
        <p class="mb-6">Alpha Fitness utiliza tus datos para gestionar tu cuenta, tus entrenamientos y los servicios del gimnasio. Esta página describe el tratamiento que realiza la aplicación.</p>
        <nav class="alpha-privacy-index" aria-label="Contenido de la política"><a href="#datos">Datos y usos</a><a href="#acceso">Quién accede</a><a href="#cookies">Cookies</a><a href="#derechos">Tus derechos</a><a href="#contacto">Contacto</a></nav>
        <section class="mb-7" aria-labelledby="datos">
            <h2 id="datos" class="text-xl font-bold mb-3">Datos y finalidad</h2>
            <ul class="list-disc pl-6 space-y-2">
                <li><strong>Cuenta:</strong> nombre, correo, contraseña protegida y, si lo eliges, avatar y preferencias de apariencia. Permiten identificarte, iniciar sesión y recuperar el acceso.</li>
                <li><strong>Google:</strong> si eliges esta opción, recibimos tu identificador de Google, tu nombre y tu correo verificado para autenticarte o vincular tu cuenta. No guardamos los tokens de acceso de Google ni accedemos a tus mensajes.</li>
                <li><strong>Membresías:</strong> plan, períodos, solicitudes y pausas para administrar el servicio contratado.</li>
                <li><strong>Asistencia y entrenamiento:</strong> entradas, salidas, rutinas, sesiones y marcas deportivas para mostrar tu historial y facilitar el seguimiento por el entrenador que te asigna una rutina.</li>
                <li><strong>Pagos y ventas:</strong> importes, productos y método de pago registrado para inventario, comprobantes y reportes. La aplicación no procesa pagos en línea ni solicita números de tarjeta.</li>
                <li><strong>Datos técnicos:</strong> identificador de sesión y, según la configuración, dirección IP, navegador y registros de errores para mantener el acceso, limitar intentos y resolver incidencias.</li>
                <li><strong>Lectura de este aviso:</strong> versión y fecha de confirmación, asociadas a tu cuenta al iniciar sesión o registrarte.</li>
            </ul>
            <p>Los datos obligatorios se señalan en los formularios. Sin ellos no se puede completar la operación correspondiente. Google y el avatar son opcionales y no condicionan el acceso básico. La confirmación de lectura no autoriza publicidad ni implica renunciar a tus derechos.</p>
        </section>
        <section class="mb-7" aria-labelledby="acceso">
            <h2 id="acceso" class="text-xl font-bold mb-3">Quién puede consultar los datos</h2>
            <p>Tu cuenta accede a su propia información. El personal autorizado consulta los datos necesarios según su función. Los reportes de analítica financiera contienen cifras agrupadas, sin correos, contraseñas ni identificadores de Google de clientes. Google interviene cuando eliges su autenticación; el proveedor de correo configurado interviene en los mensajes de recuperación, invitaciones y comprobantes.</p>
            <p>Los entrenadores consultan el seguimiento de clientes a quienes han asignado una rutina. Secretaría administra asistencia, membresías y ventas. Administración gestiona cuentas, personal, catálogos y reportes según sus permisos. Las contraseñas no se muestran al personal.</p>
            <p>Los proveedores de alojamiento y correo pueden tratar datos para prestar el servicio. Google aplica su <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">política de privacidad (otra pestaña)</a> y puede operar en otros países. La ubicación y los proveedores dependen del despliegue y deben identificarse antes de un uso real. No hay venta de datos ni decisiones con efectos legales mediante perfiles automatizados.</p>
        </section>
        <section class="mb-7" aria-labelledby="proteccion">
            <h2 id="proteccion" class="text-xl font-bold mb-3">Almacenamiento y protección</h2>
            <p>Los datos se almacenan en la base de datos del despliegue de Alpha Fitness. Las contraseñas se protegen mediante hash y los permisos se comprueban en el servidor. La instalación debe usar HTTPS y proteger su base de datos, copias de seguridad y claves. Los avatares elegidos se publican como imágenes del perfil: evita subir documentos o imágenes privadas.</p>
            <p>Ningún sistema garantiza seguridad absoluta. No compartas contraseñas ni enlaces de recuperación; cierra sesión en equipos compartidos. En desarrollo, el correo puede estar desactivado y sus enlaces guardarse en registros privados de prueba.</p>
        </section>
        <section class="mb-7" aria-labelledby="cookies">
            <h2 id="cookies" class="text-xl font-bold mb-3">Cookies y preferencias</h2>
            <p>Utilizamos cookies de sesión para autenticarte y proteger formularios. Si activas «Recordarme», una cookie permite recuperar tu acceso. El navegador también conserva preferencias de apariencia y de sonido del cronómetro. Las sesiones de entrenamiento finalizadas se guardan en el sistema. No se incorpora un servicio de publicidad o seguimiento externo a estas funciones.</p>
            <p>Bloquear las cookies necesarias puede impedir iniciar sesión. Borrar los datos del sitio desde el navegador no elimina el historial guardado en la base de datos.</p>
        </section>
        <section class="mb-7" aria-labelledby="conservacion">
            <h2 id="conservacion" class="text-xl font-bold mb-3">Conservación y solicitudes</h2>
            <p>{{ config('privacy.retention') }}</p>
            <p>Desactivar una cuenta conserva su historial; no equivale a borrarlo. Esta aplicación no elimina automáticamente el historial financiero. En un uso real, el responsable deberá documentar los plazos y las excepciones por obligaciones aplicables antes de recoger información.</p>
        </section>
        <section aria-labelledby="derechos">
            <h2 id="derechos">Tus derechos y cómo ejercerlos</h2>
            <p>Puedes solicitar acceso, corrección, eliminación, oposición, portabilidad, limitación del tratamiento y olvido cuando correspondan; también retirar una autorización opcional sin afectar el tratamiento previo. La eliminación puede estar limitada por obligaciones justificadas que el responsable deberá explicar.</p>
            <p>Indica la cuenta afectada, qué solicitas y cómo responderte. No envíes tu contraseña. Puede ser necesario verificar tu identidad de forma proporcional. En esta demostración, presenta tu solicitud al equipo o docente que facilitó el acceso: el correo de ejemplo no funciona. Un despliegue real deberá habilitar un canal efectivo y atender los plazos legales aplicables.</p>
        </section>
        <section aria-labelledby="menores">
            <h2 id="menores">Menores e información sensible</h2>
            <p>Esta demostración no debe recoger datos reales de menores ni información médica. Un uso que los incluya requiere un procedimiento específico de información, autorización y protección, con intervención del representante cuando corresponda. El sistema no verifica edad ni tutela y no está diseñado para historias clínicas.</p>
        </section>
        <section aria-labelledby="cambios">
            <h2 id="cambios">Cambios en esta política</h2>
            <p>La fecha y versión aparecen al inicio. Si cambia la versión, se pedirá su lectura en el siguiente inicio de sesión explícito. La confirmación no se usa como consentimiento para publicidad ni para finalidades nuevas.</p>
        </section>
        <section class="mb-7" aria-labelledby="contacto">
            <h2 id="contacto" class="text-xl font-bold mb-3">Responsable y contacto</h2>
            <p><strong>Responsable:</strong> {{ config('privacy.responsible_name') ?: 'Pendiente de configurar por el gimnasio.' }}</p>
            <p><strong>Contacto de privacidad:</strong> {{ config('privacy.contact_email') ?: 'Pendiente de configurar. Puedes presentar tu solicitud en recepción.' }}</p>
            <p>Antes de un uso fuera de la evaluación académica, sustituye los ejemplos por la identidad, dirección y contacto reales del operador, sus proveedores y plazos aplicables.</p>
        </section>
        <p class="alpha-privacy-back mt-8"><a href="{{ route('login') }}">Volver y continuar al acceso →</a></p>
    </main>
</body>
</html>
