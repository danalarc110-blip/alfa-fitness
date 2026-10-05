# Instalación y actualización en Windows/XAMPP

No ejecutar `migrate:fresh`, `db:wipe` ni `migrate:reset` sobre una instalación con datos reales. No regenerar `APP_KEY` al actualizar: cifra sesiones y claves 2FA. Guardar una copia privada de `.env`, archivos subidos y base de datos antes de empezar.

## Instalación nueva

1. En XAMPP Control Panel, pulsar **Start** en **Apache** y **MySQL**. Si aparece un conflicto de puerto, no detener un servicio ajeno: comprobar primero qué servicio usa el puerto.
2. Abrir la carpeta del proyecto en PowerShell. `php -v` debe mostrar PHP 8.2 o superior. Si PHP no está en PATH, usar la ruta real de XAMPP, por ejemplo `C:\xampp68\php\php.exe`.
3. Ejecutar `composer install` y `npm ci`.
4. Copiar `.env.example` a `.env`. Elegir SQLite para una demostración o MySQL para la instalación prevista. En phpMyAdmin abrir **Bases de datos**, crear la base con codificación `utf8mb4`, y seguir `docs/sql/crear_usuario_mysql.sql`. El nombre de la base debe ser el que se escriba en `.env`; no usar root para la aplicación.
5. Configurar `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE`, `DB_USERNAME=alfa_fitness_app` y la contraseña propia. No enviar ni subir `.env` a GitHub.
6. Solo para esta instalación NUEVA: ejecutar `php artisan key:generate`, `php artisan migrate --seed`, `npm run build` y `php artisan alpha:crear-admin`. El comando pregunta nombre, correo y contraseña propia, sin mostrarla.
7. Ejecutar `php artisan serve`, abrir `http://127.0.0.1:8000` y entrar en **Personal** con la cuenta creada. Para demostración local, `php artisan alpha:datos-prueba` agrega cuentas ficticias con claves aleatorias que deben guardarse al verlas.

SQLite nuevo: crear el archivo vacío `database/database.sqlite` si no existe y dejar `DB_CONNECTION=sqlite`. Nunca sustituir un archivo existente. `composer setup` automatiza una instalación de demostración, pero genera la clave: no usarlo para actualizar una instalación existente.

## Actualizar una instalación existente

Antes: abrir **phpMyAdmin → seleccionar la base → Exportar → Personalizado**, incluir todas las tablas y elegir SQL. Pulsar **Exportar** y guardar el archivo en un lugar privado. Copiar también `.env` y `public/images` a una carpeta de respaldo fuera de la raíz pública.

Desde la carpeta del proyecto:

```powershell
git fetch origin
git switch auditoria-y-cierre
git pull --ff-only origin auditoria-y-cierre
php artisan down
composer install --no-interaction --prefer-dist
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan up
php artisan route:list
```

Si la rama todavía no existe localmente, `git switch --track origin/auditoria-y-cierre`. Si Git informa cambios locales, conservarlos y resolverlos antes; no usar `reset --hard`. Como alternativa, descomprimir el ZIP en una COPIA del proyecto, revisar los archivos y desplegar esa copia. El ZIP no contiene `.env`, claves, vendor, node_modules, bases ni archivos personales.

Resultado esperado: Composer y npm terminan sin errores; Vite muestra `built`; cada migración nueva muestra `DONE`; las rutas originales siguen presentes y aparecen las nuevas `/gestion`, `/legal` y `/administracion/dos-factores`. El login original mantiene las pestañas **Personal** y **Clientes**.

## Apache y producción

La raíz del sitio debe ser **la carpeta public**, nunca la raíz del repositorio. Ejemplo de VirtualHost local, ajustando la carpeta real:

```apache
<VirtualHost *:80>
    ServerName alpha.local
    DocumentRoot "C:/ruta/real/alfa-fitness/public"
    <Directory "C:/ruta/real/alfa-fitness/public">
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>
</VirtualHost>
```

En un servidor público, obtener y configurar un certificado válido, usar `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://SU_DOMINIO`, correo SMTP propio y respaldos protegidos. El código fuerza HTTPS, debug desactivado y cookies seguras en producción; local/testing conserva HTTP. Si TLS termina en un proxy, configurar SOLO ese proxy como confiable siguiendo la documentación de Laravel; no aceptar indiscriminadamente cabeceras `X-Forwarded-*`.

Tras verificar HTTPS, ejecutar `php artisan config:cache` y `php artisan view:cache`. No guardar en caché una `.env` incompleta. Un nombre de dominio, certificado o proveedor no se puede inventar desde el código.

Se conserva la zona horaria UTC existente y los timestamps históricos: no se reinterpretan fechas guardadas. Para adoptar hora local en una instalación existente, planificar con su responsable una conversión verificable de fechas; no cambiar la interpretación silenciosamente.

Antes de uso real: completar la configuración legal, validar Google/SMTP, probar MySQL con la base de prueba y verificar licencias/origen de imágenes. Los comprobantes de venta de la app no sustituyen automáticamente documentos tributarios autorizados.
