# Publicación de Alpha Fitness en Render

La configuración de `render.yaml` utiliza un servicio web gratuito y una base PostgreSQL gratuita separada. Está preparada para una exposición estudiantil temporal, sin activar planes de pago. La base local SQLite se importa a PostgreSQL conservando identificadores, relaciones y contraseñas protegidas.

## Qué se publica

- Código, catálogos y frontend compilado durante la construcción de la imagen.
- Registros de negocio de la base local, con sus identificadores, contraseñas protegidas y relaciones existentes; no se migran sesiones, cachés ni enlaces temporales de recuperación.
- Imágenes subidas que no están versionadas, transferidas por un canal privado.
- Cuentas y datos ficticios para Secretaría, Entrenador y Cliente, preparados mediante `demo:prepare`. El administrador conserva sus credenciales.

La base, `.env`, respaldos y contraseñas nunca se incluyen en Git ni en la imagen Docker. `storage/app/private/demo-access.json` contiene los accesos de demostración; no lo publiques.

## Preparación local y transferencia

1. Ejecuta las migraciones pendientes y verifica la aplicación.
2. Para una instalación de demostración, ejecuta `php artisan demo:prepare`. Es idempotente y conserva las cuentas previas.
3. Ejecuta `php artisan render:export` para producir el paquete privado de transferencia. Usa una copia consistente de SQLite, datos portables para PostgreSQL, compresión y cifrado con la clave existente de la aplicación.
4. El resultado se guarda en `storage/app/private/render-transfer.json`. Contiene valores privados para Render; no debe publicarse, pegarse en una incidencia ni agregarse al repositorio.
5. Conserva la clave `APP_KEY` original. No ejecutes `key:generate` al desplegar.

## Creación del servicio

El Blueprint debe aplicarse desde el repositorio remoto con `render.yaml` ya publicado. Antes de confirmar, revisa que tanto el servicio como PostgreSQL indiquen **Free**. La referencia `fromDatabase` enlaza la conexión privada sin publicar su contraseña. La base creada para esta exposición se llama `alpha-fitness-db`; el Blueprint utiliza ese mismo nombre.

Después de que exista el servicio, se cargan `APP_KEY` y las variables privadas del paquete mediante actualización de variables de entorno, conservando las demás variables. En PostgreSQL, las migraciones preparan el esquema y después se importan los registros en una transacción únicamente cuando no existen datos de negocio. Los arranques posteriores conservan los registros existentes. El paquete también recupera los archivos iniciales de demostración en el almacenamiento local temporal.

El arranque ejecuta únicamente migraciones pendientes y prepara las cachés. No ejecuta `migrate:fresh`, no reinicia catálogos y no vuelve a crear las cuentas demo.

## Persistencia y disponibilidad

- Base: PostgreSQL independiente del servicio web. Los registros no desaparecen cuando la web duerme o se reinicia.
- Imágenes de demostración: `/tmp/alpha-data/images`, enlazadas a las rutas públicas que utiliza la aplicación. El paquete inicial permite recuperarlas al arrancar.
- Las imágenes nuevas que se suban después del despliegue pueden perderse al reiniciar o desplegar, porque el plan gratuito no tiene disco persistente. Para la exposición, utiliza los archivos iniciales o evita reiniciar entre una subida y su demostración.
- El servicio web gratuito se suspende después de 15 minutos sin tráfico y tarda aproximadamente un minuto en despertar. Abre la URL unos minutos antes de exponer; esto no garantiza disponibilidad permanente.
- PostgreSQL gratuito caduca 30 días después de su creación. Respalda los datos antes de ese plazo si quieres conservar cambios de la demostración.
- La alternativa SQLite del runtime exige un disco persistente de pago y no está activada por este Blueprint.

## Verificación tras publicar

Espera a que Render indique `live`; una solicitud aceptada de despliegue no demuestra que la aplicación funcione. Comprueba `/up`, `/login`, `/privacidad` y el acceso de los cuatro roles. Verifica que el entrenador vea al cliente demo asignado, que Secretaría pueda operar membresías y ventas y que el cliente consulte únicamente su información.

Comprueba también que los formularios y cookies utilicen HTTPS, que las imágenes iniciales se restauren al reiniciar y que PostgreSQL conserve sus datos tras otro despliegue. No borres ni recrees la base para comprobar persistencia.

Para Google hay que registrar el callback HTTPS de la URL final en el proveedor. El correo permanece en modo de registro para la demostración: no entrega invitaciones ni recuperaciones reales hasta configurar SMTP. No se enviaron correos a terceros durante la preparación.

Referencias: [Blueprints](https://render.com/docs/blueprint-spec), [discos persistentes](https://render.com/docs/disks), [limitaciones del servicio gratuito](https://render.com/docs/free) y [precios](https://render.com/pricing).
