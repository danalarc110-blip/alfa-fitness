# Alpha Fitness

Aplicación Laravel 12 para gestionar entrenamiento y operaciones del gimnasio.

## Uso local

Requisitos: PHP 8.2 o superior, Composer, Node.js y SQLite o MySQL.

```sh
composer install
npm ci
```

En una instalación nueva, copia `.env.example` a `.env`, configura la base de datos y ejecuta:

```sh
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Abre http://127.0.0.1:8000. Para crear tu primer administrador: `php artisan alpha:crear-admin`.
No ejecutes `migrate:fresh` sobre una base con datos que quieras conservar.

Guía paso a paso para Windows/XAMPP: [INSTALACION_XAMPP.md](docs/INSTALACION_XAMPP.md).
Resultados y límites de la auditoría: [INFORME_FINAL.md](docs/INFORME_FINAL.md).

## Funciones

- Inicio con indicadores reales, rutinas propias y visitas recientes.
- Registro e ingreso de clientes y acceso de empleados.
- Constructor de rutinas, catálogo de ejercicios y calificaciones.
- Membresías: registro, importe en USD, fechas, detección de superposiciones, búsqueda, historial y cancelación. La renovación se registra como un nuevo periodo; no procesa pagos en línea.
- Entrenadores: directorio con búsqueda; el administrador puede crear, invitar y editar entrenadores.
- Asistencia: secretaría registra entradas y salidas; el cliente ve sus visitas en su panel. Correcciones y anulaciones conservan una bitácora y no borran registros.
- Productos e inventario: cinco productos iniciales editables y un máximo de cinco en el catálogo.
- Administrar cuentas: solo el administrador puede buscar clientes, ver su fecha de registro y banear o restaurar su acceso; la acción pide confirmación y conserva el historial.
- Perfil, avatar, diseños Elegante y Verde, modos claro/oscuro/personalizado y navegación móvil accesible por teclado.
- Gestión complementaria: usuarios por invitación, clientes, planes y sesiones con entrenador; búsqueda, paginación, bajas lógicas y agenda sin solapamientos.
- Ventas con inventario transaccional; el administrador puede anular registros erróneos restituyendo existencias una sola vez. No procesa ni reembolsa tarjetas.
- Estadísticas privadas por ejercicio, volumen y estimación orientativa, sin publicar datos de otros clientes.
- Documentos legales, consentimiento de nuevas cuentas con fecha/versión y archivo del texto aceptado, solicitudes sobre datos y procedimiento de brechas.
- Verificación TOTP opcional del administrador, con claves cifradas y códigos de recuperación. [Seguridad](docs/SEGURIDAD.md).

## Diseño y apariencia

El diseño Elegante es el predeterminado y conserva la paleta original y el logotipo plateado. En **Configuración → Apariencia → Diseño de la interfaz** puedes elegir **Verde** y guardar. Cada cuenta conserva su elección al volver a iniciar sesión. El diseño se aplica también al constructor de rutinas y a las pantallas de acceso.

El modo de color se elige por separado: Claro, Oscuro o Personalizado. La vista previa permite revisar los cambios antes de guardarlos; el modo personalizado valida el contraste del texto. **Restaurar predeterminados** prepara la combinación Elegante/Claro; pulsa **Guardar apariencia** para confirmarla.

## Imágenes

Puedes colocar tus archivos en `public/images/ejercicios/`. Los campos `imagen` e `imagen_musculos` del ejercicio indican el nombre del archivo. También se buscan variantes PNG, JPG, JPEG y WebP. Las vistas del catálogo incluyen un estado sin imagen.

Logotipos: `public/images/logo-sidebar.png` y `public/images/LOGO.png`. Fondo de acceso: `public/images/gym-bg.png`. Los avatares se cambian en Configuración. El nuevo inicio usa un fondo CSS y no necesita fotografías.

Las fuentes originales se conservan y el navegador recibe copias WebP ligeras. Si sustituyes una imagen, regenera esas copias con `php tools/optimize_images.php`.

## Datos y despliegue

El registro público crea clientes. Las cuentas de empleados se administran por invitación y cada persona establece su propia contraseña. El seeder no crea usuarios ni credenciales conocidas.

Los datos ficticios se agregan únicamente en local/testing mediante `php artisan alpha:datos-prueba`; las contraseñas aleatorias nuevas se muestran una vez. No utilizar en una instalación real: [DATOS_PRUEBA.md](docs/DATOS_PRUEBA.md).

Antes de abrir al público, completar los marcadores de `.env.example` y revisar los documentos con un abogado de El Salvador. Si cambian los textos o los datos del responsable, publicar otra `LEGAL_VERSION`: [CONFIGURACION_LEGAL.md](docs/CONFIGURACION_LEGAL.md).

Google requiere credenciales propias en las variables de `config/services.php`. El acceso por correo funciona sin Google. Para actualizar, conserva `.env` y `APP_KEY`, usa HTTPS en producción, apunta el servidor a `public/` y ejecuta `php artisan migrate --force`, `npm run build` y `php artisan optimize:clear`. No vuelvas a sembrar datos reales como parte de una actualización normal. En producción usa correo SMTP real y conserva `SESSION_ENCRYPT=true`.

## Verificación

```sh
php artisan test
php artisan route:list
php tools/verify_additive_migrations.php
npm run build
php artisan view:cache
composer audit
npm audit
```

Las pruebas usan SQLite en memoria. El verificador de migraciones crea una base temporal propia y la retira al terminar; nunca utiliza la base configurada de la instalación. Google/SMTP/TLS/MySQL reales requieren sus propias credenciales y una verificación de despliegue.
