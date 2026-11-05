# Revisión integral y correcciones — 5 de noviembre de 2026

## Revisión objetivo

Base descargada: `origin/codex/revision-alpha-fitness-2026-11-05`, commit `9de486852362480cba82ee70c94be9e317d3f985`.
Rama de entrega: `codex/review-updated-2026-11-05`, base anterior más las correcciones descritas y las pruebas de regresión. La rama local anterior se conserva.

Estado: **VERIFICADO_CON_LIMITES**. Once defectos confirmados corregidos; suite integrada sin errores ni fallos y recorridos de navegador correctos. Los límites de servicios externos y despliegue se detallan abajo. Las comprobaciones corresponden a las fuentes identificadas por la huella registrada en este informe.

## Alcance y comprensión

Alpha Fitness es una aplicación Laravel 12, Blade y Vite para administrar un gimnasio y registrar entrenamiento. Se revisaron contratos de los roles Administrador, Secretaría, Entrenador y Cliente; autenticación, cuentas, privacidad, asistencia y cobros por entrada, membresías y pausas, ventas e inventario, reportes financieros, catálogo, rutinas, sesiones y progreso. También se inspeccionaron migraciones, exportación/importación, configuración de despliegue y herramientas de verificación.

Criterios: corregir causas reproducibles con cambios mínimos; mantener aislamiento entre roles, historial y reglas de negocio; demostrar las regresiones antes y después; comprobar instalación desde los lockfiles, construcción, pruebas, vistas e interfaz. No se modifican dependencias declaradas, datos reales ni configuración de producción.

## Hallazgos corregidos

Todos los siguientes hallazgos están CONFIRMADOS mediante reproducción. Las severidades reflejan su impacto; no son una certificación de ausencia de otros errores.

| ID | Severidad | Defecto y corrección | Evidencia de regresión |
| --- | --- | --- | --- |
| H-SEC-01 | S2 | Bcrypt ignoraba bytes posteriores al límite o al carácter NUL. La regla compartida rechaza esas contraseñas en registro, acceso, recuperación, cambios, vinculación Google y alta inicial del administrador; admite el límite válido. | `RevisionIntegralSeguridadTest`, `CreateAdminCommandTest` |
| H-SEC-02 | S2 | Restaurar una cuenta podía rehabilitar cookies persistentes y sesiones anteriores al baneo. El baneo rota el token e incrementa una versión de acceso; las sesiones antiguas se rechazan sin invalidar las nuevas. | `RevisionIntegralSeguridadTest`; migración comprobada con cuentas existentes |
| H-CORE-01 | S2 | Comparar DATE con datetime ignoraba el último día de pausas almacenadas como `Y-m-d` en SQLite. Se comparan fechas de calendario. | `RevisionIntegralNegocioTest` |
| H-CORE-02 | S2 | Pausar y reanudar varias veces hoy podía acreditar el mismo día más de una vez. Se impide solapar días ya contabilizados al solicitar y aprobar; se conservan anulaciones futuras de cero días y pausas posteriores. | `RevisionIntegralNegocioTest` |
| H-HOME-01 | S2 | Inicio omitía membresías que vencen hoy cuando su DATE era canónica. Se incluyen en el contador y los avisos de los tres perfiles pertinentes. | `RevisionIntegralDashboardTest`, tres perfiles |
| H-UI-01 | S2 | El descanso medía callbacks y se atrasaba en segundo plano. Ahora calcula el tiempo restante desde el vencimiento, conserva la pausa y respeta cero segundos. | `revision_integral_frontend_check.cjs`, `training_edge_check.cjs` |
| H-UI-02 | S2 | Reconstruir el constructor habilitaba un campo cuyo guardado seguía pendiente. Su bloqueo sobrevive al render y termina cuando concluye la petición. | Regresión Node del constructor |
| H-UI-03 | S3 | El buscador prometía buscar por músculo, pero solo consultaba nombres. Incluye nombre, grupo y subgrupo conservando el filtro y la restricción de ejercicios activos. | `RevisionIntegralEntrenamientoTest` |
| H-UI-04 | S2 | Una gráfica unía pesos de ejercicios distintos como evolución de fuerza. Solo compara un ejercicio y solicita filtrar cuando la muestra es mixta; añade nombre y descripción accesibles al SVG. | Regresiones de cliente y entrenador |
| H-UI-05 | S2 | Empates de fecha seleccionaban registros antiguos como recientes. Se desempata por ID en ambos perfiles y se conserva la representación cronológica. | Regresiones con diez registros de la misma fecha |
| H-UI-06 | S2 | A 768 píxeles, la vista previa de apariencia comprimía el texto hasta mostrarlo casi letra por línea. Sus dos columnas se activan ahora únicamente en pantallas amplias. | Captura anterior de Cliente a 768 píxeles y revisión posterior a la compilación |

## Verificación

Las pruebas de lógica nuevas se ejecutaron primero en rojo y después en verde. PHPUnit usa SQLite en memoria; las ejecuciones posteriores aíslan vistas compiladas y caché de configuración en directorios temporales únicos.

| Comprobación | Resultado final observado |
| --- | --- |
| Instalación desde `composer.lock` / `package-lock.json` | Código 0; no se actualizaron los lockfiles |
| PHPUnit completo, `--do-not-cache-result --log-junit storage/logs/revision-integral-verificado.xml` | **423 casos, 3159 aserciones, 421 aprobados y 2 omitidos; 0 errores/fallos; código 0**, 5 min 14,741 s |
| `npm run build` después del último cambio de interfaz | Código 0, Vite 7.3.7, CSS de 98,94 kB; manifest nuevo |
| `php artisan view:cache` con rutas temporales | Código 0 después de la última modificación de vistas |
| Pint: conjunto PHP y comprobación final de fixtures | Código 0; un intento intermedio sin memoria se repitió después de cerrar el navegador de QA |
| `node tools/frontend_regression_check.cjs` | 10 grupos correctos, código 0 |
| `node tools/appearance_regression_check.cjs` | 4 grupos correctos, código 0 |
| `node --test tools/revision_integral_frontend_check.cjs tools/training_edge_check.cjs` | 8 pruebas correctas, ninguna omitida, código 0 |
| `composer audit` / `npm audit` | Sin vulnerabilidades conocidas reportadas, ambos código 0 |
| `node tools/second_review_browser_check.cjs settings forms matrix` | Tres suites correctas: cuatro roles, configuración a 390/768/1440 px, registro, membresías, módulos, permisos y ambos diseños claro/oscuro; sin excepciones JavaScript |
| Vista previa después de la corrección | Recorrido focal de Cliente en **320/390/768/1440 px, claro y oscuro: ocho combinaciones correctas**, sin desbordamiento, texto comprimido ni excepciones JavaScript; código 0. Captura nueva revisada visualmente. La repetición amplia de Settings se interrumpió para reducir memoria y fue sustituida por esta comprobación focal del cambio |
| `node tools/entrada_diaria_browser_check.cjs` | Código 0: confirmación obligatoria, USD 2 por entrada, reintento sin duplicar cobros, salida gratuita, reingreso con cobro nuevo, último día de membresía incluido, privacidad por rol, total de entradas en Inicio y exportación PDF; sin desbordamiento móvil |

Registro privado de fuentes y lockfiles: `output/revision-integral/source-snapshot.json`, 36 archivos, SHA-256 agregado `ef47d40dd355f85acdc340d446b115a14e806515907958a2809ae7f02a8855b5`. Las capturas comparativas de apariencia están en `output/revision-integral/appearance-before-768.png` y `appearance-after-768.png`. No contienen datos reales.

La primera pasada de 390 casos coincidió con instalación y cambios en curso. La siguiente, de 423 casos, permitió separar dos causas: dependencias de OneDrive que no se podían leer y fixtures que alternaban cuentas sin indicar el guard del empleado. Se conservan ambas salidas fallidas; no se desactivaron pruebas ni cambiaron sus expectativas de autorización.

La lectura nativa y PHP confirmó archivos sin conexión y errores del proveedor de nube, con unos 25 MB libres en C:. Se reinstalaron únicamente `egulias/email-validator` 4.0.4 y `league/mime-type-detection` 1.17.0 desde las versiones del lockfile, sin actualizarlo. Después se comprobó la lectura completa y carga de las clases afectadas. Se eliminaron únicamente 1,5 MB de cachés temporales creadas por esta revisión; no se borraron archivos personales. Se avisó del espacio insuficiente al usuario. Fijar archivos como disponibles sin conexión sigue el mecanismo de [Microsoft](https://learn.microsoft.com/en-us/sharepoint/files-on-demand-windows); en este equipo fue necesario reinstalar esos dos paquetes.

`git fsck --no-reflogs --no-dangling` terminó con código 0.

El usuario liberó espacio durante la revisión; la comprobación siguiente encontró aproximadamente 500 MB disponibles. Se serializaron las comprobaciones finales después de un error de paginación de Windows. La falta de espacio no se resolvió eliminando datos personales ni alterando límites de memoria para ocultar fallos.

La suite completa se ejecutó desde la raíz del repositorio con PHP `C:/xampp/php/php.exe` 8.2.12 y PHPUnit 11.5.56. Para reproducir el aislamiento en PowerShell:

```powershell
$qa = Join-Path $env:TEMP ('alpha-verificacion-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $qa | Out-Null
$env:VIEW_COMPILED_PATH = $qa
$env:APP_CONFIG_CACHE = Join-Path $qa 'config.php'
& 'C:/xampp/php/php.exe' vendor/bin/phpunit --do-not-cache-result
```

Los registros detallados están en `storage/logs/revision-integral-*`; contienen únicamente pruebas y deben permanecer fuera de Git. Las dos omisiones corresponden a `RenderBootstrapPostgresTest`: importación con claves/secuencias e importación fallida con rollback, que requieren PostgreSQL externo.

## Aplicación de los cambios

La nueva migración `2026_11_05_120000_add_access_version_to_clientes` agrega una columna con valor inicial cero, sin borrar cuentas. Antes de iniciar esta versión sobre una instalación existente se debe respaldar la base y ejecutar `php artisan migrate` (en este equipo: `& 'C:/xampp/php/php.exe' artisan migrate`). No se aplicó a la base real durante esta auditoría. Su aplicación y reversión con cuentas existentes se comprobaron en SQLite desechable.

Las contraseñas antiguas que excedan los 72 bytes o contengan NUL deben restablecerse: ya no se acepta su interpretación parcial. Las cuentas sin ese caso conservan sus contraseñas. La migración mantiene las sesiones anteriores con versión cero hasta que su cuenta sea baneada.

## Límites y pendientes externos

- Sin despliegue, integración en `main` ni escritura en producción.
- SQLite y navegador emulado no acreditan concurrencia real en PostgreSQL/MySQL ni funcionamiento en un teléfono físico.
- Sin entrega SMTP real, OAuth con credenciales reales, prueba de carga ni construcción Docker en este equipo.
- La revisión de accesibilidad incluye semántica, teclado y comprobaciones de interfaz; no certifica WCAG ni sustituye pruebas con lectores de pantalla. No se instaló axe.
- No se dispone de investigación con usuarios, analítica de uso ni mediciones de rendimiento en dispositivos. Por ello no se asigna una puntuación global de producto que sugiera cobertura completa.
- Se conservan las decisiones funcionales del proyecto: cobros presenciales, cancelación sin reembolso automático y relación entrenador-cliente mediante asignaciones de rutinas.
