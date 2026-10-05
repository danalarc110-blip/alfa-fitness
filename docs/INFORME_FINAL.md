# Informe final — Alpha Fitness

Fecha: 4 de octubre de 2026. Repositorio: [danalarc110-blip/alfa-fitness](https://github.com/danalarc110-blip/alfa-fitness). Rama de entrega: `auditoria-y-cierre`. Base: `28950473b9c302325c1f4075f4e929a22f476ceb`. No se integra ni modifica `main`.

Actualización de alcance, 5 de octubre de 2026: el usuario confirmó que es un **proyecto estudiantil**, no un despliegue comercial. No se requiere contratar un abogado, identificar una empresa inexistente ni configurar servicios externos para presentar la demostración. Las recomendaciones sobre un gimnasio real de este informe son referencias para una posible adopción futura, no tareas pendientes de la entrega académica.

Se mantienen las protecciones y todos los módulos. Los documentos son plantillas educativas; el acceso muestra el carácter estudiantil y recomienda datos/operaciones ficticios. Se publica la versión `2026-10-05-academico` sin modificar archivos legales ya aceptados. Las credenciales de demostración local no se suben a GitHub. Esto no certifica seguridad absoluta; si se usan datos reales, deben protegerse y atenderse las solicitudes, también en un proyecto estudiantil.

Verificación del ajuste académico: **173 pruebas, 1.440 aserciones, cero errores/fallos/omitidas y 133 rutas**. Se conservaron los controles de consentimiento, guard, registro y archivo inmutable; cuatro pruebas nuevas cubren el contexto educativo. Chrome comprobó los cuatro roles con cuentas ficticias, documentos públicos y vista móvil sin errores JavaScript. Evidencias: [academico.txt](evidencia/academico.txt), [academico-junit.xml](evidencia/academico-junit.xml) y [browser-academico.txt](evidencia/browser-academico.txt). Pint, build y compilación de vistas aprobados.

Arranque local: se respaldó SQLite antes de aplicar las seis migraciones pendientes con `migrate --force`, sin fresh ni reset. Se confirmó que la copia local no tenía usuarios/clientes; se añadieron tres empleados y un cliente de demostración con claves aleatorias, más los datos ficticios del comando existente. No se cambiaron claves ni cuentas anteriores. Los accesos se entregan en un archivo privado fuera de Git y de `public`, restringido al usuario de Windows. `php artisan serve --host=127.0.0.1 --port=8000 --tries=1 --no-reload` queda en segundo plano, limitado a este equipo. Login y los cuatro documentos responden HTTP 200 con la versión académica efectiva.

Al actualizar otra copia, si `.env` declara una versión vieja, cambiar solo `LEGAL_VERSION=2026-10-05-academico` y ejecutar `php artisan optimize:clear`; no regenerar APP_KEY ni sustituir `.env`. La tabla de cierre de abajo corresponde a la auditoría original del 4 de octubre; los resultados de este párrafo son los actuales.

## 1. Resumen de cambios

### Seguridad

- Inventario de rutas, controladores, modelos, migraciones, seeders, middleware, vistas, configuración, dependencias e historial. Se revisaron las 17 revisiones accesibles anteriores a esta auditoría. No apareció un `.env` versionado ni una APP_KEY, contraseña BD o secreto Google real. Se amplió `.gitignore` para variantes de `.env` y nuevas subidas personales. `.env.example` queda sin secretos.
- Se encontraron contraseñas previsibles en seeders históricos hasta `510d176`, ya retiradas desde `e861fb5`. Si sobreviven cuentas creadas entonces, deben cambiar sus contraseñas. No se reproducen esos valores ni se reescribe el historial compartido. Las claves de PHPUnit y ejemplos RFC son exclusivamente sintéticos.
- Producción fuerza debug desactivado, HTTPS al host configurado, cookies Secure/HttpOnly/SameSite=Lax, sesiones cifradas y cabeceras de seguridad; HTTP local XAMPP sigue funcionando. La redirección actúa antes de iniciar sesión o validar CSRF. Se conservan los scripts existentes: la CSP todavía permite `unsafe-inline`, limitación documentada.
- Revisados límites de intentos, regeneración de sesión, hash, contraseñas robustas, CSRF y validación. Se impide truncar silenciosamente contraseñas bcrypt de más de 72 bytes. No se cambió cómo se validan contraseñas antiguas al entrar.
- Separación de guards y permisos: una identidad cliente no retiene acceso de empleado. Corregido IDOR al asignar rutinas; pausas, directorio y módulos nuevos no toman privilegios de otra sesión. Precios existentes, roles, planes y anulaciones financieras quedan reservados al administrador; secretaría conserva operación cotidiana e inventario.
- Google mantiene state de Socialite y exige correo verificado. Una cuenta local no se vincula silenciosamente por coincidir el correo: requiere acreditar la contraseña actual. Nuevos usuarios Google confirman los documentos antes de crear la cuenta; no se guardan tokens de Google.
- 2FA TOTP opcional del administrador, desactivado por defecto. Claves cifradas, ventana limitada, protección de reutilización de códigos, revocación de sesiones recordadas y ocho códigos de recuperación de un uso con 128 bits aleatorios. No se utiliza un servicio QR externo. Instrucciones en [DOS_FACTORES.md](DOS_FACTORES.md).
- Corregido XSS almacenado en opciones dinámicas del TPV; neutralización de fórmulas en CSV; filtros de fechas y entradas validados; imágenes nuevas verificadas y recodificadas a WebP aleatorio sin metadatos. Defensa Apache adicional contra ejecución en directorios de subida. No se introdujo SQL concatenado con entradas del usuario.
- No hay columnas/formularios para PAN, CVV, PIN o vencimiento de tarjeta. Se rechazan campos explícitos y patrones de tarjeta/CVV en notas de pago. La aplicación registra el método, no procesa tarjetas. Esto no es una auditoría PCI ni demuestra que nunca se hayan escrito datos indebidos en texto libre de una base real no accesible.
- Auditoría diaria de fallos de login, precios, roles, acceso, borrados y 2FA, con IDs y contexto limitado, sin contraseñas, tokens, correos ni texto de excepciones. Eventos de modelos después del commit; fallar al escribir el log no devuelve un falso error de cobro confirmado. Retención inicial: 30 días, ajustable mediante `AUDIT_LOG_DAYS` y pendiente de aprobación del responsable.
- Script [sql/crear_usuario_mysql.sql](sql/crear_usuario_mysql.sql) preparado para cuenta propia, limitada a la base del gimnasio, con reducción de permisos después de migrar. Revisadas relaciones e índices; las nuevas tablas incluyen claves e índices. No se alteraron ni retiraron índices antiguos.
- `composer audit` y `npm audit`: cero vulnerabilidades informadas. No fue necesario cambiar versiones de dependencias en esta rama. Se conserva el inventario de licencias de Composer como evidencia.

### Legal — El Salvador

Se conectaron [PRIVACIDAD.md](PRIVACIDAD.md), [TERMINOS.md](TERMINOS.md), [LESIONES.md](LESIONES.md) y [DERECHOS_DATOS.md](DERECHOS_DATOS.md) a las pantallas de acceso, registro y pie de página. Los términos no imponen una exoneración absoluta por lesiones ni renuncia a derechos del consumidor.

La aceptación de nuevas cuentas es obligatoria, explícita y no premarcada. Guarda fecha y versión; archiva los cuatro textos resueltos y su SHA-256. Se verifica que la versión enviada sea la mostrada/vigente. Cambiar documentos o datos legales exige otra versión; no se inventan aceptaciones de clientes existentes. Un cliente nuevo creado por recepción acepta personalmente al entrar. Publicidad y tratamiento sensible requieren autorización separada, no esta casilla.

El formulario público de derechos crea una solicitud privada; solo el administrador consulta y tramita su estado. No se exige aceptar términos para ejercer derechos y no se ejecutan borrados automáticos de obligaciones financieras. El gimnasio debe verificar identidad, responder y conservar constancia; no se envían respuestas legales automáticamente.

Inventario personal: nombres/correos, hash de contraseña, ID Google, avatar y preferencias, roles/estado, membresías, cobros/compras y referencias, visitas/horarios, rutinas/cargas/objetivos/calificaciones/récords, agenda con entrenador, notas, solicitudes de derechos, aceptación legal y metadatos de sesión/auditoría. Rendimiento o notas que revelen salud pueden requerir protección como información sensible; la app no incorpora una historia clínica ni pide DUI. Los avatares mantienen su URL pública existente, advertida en el aviso.

Verificación normativa: el Decreto 144 se publicó el 15/11/2024 y su art. 64 fija ocho días para entrar en vigencia; de ello resulta el 23/11/2024. El texto atribuye supervisión a ACE y su art. 25 exige avisar a **ACE, FGR y titulares afectados** dentro de 72 horas desde el conocimiento, e iniciar la revisión en ese plazo. [Ley oficial](https://www.ace.gob.sv/documentos/decretos/decreto_144_proteccion_datos.pdf), [políticas oficiales](https://www.ace.gob.sv/documentos/politicas/politicas_protecciondatos.pdf).

La reforma Decreto 659 aprobada el 17/09/2026 tiene [ficha oficial](https://www.asamblea.gob.sv/leyes-y-decretos/view/7022) con referencia a DO 173, tomo 452, pero todavía indica publicación material pendiente. No se confirmó su texto consolidado ni vigencia efectiva. El abogado debe cotejarla y determinar obligaciones actuales, incluida la figura del delegado; no se deduce una exención de una noticia.

Se entregan [RESPUESTA_BRECHAS.md](RESPUESTA_BRECHAS.md), procedimiento interno con plazos y destinatarios, y [LICENCIAS.md](LICENCIAS.md), revisión de librerías, logo, fondo, imágenes y seis avatares previamente versionados. No existe prueba de titularidad de esos activos en el repositorio; convertir a WebP no otorga derechos ni anonimiza personas.

### Pendientes funcionales completados

- Gestión adicional de empleados por invitación, clientes y planes: alta, búsqueda, paginación, edición, baja y reactivación; administrador único protegido. Las bajas son lógicas, conservando operaciones anteriores.
- Relación opcional `clientes.entrenador_id` y agenda: crear, buscar, paginar, editar, completar y cancelar sesiones; no permite solapar cliente o entrenador. Clientes ven solo su agenda; entrenadores solo las sesiones propias. [GESTION.md](GESTION.md).
- Historial de ventas y asistencias: correcciones con motivo y bitácora transaccional; anulaciones sin borrar filas. Anular una venta devuelve inventario exactamente una vez, solo por administrador. No cambia el cobro original ni realiza reembolso bancario. Asistencias corregidas no pueden solaparse ni dejar dos visitas abiertas; las anuladas no inflan aforo. [HISTORIAL_OPERATIVO.md](HISTORIAL_OPERATIVO.md).
- Ventas transaccionales con bloqueo estable de productos, límite de importes compatible con DECIMAL y UUID de solicitud: repetir el mismo formulario no cobra ni descuenta stock dos veces. Formularios antiguos sin UUID siguen admitidos; no se promete idempotencia entre solicitudes antiguas independientes.
- Pausas y reanudaciones desplazan los períodos futuros pagados sin perder días ni crear superposiciones; conservan pagos y duración. No se reescriben períodos legados que ya comenzaron. El panel no pierde ingresos cobrados por cancelar acceso. [PAUSAS.md](PAUSAS.md).
- Método efectivo/tarjeta agregado a pagos de membresía con columna nullable para preservar pagos legados sin inventar su método. Se mantiene Transferencia donde ya existía en ventas.
- Estadísticas privadas por ejercicio, filtros y paginación: volumen `kg × repeticiones`; se conserva clasificación Inicial <300, Intermedio 300–599 y Avanzado ≥600. Estimación orientativa Epley `kg × (1 + min(repeticiones, 30)/30)`, no recomendación de carga segura. Estrellas subjetivas siguen independientes. [FORMULA_PROGRESO.md](FORMULA_PROGRESO.md).
- Comandos seguros para administrador inicial y datos ficticios locales, sin claves compartidas ni sobrescribir contraseñas. [DATOS_PRUEBA.md](DATOS_PRUEBA.md).
- Pantallas nuevas mantienen el layout/temas originales, validación en español, confirmaciones, mensajes y estados vacíos. Se corrigió desbordamiento móvil del modelo de solicitud legal. Se conservaron los errores personalizados, imágenes de respaldo y los flujos originales del catálogo/rutinas.
- README e instalación/actualización XAMPP documentados. No se encontraron TODO/FIXME pendientes en el código de aplicación ni el diccionario «Base de Datos Definitiva v1.2». No se inventó un esquema de máquinas físicas no existente.

## 2. Decisiones tomadas

1. **Preservar la instalación:** todos los `migrate:fresh` se ejecutaron sobre SQLite temporal/aislada, nunca sobre datos del gimnasio. `.env`, APP_KEY y base existente no se sustituyeron. Solo seis migraciones aditivas y reversibles; no se editaron migraciones antiguas.
2. **Excepciones solicitadas al comportamiento anterior:** casilla legal para nuevas cuentas, consentimiento de alta nueva por recepción, cambio de precios existentes reservado al administrador y desafío adicional solo si el administrador activa 2FA. Son cambios visibles exigidos por el encargo. El resto del flujo original conserva sus contratos.
3. **Pruebas sin ocultar cambios:** caracterización escrita antes de implementar; evidencia original conservada. Se adaptaron expectativas de registro a la aceptación explícita y de edición de precio al administrador. Una prueba nueva de pausas usaba el guard por defecto incorrecto después de alternar identidades: se corrigió a `web`, sin rebajar permisos.
4. **No borrar operaciones financieras:** bajas/anulaciones conservan filas y bitácora. No se permite editar dinero/items históricos ni simular reembolsos. Supresión legal requiere revisión humana de obligaciones de conservación.
5. **Compatibilidad de datos:** UTC y timestamps anteriores se mantienen, con agenda rotulada en esa zona; máximo original de cinco productos y un administrador se conserva. No se inventan pagos/métodos/aceptaciones pasados ni se añaden columnas de máquinas sin contexto.
6. **Seguridad compatible:** CSP conserva inline existente; avatares continúan públicos y se advierte su alcance. No se elimina material gráfico desconocido ni se reescribe Git sin verificar derechos y posibles datos personales. Son límites residuales, no certificados de seguridad.
7. **Cumplimiento honesto:** marcadores en lugar de datos legales ficticios; sin declaración de cumplimiento total, firma electrónica, certificación médica/PCI ni comprobante fiscal autorizado. Derechos y respuesta a brechas no se automatizan jurídicamente.

## 3. Resultados antes y después

| Verificación | Línea base | Cierre |
| --- | --- | --- |
| `migrate:fresh --seed --force` en base aislada | Aprobado | Aprobado |
| Suite completa | 89 pruebas / 748 aserciones / 0 fallos | 169 pruebas / 1.404 aserciones / 0 fallos / 0 omitidas |
| `route:list` | 82 rutas | 133 rutas |
| Contratos de rutas originales | Guardados | 82/82 conservan método, dominio, URI y nombre; 51 añadidas |
| Nuevas migraciones, rollback y reaplicación | No aplicable | 6/6; campos, cuentas y hashes anteriores conservados |
| `composer audit` | Dependencias de la base | 0 avisos / 0 paquetes abandonados |
| `npm audit` | Dependencias de la base | 0 vulnerabilidades |
| PHP / Pint / Blade / Vite | — | 160 archivos PHP válidos; Pint aprobado; vistas y build aprobados |
| Navegador Chrome | — | Flujos originales y nuevos, roles, CSRF real, móvil, temas y XSS aprobados; sin excepciones JavaScript |

Caracterización incluye empleados, registro/login/perfil de cliente, Google simulado verificado/no verificado, solicitudes/cobros/renovaciones, entrada/salida, creación/edición de rutinas, privacidad por ID y catálogo de ejercicios. Pruebas adicionales cubren administración, agenda, anulaciones, legal versionado, 2FA/vectores RFC, contraseñas, CSV y doble envío de venta.

La suite y `route:list` se volvieron a ejecutar después de los lotes; los archivos `docs/evidencia/*-junit.xml`, `*-rutas.json` y transcripciones lo registran. Al trabajar en paralelo, algunas ejecuciones incluyen ya pruebas de otros módulos; no son pruebas parciales presentadas como suite completa. Evidencia principal: [linea-base.txt](evidencia/linea-base.txt), [final.txt](evidencia/final.txt), [contrato-rutas.txt](evidencia/contrato-rutas.txt), [migraciones-reversibles.txt](evidencia/migraciones-reversibles.txt), [browser-original.txt](evidencia/browser-original.txt), [browser-auditoria.txt](evidencia/browser-auditoria.txt), [composer-audit.json](evidencia/composer-audit.json).

Entorno: Windows, PHP 8.2.12, Laravel 12.69.3, Composer 2.9.8, Node 24.15.0 y npm 11.12.0. Las pruebas usan SQLite; los escenarios Google/SMTP son simulados. Los navegadores usan cuentas ficticias y base temporal, no datos reales.

Límites de verificación:

- MySQL está disponible en el equipo, pero las credenciales administrativas no están disponibles (acceso denegado). No se certifican migraciones ni concurrencia real en ese motor: probar primero en una copia MySQL del gimnasio.
- Google OAuth real, SMTP/envío, dominio/certificado HTTPS, Apache público y privilegios de servidor no se verificaron sin acceso a esa instalación. No se abrió ni publicó un servidor de producción.
- Una migración antigua de septiembre presenta un problema de rollback completo en SQLite con un índice de `solicitud_id`; no se alteró el esquema histórico. Las seis nuevas sí se revirtieron y reaplicaron. No usar reset/fresh para actualizar datos reales.
- Vite avisa que `/images/gym-bg.webp` se resolverá en ejecución. El archivo existe y las pantallas cargan; no se sustituyó el fondo ni se ocultó el aviso.
- No se accedió a una base real para comprobar contenido libre antiguo, autorizaciones de fotos, copias expuestas ni derechos efectivos de titulares.

## 4. Lo que solo tú puedes completar

Esta sección es una referencia **solo para una posible adopción real**. No necesitas abogados, dominios ni datos de una empresa para la presentación estudiantil. Google real y SMTP son opcionales; la demostración local funciona con los accesos por correo y SQLite.

### 1. Datos legales y abogado — necesario antes de uso público

Abre `.env` local con un editor. Completa estas variables sin subir ese archivo a GitHub. El responsable y el abogado deben decidir valores reales, plazos por categoría, proveedores/países, menores, delegado y obligaciones comerciales/tributarias.

| Marcador | Variable privada de `.env` |
| --- | --- |
| `{{NOMBRE_RESPONSABLE}}` | `LEGAL_NOMBRE_RESPONSABLE` |
| `{{DIRECCION_RESPONSABLE}}` | `LEGAL_DIRECCION_RESPONSABLE` |
| `{{CORREO_PRIVACIDAD}}` | `LEGAL_CORREO_PRIVACIDAD` |
| `{{TELEFONO_RESPONSABLE}}` | `LEGAL_TELEFONO_RESPONSABLE` |
| `{{PERSONA_CONTACTO_PRIVACIDAD}}` | `LEGAL_PERSONA_CONTACTO` |
| `{{PROVEEDORES_Y_PAISES}}` | `LEGAL_PROVEEDORES_Y_PAISES` |
| `{{PLAZO_RETENCION}}` | `LEGAL_PLAZO_RETENCION` |
| `{{POLITICA_CANCELACIONES_Y_REEMBOLSOS}}` | `LEGAL_CANCELACIONES` |
| `{{HORARIOS_Y_SERVICIOS}}` | `LEGAL_HORARIOS_Y_SERVICIOS` |
| `{{PROCEDIMIENTO_ACCIDENTES}}` | `LEGAL_PROCEDIMIENTO_ACCIDENTES` |

Completa también `{{CONTACTO_TECNICO_BRECHAS}}` y `{{UBICACION_EXPEDIENTE_INCIDENTES}}` en el procedimiento interno; no publiques un expediente real. Cambia `LEGAL_VERSION` a una versión nueva aprobada, ejecuta `php artisan optimize:clear` y abre los cuatro enlaces del login para comprobar que no queden marcadores. Entra como administrador → **Solicitudes de datos** para revisar y responder las recibidas. Conserva respuestas por un canal privado.

Entrega al abogado los documentos de `docs`, pide comprobar la reforma 659 y las obligaciones actuales de ACE. Acredita licencias del logo/fondo/ejercicios y autorización de los seis avatares antiguos; si no existen, acuerda su sustitución y el tratamiento del historial público. No puedo inventar autorizaciones ni decidir si esas imágenes representan personas reales.

### 2. Cuenta MySQL propia — requiere tu acceso administrador

En XAMPP pulsa **Start → MySQL** y **Admin** para abrir phpMyAdmin. Accede con tu cuenta administrativa. Selecciona la base → **Exportar → Personalizado → SQL → Exportar** para respaldar todas las tablas. Después abre **SQL**, copia `docs/sql/crear_usuario_mysql.sql`, reemplaza `{{BASE_DATOS_EXISTENTE}}` y `{{CONTRASENA_MYSQL_SEGURA}}` (clave aleatoria privada), y pulsa **Continuar**. Esas credenciales nunca van al repositorio.

En `.env` configura MySQL con `DB_USERNAME=alfa_fitness_app`, contraseña y nombre de base correctos. Ejecuta `php artisan optimize:clear` y `php artisan migrate:status`; debe listar migraciones, no `Access denied`. Aplica/verifica la actualización primero en una copia de la base. Al terminar, ejecuta las líneas REVOKE indicadas en el script para el servidor público. No cambies contraseñas de usuarios ajenos ni uses root en la app.

### 3. Credenciales antiguas y Google — solo rotar si corresponde

Si conservas cuentas del seeder antiguo, entra con cada cuenta legítima → **Configuración → Seguridad**, escribe la contraseña actual, una nueva propia y su confirmación, y pulsa **Actualizar contraseña**. Para empleados sin contraseña establecida usa el enlace privado de invitación; el administrador puede reenviarlo desde **Gestión administrativa**. No hay una clave común nueva.

No se detectaron secretos Google reales en Git que obliguen a rotar. Si los publicaste por otro medio: abre [Google Cloud Console](https://console.cloud.google.com/apis/credentials), selecciona tu proyecto → **APIs y servicios → Credenciales** → tu **ID de cliente OAuth 2.0** → **Editar** → **Restablecer secreto / Reset Secret**. Guarda el nuevo valor únicamente en `GOOGLE_CLIENT_SECRET` de `.env`; conserva el client ID. Las interfaces pueden mostrar el cliente en **Google Auth Platform → Clientes**. [Instrucciones oficiales de restablecimiento](https://developers.google.com/workspace/guides/manage-credentials).

Revisa en el cliente OAuth las **URI de redireccionamiento autorizadas**: la URL real debe acabar en `/cliente/google/callback`, coincidir exactamente con `GOOGLE_REDIRECT_URI` y usar HTTPS en producción. Guarda, ejecuta `php artisan optimize:clear` y prueba **Continuar con Google** con una cuenta real. No elimines el cliente para rotar su secreto ni pegues claves aquí/GitHub. Las contraseñas SMTP/BD también se rotan si se expusieron fuera de Git, actualizando solo `.env` privado.

### 4. Servidor, correo y acceso inicial

Si todavía no hay administrador, ejecuta `php artisan alpha:crear-admin` y escribe tus datos privados; no sustituye uno existente. Para mayor protección, entra → **Verificación en dos pasos**, sigue [DOS_FACTORES.md](DOS_FACTORES.md) y guarda los códigos fuera del equipo. Es opcional, no se activó por ti.

Para uso por internet necesitas tu dominio, certificado, alojamiento, SMTP y respaldos privados. Configura Apache hacia `public`, TLS válido y correo del gimnasio; prueba una invitación y un comprobante real. Solo después usa `APP_ENV=production`, `APP_DEBUG=false` y `APP_URL=https://TU_DOMINIO`. No expongas XAMPP ni `artisan serve` como servidor público. Si no administras servidores, entrega [INSTALACION_XAMPP.md](INSTALACION_XAMPP.md) y este informe a un técnico con acceso a esa instalación.

## 5. Actualizar tu instalación sin perder datos

Primero respalda la base completa desde phpMyAdmin, `.env` y las imágenes subidas, fuera de la raíz pública. Cierra temporalmente el uso del gimnasio durante las migraciones. Abre PowerShell en la carpeta del proyecto, no en `public`:

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

Si no existe la rama local: `git switch --track origin/auditoria-y-cierre`. Si hay cambios locales, consérvalos y resuelve el solapamiento; no uses `reset --hard`. Si un comando falla, no continúes a ciegas: guarda el mensaje sin secretos, mantiene el respaldo y corrige la causa. El modo mantenimiento no reemplaza respaldos.

Debe verse `built` en Vite, `DONE` en migraciones nuevas y las rutas originales más `/gestion`, `/legal` y `/administracion/dos-factores`. La migración no recrea la base ni cambia claves; el login conserva Personal/Clientes. Con dependencias de desarrollo instaladas, `php artisan test` debe mostrar **173 passed (1440 assertions)** y `composer audit` ninguna vulnerabilidad. Para adoptar MySQL en una instalación real, revisa primero los flujos con una cuenta de cada rol en una copia de prueba.

No ejecutar `migrate:fresh`, `migrate:reset`, `db:wipe`, `key:generate` ni `composer setup` como actualización. No ejecutar seeders ni `alpha:datos-prueba` sobre datos reales. Una reversión de migración elimina sus columnas/tablas nuevas, incluyendo evidencia legal/agenda/bitácoras: exportarla antes; para revertir código se prefieren commits individuales y restauración planificada.

El ZIP de actualización contiene únicamente archivos nuevos/modificados respecto a la base indicada, con estructura relativa para la raíz. No incluye `.env`, vendor, node_modules, bases, archivos personales nuevos ni secretos. Es una actualización, no un proyecto completo. Descomprímelo primero en una copia del proyecto; conserva `.env` y ejecuta los mismos comandos de dependencias/build/migrate/limpieza. No hace falta renombrar tablas, rutas o campos existentes.

Los commits de la rama separan línea base, seguridad, legal, 2FA, permisos, membresías, gestión, historial, progreso, ventas e integración. No se mezclan con `main`; la integración futura debe hacerse tras validar tu copia de instalación.
