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
    <main class="max-w-3xl mx-auto p-5 sm:p-10" id="contenido">
        <nav class="mb-8 flex flex-wrap gap-5" aria-label="Navegación">
            <a href="{{ route('login') }}" class="underline">Acceso</a>
            <a href="{{ route('informacion') }}" class="underline">Información del gimnasio</a>
        </nav>
        <h1 class="text-3xl font-bold mb-6">Política de privacidad</h1>
        <p class="mb-6">Alpha Fitness utiliza tus datos para gestionar tu cuenta, tus entrenamientos y los servicios del gimnasio. Esta página describe el tratamiento que realiza la aplicación.</p>
        <section class="mb-7" aria-labelledby="datos">
            <h2 id="datos" class="text-xl font-bold mb-3">Datos y finalidad</h2>
            <ul class="list-disc pl-6 space-y-2">
                <li><strong>Cuenta:</strong> nombre, correo, contraseña protegida y, si lo eliges, avatar y preferencias de apariencia. Permiten identificarte, iniciar sesión y recuperar el acceso.</li>
                <li><strong>Google:</strong> si eliges esta opción, recibimos tu identificador de Google, tu nombre y tu correo verificado para autenticarte o vincular tu cuenta. No guardamos los tokens de acceso de Google ni accedemos a tus mensajes.</li>
                <li><strong>Membresías:</strong> plan, períodos, solicitudes y pausas para administrar el servicio contratado.</li>
                <li><strong>Asistencia y entrenamiento:</strong> entradas, salidas, rutinas, sesiones y marcas deportivas para mostrar tu historial y facilitar el seguimiento por el entrenador que te asigna una rutina.</li>
                <li><strong>Pagos y ventas:</strong> importes, productos y método de pago registrado para inventario, comprobantes y reportes. La aplicación no procesa pagos en línea ni solicita números de tarjeta.</li>
            </ul>
        </section>
        <section class="mb-7" aria-labelledby="acceso">
            <h2 id="acceso" class="text-xl font-bold mb-3">Quién puede consultar los datos</h2>
            <p>Tu cuenta accede a su propia información. El personal autorizado consulta los datos necesarios según su función. Los reportes de analítica financiera contienen cifras agrupadas, sin correos, contraseñas ni identificadores de Google de clientes. Google interviene cuando eliges su autenticación; el proveedor de correo configurado interviene en los mensajes de recuperación, invitaciones y comprobantes.</p>
        </section>
        <section class="mb-7" aria-labelledby="proteccion">
            <h2 id="proteccion" class="text-xl font-bold mb-3">Almacenamiento y protección</h2>
            <p>Los datos se almacenan en la base de datos del despliegue de Alpha Fitness. Las contraseñas se protegen mediante hash y los permisos se comprueban en el servidor. La instalación debe usar HTTPS y proteger su base de datos, copias de seguridad y claves. Los avatares elegidos se publican como imágenes del perfil: evita subir documentos o imágenes privadas.</p>
        </section>
        <section class="mb-7" aria-labelledby="cookies">
            <h2 id="cookies" class="text-xl font-bold mb-3">Cookies y preferencias</h2>
            <p>Utilizamos cookies de sesión para autenticarte y proteger formularios. Si activas «Recordarme», una cookie permite recuperar tu acceso. El navegador también conserva preferencias de apariencia y el avance temporal de tu entrenamiento. No se incorpora un servicio de publicidad o seguimiento externo a estas funciones.</p>
        </section>
        <section class="mb-7" aria-labelledby="conservacion">
            <h2 id="conservacion" class="text-xl font-bold mb-3">Conservación y solicitudes</h2>
            <p>Los datos se conservan mientras se necesiten para la cuenta y las operaciones del gimnasio. Desactivar una cuenta conserva su historial; no equivale a borrarlo. El responsable debe definir los plazos de conservación y de sus copias de seguridad. Puedes solicitar acceso, corrección o eliminación de tus datos y consultar qué registros deben conservarse por razones operativas. Esta aplicación no elimina automáticamente el historial financiero.</p>
        </section>
        <section class="mb-7" aria-labelledby="contacto">
            <h2 id="contacto" class="text-xl font-bold mb-3">Responsable y contacto</h2>
            <p><strong>Responsable:</strong> {{ config('privacy.responsible_name') ?: 'Pendiente de configurar por el gimnasio.' }}</p>
            <p><strong>Contacto de privacidad:</strong> {{ config('privacy.contact_email') ?: 'Pendiente de configurar. Puedes presentar tu solicitud en recepción.' }}</p>
            @if (! config('privacy.responsible_name') || ! config('privacy.contact_email'))
                <p class="mt-3">El gimnasio debe completar estos datos antes de publicar el sistema para uso general.</p>
            @endif
        </section>
    </main>
</body>
</html>
