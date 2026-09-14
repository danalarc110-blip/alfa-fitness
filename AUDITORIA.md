# Auditoría y mejoras de Alpha Fitness

## Revisión final del 14 de septiembre de 2026

- El catálogo incluye exactamente cinco productos iniciales editables. El servidor bloquea un sexto producto y muestra cinco por página sin borrar datos heredados.
- Entrenadores tiene búsqueda, imágenes diferidas y gestión separada para el administrador.
- Administrar cuentas quedó reducido a clientes y es exclusivo del administrador: búsqueda, fecha de registro, estado, banear y restaurar. Ya no permite crear personal ni cambiar nombres, correos o roles.
- Cada baneo registra internamente fecha y administrador responsable; una confirmación evita acciones accidentales, se conserva el historial y la sesión baneada se revoca en su siguiente solicitud.
- Se añadió una política de seguridad de contenido, aislamiento de recursos, HSTS bajo HTTPS de producción, sesiones cifradas por defecto y eliminación de dependencias externas de fuentes.
- Las animaciones usan transformaciones breves, solo se activan al entrar en pantalla y respetan `prefers-reduced-motion`. Las vistas previas de imágenes liberan sus URL temporales.
- Se eliminó Axios porque no tenía consumidores. El JavaScript compilado pasó de 53.10 kB a 5.48 kB (20.29 kB a 2.20 kB comprimido) y las auditorías de Composer y npm no reportaron vulnerabilidades conocidas.
- Las imágenes originales de ejercicios se conservaron; sus 14.67 MB tienen copias WebP de entrega que suman aproximadamente 0.21 MB. El fondo, el logotipo y los avatares ficticios también cuentan con variantes ligeras.
- Las imágenes ficticias requeridas se conservaron. Los HTML temporales de QA con tokens de sesión fueron retirados del directorio público.

Mejoras futuras recomendadas: verificar el correo del registro local, actualizar el PHP del servidor antes del despliegue, servir imágenes adaptativas WebP/AVIF y realizar una prueba de carga sobre la base de datos elegida para producción.

Revisión realizada el 10 de septiembre de 2026 sobre la aplicación existente: rutas, autenticación, permisos, controladores, modelos, servicios, migraciones, consultas, vistas, JavaScript, CSS, dependencias y pruebas. El alcance es el código local y su funcionamiento en un entorno de prueba; no constituye una prueba de penetración de un servidor desplegado.

## Correcciones de seguridad y lógica

| Hallazgo | Corrección y comprobación |
| --- | --- |
| El cambio de contraseña intentaba modificar `remember_token` mediante un campo no asignable, y faltaba comprobar el hash en sesiones existentes. | Rotación explícita del token, hash de sesión desde el inicio de sesión y middleware `auth.session` en todas las rutas protegidas. Pruebas para ambos tipos de cuenta. |
| Rutas, navegación y controladores elegían distintas identidades si coexistían sesiones de cliente y empleado. | Un mismo orden de selección en rutas compartidas; la identidad de cliente no puede tomar permisos de empleado. Prueba de acceso directo. |
| Google vinculaba por correo una cuenta local sin demostrar que el usuario controlaba su contraseña. | Vinculación desde Ajustes mediante contraseña actual, autorización de cinco minutos y consumo único, además del estado OAuth de Socialite. Acceso posterior por identificador estable de Google, incluso si cambia el correo del proveedor. Pruebas con proveedor simulado. |
| Excepciones de OAuth y correo podían incluir tokens o datos del transporte en logs. | Registro del tipo de excepción sin su contenido sensible; las invitaciones fallidas conservan una cuenta pendiente y una opción de reenvío. |
| Dos solicitudes diferentes de un mismo cliente podían calcular el mismo periodo de membresía. | Bloqueo del cliente dentro de la transacción antes de leer periodos y registrar pago. Se mantiene la resolución única por solicitud, se bloquean activaciones de clientes inactivos y se validan importes con hasta dos decimales. |
| Operaciones de rutinas podían quedar a medias o competir al ordenar días y ejercicios. | Creación y modificaciones estructurales transaccionales, bloqueos del padre y validación del conjunto al reordenar. Se rechazan ejercicios inactivos. |
| El inicio mostraba como máximo cuatro rutinas en el contador. | Conteo independiente del listado de las cuatro más recientes. Prueba con seis rutinas. |
| El filtro «Hasta» de asistencia dependía de haber indicado «Desde». | Validación condicional del orden de fechas. Se puede consultar solo por fecha final. |
| Archivos originales se publicaban sin recodificación en algunos flujos; el alta de un producto podía dejar un registro incompleto. | Servicio común con recodificación obligatoria, límite de píxeles y tamaño, nombres propios y limpieza al fallar. Se sincroniza la sustitución de imágenes con el registro bloqueado y se conserva el archivo anterior hasta guardar. |
| Filtros de búsqueda aceptaban estructuras inesperadas. | Validación de tipo y longitud antes de construir consultas. Se mantienen consultas parametrizadas. |
| Algunos envíos repetidos y respuestas asíncronas podían duplicar elementos o mostrar valores anteriores. | Bloqueo de operaciones pendientes en altas de días, ejercicios y calificaciones; se mantiene el borrador al cambiar de panel mientras se guarda un campo. |
| Faltaban cabeceras defensivas y controles de caché privada. | `nosniff`, protección de marcos, política de referente, permisos del navegador y `private, no-store` en respuestas autenticadas y recuperación de contraseña. Cookies seguras por defecto en producción. |

Se conservaron los controles de propiedad de rutinas y progreso, la matriz de permisos, el administrador único, las contraseñas ocultas en serialización, CSRF, los límites de intentos y la validación de campos permitidos. Se comprobó CSRF contra el servidor HTTP real, porque Laravel lo omite normalmente durante pruebas unitarias.

## Apariencia, experiencia de uso y mantenimiento

- Ajustes ofrece **Claro**, **Oscuro** y **Personalizado**. El modo inicial es claro.
- La paleta permite editar principal, acento, fondo, superficie y texto. Las paletas base viven en `App\Support\Apariencia`; los componentes consumen variables `--alpha-*`.
- La preferencia se guarda en la cuenta, se conserva entre sesiones y se aplica antes de cargar los estilos. El almacenamiento del navegador es opcional y no reemplaza la preferencia de otra cuenta autenticada.
- La vista previa no modifica la interfaz hasta guardar. El servidor y el navegador rechazan colores que no sean hexadecimales y texto con contraste inferior a 4.5:1 contra cualquiera de los dos fondos. Las etiquetas de botones, acentos y estados usan colores legibles derivados de la paleta.
- Restaurar predeterminados prepara la paleta clara; Guardar confirma la restauración.
- Se unificaron superficies, botones, foco, formularios y navegación sin duplicar hojas completas por tema. Se corrigió la discrepancia entre la ayuda de contraseña y la política de doce caracteres.
- Las animaciones se limitan a entradas breves y microinteracciones. Se retiraron los efectos continuos y los manejadores de animación sobre todos los botones. Se respeta la reducción de movimiento incluso si cambia durante la sesión; el modal conserva foco, cierre con Escape y navegación por teclado.
- El listado de rutinas usa paginación y conteos de relaciones, sin cargar todos los ejercicios para contar.
- Se retiraron vistas y controladores sin rutas ni referencias activas, junto con la hoja de efectos no utilizada. Las rutas históricas de estadísticas y rankings mantienen su redirección al progreso privado.
- Las capturas del comprobador visual se generan en almacenamiento privado, fuera del directorio público.

## Dependencias y datos

`league/commonmark` se actualizó de 2.8.3 a 2.10.1 y `nanoid` a 3.3.18, dentro de los rangos compatibles. Se retiró GSAP al sustituir sus usos por animaciones nativas pequeñas. Composer y npm finalizaron sin avisos conocidos de vulnerabilidad en la consulta realizada.

La migración `2026_09_10_000000_add_appearance_to_accounts` agrega una columna JSON nullable a `users` y `clientes`. Está aplicada en este espacio de trabajo. No cambia los registros previos ni recrea tablas de negocio.

## Verificación

La suite inicial tenía 22 pruebas y 138 aserciones. Se ampliaron las pruebas de regresión de apariencia, permisos, sesiones, archivos, consultas, membresías y Google. Los resultados finales y comandos se recogen en el README.

El recorrido de navegador usa Chrome sin ventana y una base SQLite temporal. Comprueba:

- Los tres temas en inicio, productos, ejercicios, entrenamientos, progreso, membresías, entrenadores y Ajustes.
- Guardado, recarga, contraste inválido, restauración, cierre y nuevo inicio de sesión.
- Vista móvil de 390 px, menú con Escape, tablas con desplazamiento propio y edición de rutinas.
- Preferencia de movimiento reducido, accesos de administrador, secretaria y entrenador, y denegación del progreso de clientes al personal.
- Excepciones de JavaScript y respuestas HTTP de las rutas comprobadas.

## Límites de la comprobación y despliegue

Las pruebas de Google y entrega de invitaciones usan sustitutos; el acceso real a Google y la entrega SMTP deben comprobarse con la configuración del despliegue. No se enviaron correos reales. Los bloqueos se revisaron en código y los flujos transaccionales se probaron con SQLite; no se hizo una prueba de carga concurrente sobre MySQL.

El entorno disponible usa **PHP 8.2.12**. Antes de publicar, actualizar el intérprete a una versión mantenida con sus parches, habilitar GD, servir exclusivamente `public/` mediante HTTPS y configurar `APP_ENV=production`, `APP_DEBUG=false` y correo real. La rama 8.2 tiene soporte de seguridad hasta el 31 de diciembre de 2026 según [PHP](https://www.php.net/supported-versions.php). Actualizar el PHP compartido de XAMPP queda fuera de los cambios del repositorio.

Referencias consultadas: [API de sesiones de Laravel](https://api.laravel.com/docs/12.x/Illuminate/Auth/SessionGuard.html) y [seguridad de CommonMark](https://commonmark.thephpleague.com/2.x/security/). La ausencia de avisos en las dependencias no equivale a una garantía de ausencia de vulnerabilidades.
