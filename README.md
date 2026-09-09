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
php artisan migrate
npm run build
php artisan serve
```

Abre http://127.0.0.1:8000. En este espacio de trabajo ya se aplicó la migración de membresías.
No ejecutes `migrate:fresh` sobre una base con datos que quieras conservar.

## Funciones

- Inicio con indicadores reales, rutinas propias y visitas recientes.
- Registro e ingreso de clientes y acceso de empleados.
- Constructor de rutinas, catálogo de ejercicios y calificaciones.
- Membresías: registro, importe en USD, fechas, detección de superposiciones, búsqueda, historial y cancelación. La renovación se registra como un nuevo periodo; no procesa pagos en línea.
- Entrenadores: directorio de empleados activos con ese rol y alta de cuentas por el administrador.
- Asistencia: entrada, salida e historial. Los clientes solo consultan y registran sus propias visitas; los empleados gestionan el conjunto.
- Productos e inventario, progreso, marcas personales, estadísticas y rankings.
- Perfil, avatar, tema claro/oscuro y navegación móvil accesible por teclado.

## Imágenes

Puedes colocar tus archivos en `public/images/ejercicios/`. Los campos `imagen` e `imagen_musculos` del ejercicio indican el nombre del archivo. También se buscan variantes PNG, JPG, JPEG y WebP. Las vistas del catálogo incluyen un estado sin imagen.

Logotipos: `public/images/logo-sidebar.png` y `public/images/LOGO.png`. Fondo de acceso: `public/images/gym-bg.png`. Los avatares se cambian en Configuración. El nuevo inicio usa un fondo CSS y no necesita fotografías.

## Datos y despliegue

El registro público crea clientes. Las cuentas de empleados se administran por invitación y cada persona establece su propia contraseña. El seeder no crea usuarios ni credenciales conocidas.

Google requiere credenciales propias en las variables de `config/services.php`. El acceso por correo funciona sin Google. Para desplegar, configura `.env`, desactiva `APP_DEBUG`, usa HTTPS, apunta el servidor a `public/` y ejecuta `php artisan migrate --force`, `npm run build` y `php artisan optimize`.

## Verificación

```sh
php artisan test
npm run build
php artisan view:cache
```

Las pruebas usan SQLite en memoria y cubren membresías, privacidad de asistencia, permisos de entrenadores, productos, progreso y rankings.
