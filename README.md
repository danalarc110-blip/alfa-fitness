# Alpha Fitness

Aplicación Laravel 12 para gestionar entrenamiento y operaciones del gimnasio.

## Uso local

Requisitos: PHP 8.2 o superior, Composer, Node.js compatible con Vite 7 (20.19+ o 22.12+) y SQLite o MySQL. PHP necesita PDO para la base elegida, mbstring, XML/DOM, OpenSSL, cURL, ZIP y GD. GD recodifica imágenes y elimina sus metadatos; si falta, habilítalo en el entorno antes de probar uploads. No se modifica `php.ini` automáticamente.

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

Abre http://127.0.0.1:8000. En una instalación existente, respalda primero la base y aplica solamente las migraciones pendientes con `php artisan migrate`.
No ejecutes `migrate:fresh` sobre una base con datos que quieras conservar.

Para crear el administrador inicial, ejecuta `php artisan admin:create` y proporciona los datos en los prompts. La aplicación admite un único administrador y valida la fortaleza de su contraseña. No guardes contraseñas en comandos, documentación ni historial del terminal. Los seeders cargan catálogos, no cuentas con credenciales conocidas.

## Funciones

- Inicio con indicadores reales, rutinas propias y visitas recientes.
- Registro e ingreso de clientes y acceso de empleados.
- Constructor de rutinas, catálogo de ejercicios y calificaciones.
- Membresías: registro, importe en USD, fechas, detección de superposiciones, búsqueda, historial y cancelación. La renovación se registra como un nuevo periodo; no procesa pagos en línea.
- Entrenadores: directorio con búsqueda; el administrador puede crear, invitar y editar entrenadores.
- Asistencia: entrada, salida e historial. Los clientes solo consultan y registran sus propias visitas; los empleados gestionan el conjunto.
- Productos e inventario: cinco productos iniciales editables y catálogo paginado de cinco en cinco, sin límite artificial de productos.
- Administrar cuentas: solo el administrador puede buscar clientes, ver su fecha de registro y banear o restaurar su acceso; la acción pide confirmación y conserva el historial.
- Perfil, avatar, diseños Elegante y Verde, modos claro/oscuro/personalizado y navegación móvil accesible por teclado.
- Personal: creación e invitación de secretarias y entrenadores por el administrador.
- Ventas y comprobantes; analítica financiera agregada de hasta 90 días, exportable a PDF y Excel por el administrador.
- Entrenar Ahora e historial de sesiones persistidas; un reintento con el mismo identificador no crea otra sesión o venta.
- Política de privacidad pública en `/privacidad`, accesible desde el acceso y el pie de la aplicación.

## Diseño y apariencia

El diseño Elegante es el predeterminado y conserva la paleta original y el logotipo plateado. En **Configuración → Apariencia → Diseño de la interfaz** puedes elegir **Verde** y guardar. Cada cuenta conserva su elección al volver a iniciar sesión. El diseño se aplica también al constructor de rutinas y a las pantallas de acceso.

El modo de color se elige por separado: Claro, Oscuro o Personalizado. La vista previa permite revisar los cambios antes de guardarlos; el modo personalizado valida el contraste del texto. **Restaurar predeterminados** prepara la combinación Elegante/Claro; pulsa **Guardar apariencia** para confirmarla.

## Imágenes

Puedes colocar tus archivos en `public/images/ejercicios/`. Los campos `imagen` e `imagen_musculos` del ejercicio indican el nombre del archivo. También se buscan variantes PNG, JPG, JPEG y WebP. Las vistas del catálogo incluyen un estado sin imagen.

Logotipos: `public/images/logo-sidebar.png` y `public/images/LOGO.png`. Fondo de acceso: `public/images/gym-bg.png`. Los avatares se cambian en Configuración. El nuevo inicio usa un fondo CSS y no necesita fotografías.

Las fuentes originales se conservan y el navegador recibe copias WebP ligeras. Si sustituyes una imagen, regenera esas copias con `php tools/optimize_images.php`.

## Datos y despliegue

El registro público crea clientes. Las cuentas de empleados se administran por invitación y cada persona establece su propia contraseña. El seeder no crea usuarios ni credenciales conocidas.

El archivo `.env` es privado y no debe versionarse. Usa `.env.example` como referencia; `APP_KEY` protege el cifrado de Laravel y no se debe regenerar sobre una instalación existente. Custodia la clave y las copias de seguridad fuera de `public/`.

Google requiere `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y `GOOGLE_REDIRECT_URI`, con la URL exacta del callback `/cliente/google/callback` registrada en el proveedor. El acceso por correo funciona sin Google. Las cuentas existentes se vinculan desde Configuración tras confirmar la contraseña; no se vinculan silenciosamente por coincidencia de correo. No se guardan tokens de acceso OAuth.

`MAIL_MAILER=log` sirve para desarrollo: no entrega mensajes y escribe el contenido del correo, incluidos enlaces de recuperación e invitación, en logs privados. En producción configura SMTP real, remitente autorizado y acceso restringido a logs. La respuesta de recuperación no confirma si una cuenta existe.

Configura `APP_TIMEZONE` según el despliegue. `.env.example` propone `America/El_Salvador`; sin esa variable se conserva UTC para no reinterpretar fechas de una instalación existente. Cambiar de zona en una instalación con datos requiere revisar sus fechas históricas.

Antes de publicar, completa `PRIVACY_RESPONSIBLE_NAME` y `PRIVACY_CONTACT_EMAIL`, y define la conservación de datos y copias de seguridad con el responsable. Los avatares son públicos; no deben contener documentos ni información privada. No se añaden banners de cookies publicitarias que la aplicación no utiliza.

Para desplegar, configura `.env` con `APP_ENV=production` y `APP_DEBUG=false`, usa HTTPS y apunta el servidor a `public/`. Conserva `SESSION_ENCRYPT=true`, cookies HttpOnly y SameSite=Lax. Las cookies Secure se activan por defecto en producción; localhost sigue admitiendo HTTP. Ejecuta las migraciones pendientes tras un respaldo, `npm ci`, `npm run build` y `php artisan optimize`. No uses seeders en producción sin revisar previamente los catálogos que modifican.

Las migraciones del hardening separan los tokens de clientes, protegen ventas y detalles frente a borrados en cascada, identifican al entrenador por ID y agregan claves de reintento. La separación de tokens invalida enlaces pendientes anteriores: se debe solicitar un enlace nuevo. Las asignaciones históricas conservan su nombre, pero este no concede permiso para consultar progreso: se deben verificar y reasignar mediante el flujo autorizado. No se infieren identidades a partir de nombres duplicables.

Los importes persistidos usan DECIMAL; las ventas calculan en centavos enteros. La analítica convierte a números solo para presentación de gráficos, PDF y Excel. Las bajas del personal y productos deben hacerse por desactivación para conservar la historia.

## Verificación

```sh
php artisan test
npm run build
php artisan view:cache
vendor/bin/pint --test
composer audit
npm audit
```

Las pruebas usan SQLite en memoria y cubren módulos, permisos, privacidad, recuperación entre proveedores, IDOR, integridad financiera y reintentos. No prueban la entrega real de correo, credenciales reales de Google ni concurrencia de MySQL: esos controles se verifican en el despliegue correspondiente. Se deben mantener directorios escribibles y espacio libre para vistas, exports y logs.
