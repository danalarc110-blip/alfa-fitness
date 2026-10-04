# Seguridad y despliegue de Alpha Fitness

Revisión del 4 de octubre de 2026. Base auditada: `2895047`; caracterización previa: `335281b` (89 pruebas, 748 aserciones). Este documento describe controles técnicos; no certifica ausencia absoluta de fallos ni sustituye una revisión profesional del despliegue.

## Controles implementados

- Las rutinas se consultan, modifican y asignan sólo desde la identidad propietaria. Un empleado puede seguir asignando sus propias rutinas.
- Si excepcionalmente coexisten los guards `cliente` y `web`, las pantallas compartidas utilizan la identidad cliente, con menos privilegios. Las pausas propias del cliente siguen pendientes de aprobación y no toman privilegios de otra sesión.
- Los nombres de productos se insertan como texto en el selector del TPV. Se eliminó la interpretación HTML que permitía XSS almacenado.
- Los filtros de fechas de ventas se validan antes de consultar; los nombres y correos exportados a CSV se neutralizan cuando Excel podría interpretarlos como fórmulas.
- En `APP_ENV=production`, el sistema desactiva debug, cifra sesiones, obliga cookies Secure/HttpOnly/SameSite=Lax y redirige HTTP a HTTPS usando el host de `APP_URL`, nunca el Host enviado por el visitante. XAMPP con `APP_ENV=local` conserva HTTP.
- Continúan CSRF, límites de intentos de login, contraseñas robustas, renovación de sesión, correo Google verificado y control de state de Socialite. El flujo real Google requiere las credenciales externas correctas.
- Las imágenes nuevas se validan y recodifican a WebP con nombre aleatorio, sin metadatos. Los directorios de subida añaden defensa Apache contra ejecución de scripts/listado de archivos. Apache debe permitir los `.htaccess`; otros servidores necesitan reglas equivalentes.
- Las subidas personales, secretos `.env.*` y nuevas imágenes generadas quedan excluidos de Git. La exclusión no elimina archivos que ya estaban versionados ni su historial.
- Ventas conserva sus métodos actuales Efectivo, Tarjeta y Transferencia. No existen campos de número de tarjeta o CVV; se rechazan campos explícitos `card_number`, `pan` y `cvv`. Las notas/referencias libres no deben contener números de tarjeta, CVV, claves ni información médica innecesaria.

## Decisiones tomadas y compatibilidad

La secretaria conserva la creación de productos, ventas, cobros y actualización de inventario/textos. Se restringe al administrador cambiar un precio existente: fue solicitado expresamente como medida de seguridad y constituye una excepción documentada a la regla de conservar todas las acciones anteriores. En la actualización se compara el precio en centavos; volver a enviar el mismo precio no bloquea la edición de stock.

La política CSP conserva temporalmente `unsafe-inline` porque la aplicación tiene scripts y eventos inline existentes. Eliminarlos requiere una migración completa de las vistas; no se presenta esa CSP como protección suficiente contra todo XSS. Los controles de salida/entrada corrigieron el punto vulnerable comprobado.

Los registros financieros deben conservarse: las FK antiguas pueden eliminar ventas o detalles al borrar físicamente usuarios/productos. Las bajas administrativas deben desactivar registros, no borrar su historial. No se eliminaron índices redundantes existentes ni se cambiaron contratos de tablas.

## Auditoría de acciones

El canal `auditoria` escribe `storage/logs/auditoria-AAAA-MM-DD.log`, rota a diario y conserva 30 días por defecto (`AUDIT_LOG_DAYS`). El responsable debe confirmar ese plazo con su política de retención y revisión legal.

Eventos: `login_fallido`, `precio_cambiado`, `rol_cambiado`, `acceso_cambiado`, `registro_eliminado`, `dos_factores_cambiado`, `dos_factores_fallido`. Incluyen solamente guard, IDs internos, tabla, importes o valores de rol/estado permitidos. No incluyen nombres, correos, IP, contraseña, token, request completo, ni texto de excepciones SMTP/OAuth. Los cambios de modelos se registran tras confirmar la transacción; una operación revertida no figura como completada. Los borrados por cascada o consultas SQL directas no generan eventos Eloquent individuales; el evento del registro principal y los permisos restringidos de base reducen este límite.

Si el archivo de auditoría no puede escribirse, se emite un aviso fijo al log de PHP/Apache (`auditoria no disponible`). No se devuelve un falso error de pago después de que su transacción ya se confirmó. Revisar permisos y espacio en disco ante ese aviso. Proteger `storage` y los backups fuera del directorio público y limitar el acceso a los logs.

## Secretos e historial

Se escanearon las 17 revisiones accesibles de Git al inicio. No se encontraron claves reales de Google, `APP_KEY`, contraseñas BD ni `.env` versionado. `.env.example` conserva claves vacías o marcadores. La APP_KEY fija de PHPUnit sólo sirve para pruebas aisladas.

Las revisiones anteriores contienen contraseñas previsibles en `database/seeders/DatabaseSeeder.php` (líneas 25 y 38, presentes hasta `510d176`; retiradas desde `e861fb5`). Si una instalación conserva cuentas creadas por esos seeders, establecer contraseñas nuevas desde el enlace privado de invitación o el procedimiento de recuperación. No se publican sus valores en este informe. No se reescribe la historia compartida del repositorio.

Se encontraron también seis imágenes de avatares de clientes versionadas antes de esta auditoría. Determinar si representan personas reales y si existe autorización para publicarlas. Ignorarlas sólo evita futuras adiciones; no las retira de revisiones anteriores. No borrar imágenes activas de la instalación ni reescribir Git sin un procedimiento coordinado de conservación y privacidad.

Si Google/SMTP/BD se compartieron fuera del repositorio, la ausencia de secretos en Git no demuestra que nunca se expusieron. Rotarlos únicamente cuando corresponda y actualizar el `.env` privado. No ejecutar `key:generate` en una instalación existente como actualización normal: invalidaría material cifrado si no se conserva la clave anterior de forma segura.

## XAMPP y producción

En XAMPP local: `APP_ENV=local`, `APP_URL` con la URL real local, y Apache sirviendo exclusivamente la carpeta `public`. No exponer la raíz del proyecto, `.env`, `storage`, `vendor` o backups en el sitio web. El servidor de desarrollo no es un despliegue público.

Antes de producción, el servidor debe disponer de certificado HTTPS válido. Configurar `APP_ENV=production`, `APP_DEBUG=false` y `APP_URL=https://{{DOMINIO_REAL}}`. Activar HTTPS antes de esta configuración para evitar redirecciones a un servicio inexistente. Si hay proxy, sus direcciones confiables deben configurarse explícitamente por el administrador; no confiar en cualquier `X-Forwarded-Proto` que envíe Internet.

Usar [el script MySQL](sql/crear_usuario_mysql.sql) para una cuenta propia limitada a la base existente, conservando todos los datos. Nunca publicar el archivo con contraseñas reales. En phpMyAdmin: abrir la base → pestaña **SQL** → pegar el script con marcadores completados → **Continuar**. Actualizar `DB_USERNAME` y `DB_PASSWORD` únicamente en `.env`, ejecutar `php artisan optimize:clear` y comprobar acceso normal al sistema. No ejecutar `migrate:fresh` sobre datos reales.

SMTP y Google reales, TLS/certificado, reglas Apache activas y privilegios de la instancia MySQL del usuario sólo pueden validarse con acceso a esa instalación y sus credenciales. La suite verifica escenarios simulados, permisos, errores y contratos del código.
