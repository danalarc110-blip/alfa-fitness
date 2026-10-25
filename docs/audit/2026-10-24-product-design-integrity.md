# Auditoría de Alfa Fitness · 24 de octubre de 2026

> Actualización posterior: se implementaron correcciones para los tres hallazgos. Consulta la [verificación de las correcciones](2026-10-24-correcciones.md). El contenido siguiente conserva el diagnóstico anterior a esos cambios.

## Resultado

La habilidad `product-design-integrity-audit` quedó instalada y fue aplicada en modo **AUDITAR**. La instalación está verificada; la revisión del producto tiene límites explícitos. El producto requiere correcciones: se confirmaron tres hallazgos S2 en inventario, temporizador y accesibilidad del modal de ejercicios. No se implementaron cambios de aplicación.

Las 264 pruebas existentes pasan, con 2,097 aserciones. También pasan la sintaxis de 167 archivos PHP, la compilación y 20 comprobaciones de JavaScript. Estas pruebas no cubren los tres casos encontrados. No se recomienda declarar el producto listo para producción mientras una edición de metadatos pueda sobrescribir existencias vendidas.

Los siguientes cambios prioritarios son: proteger actualizaciones de stock antiguas, medir el descanso por tiempo transcurrido e integrar los modales de ejercicios con la gestión de foco y etiquetas. La revisión estática de seguridad no encontró fallos S0–S2 confirmados en su alcance; esto no certifica el despliegue.

## Instalación y alcance

- Origen: `%USERPROFILE%/Downloads/SKILL.md`.
- Instalación personal: `%USERPROFILE%/.agents/skills/product-design-integrity-audit/SKILL.md`.
- Copia idéntica, SHA-256: `9D7C4865A1F8EC1BF68381560A6E9CC6715F1A342D5933B4178818EE57419986`.
- Metadatos comprobados: nombre de 30 caracteres y descripción de 947 caracteres; archivo autónomo, sin recursos locales adicionales referenciados.
- Se aplicó el archivo completo en esta sesión. La ubicación personal corresponde a la [documentación oficial de OpenAI](https://learn.chatgpt.com/docs/build-skills), consultada el 24 de octubre de 2026. La habilidad estará disponible para invocarla en el próximo turno; si no aparece en el selector, la documentación indica reiniciar Codex.

Se revisaron fuentes, contratos y pruebas existentes de autenticación, permisos, rutinas, progreso, asistencia, membresías, productos y ventas. Se ejecutaron pruebas locales y se recorrieron pantallas con usuarios y SQLite desechables. Se preservaron los cambios previos del usuario. No se instalaron dependencias ni se usaron cuentas, correo, Google o bases de datos reales.

La base es `06d31c2984133a9596bff0db21935399eb8c658c` más el árbol de trabajo previo, con numerosos archivos modificados y nuevos. QA calculó la misma huella antes y después de sus comprobaciones: **228 archivos**, SHA-256 `168e06846a6be3570879bcd9cfae36d57fda4e762466f7e33f47512ccc676416`. El [snapshot](evidence-2026-10-24/snapshot.json) conserva estado Git y hashes individuales relevantes. El SHA base por sí solo no identifica lo auditado.

Participaron el líder, QA, auditor de lógica y especialista de seguridad. La revisión de UI del líder se contrastó con QA. No se activaron agentes de manuales ni diagramas; este archivo registra el resultado técnico de la auditoría.

## Ficha del producto

| Campo | Evidencia o estado |
|---|---|
| Propósito | Gestionar entrenamiento y operaciones de un gimnasio. |
| Plataforma | Aplicación web Laravel 12, Blade, Vite 7 y Tailwind 4. PHP requerido: ^8.2. |
| Etapa | [?] No se confirmó si es una instalación educativa, piloto o producción. Los pagos de venta son simulados por diseño. |
| Roles | Cliente, Administrador, Secretaria y Entrenador, con permisos diferentes. |
| Tareas críticas | Acceder según rol; crear/asignar rutinas y registrar sesiones; gestionar membresías/asistencia; vender y conservar inventario; consultar progreso e informes. |
| Datos | Identidad, acceso, actividad de entrenamiento, asistencia y registros financieros. No se inspeccionaron datos reales. |
| Integraciones | Google, correo, PDF y Excel. Las dos primeras no se probaron con servicios reales. No hay pasarela bancaria de ventas. |
| Riesgos principales | Pérdida de integridad del inventario, acceso a información ajena y pérdida o inconsistencia del historial. |
| Entorno comprobado | Windows, PHP 8.2.12, Node 24.15.0, Vite 7.3.7 y PHPUnit 11.5.56. |
| Investigación y métricas | [?] Sin entrevistas, analítica de uso ni umbrales de éxito aportados para esta revisión. |

## Hallazgos confirmados

### LOG-2 · Un formulario antiguo devuelve al stock unidades ya vendidas

**S2 Mayor · CONFIRMADO [V] · L7 lógica/datos · Esfuerzo M.** Afecta al administrador y a las ventas de recepción. Es la primera prioridad por su efecto en inventario.

- **Dónde:** `resources/views/productos/index.blade.php:168` y `app/Http/Controllers/ProductoController.php:70`.
- **Hecho:** el formulario precarga existencias y las envía también al cambiar únicamente nombre/precio. El controlador bloquea la fila actual, pero reemplaza el stock con el valor antiguo sin comprobar una versión.
- **Reproducción real:** administrador consulta `GET /productos` con stock 10 → secretaria registra una venta de dos unidades por `POST /ventas` → administrador envía `PUT /productos/1` con nombre nuevo y el stock 10 conservado en el formulario. Respuestas: 200, 302 y 302, sin errores de validación.
- **Resultado:** `stockSequence:[10,8,10]`, `sales:1`, `saleDetails:1`, `detailQuantity:2`. La venta permanece y las unidades reaparecen en inventario. Se ejecutó Laravel con SQLite en memoria y usuarios sintéticos; código de salida 0.
- **Impacto:** puede permitirse vender más unidades de las físicamente disponibles. No requiere solicitudes simultáneas: basta con guardar un formulario abierto antes de la venta.
- **Corrección recomendada:** separar el ajuste explícito de existencias de la edición de metadatos, o comprobar versión/stock original dentro del bloqueo y rechazar el conflicto.
- **Criterio de verificación:** abrir con 10, vender 2 y guardar un nombre nuevo debe conservar 8 o informar de conflicto; nunca restablecer 10 silenciosamente.

Harness de reproducción conservado temporalmente: `%TEMP%/alfa-logic-audit-49143c1f960a465aa8a502e251b932d6/stock-stale-form.php`, ejecutado con `C:/xampp/php/php.exe` desde el repositorio. Una primera ejecución falló exclusivamente al cerrar PHPUnit fuera de su runner; el cierre del harness se corrigió y la ejecución final terminó en 0. No se cambió código de aplicación.

### LOG-1 · El descanso no se reconcilia tras suspender callbacks

**S2 Mayor · CONFIRMADO [V] · L7/L10 · Esfuerzo S.** Afecta a quien usa el temporizador durante el entrenamiento.

- **Dónde:** `resources/views/entrenamientos/entrenar.blade.php:313`.
- **Hecho:** `setInterval` resta un segundo por callback; no calcula cuánto tiempo transcurrió ni reconcilia al recuperar visibilidad.
- **Prueba:** Node VM ejecutó el código del temporizador extraído de la vista con reloj controlado: iniciar 60 segundos, avanzar 90 sin callbacks y entregar uno.
- **Resultado, salida 0:** `actualRemainingSeconds:59`, `display:"00:59"`, `state:"Descansando"`, `alerts:0`; el restante esperado era 0.
- **Impacto:** si el navegador suspende la ejecución, el descanso mostrado se prolonga y el aviso llega tarde.
- **Corrección recomendada:** calcular el restante desde un vencimiento y reconciliar al reanudar; conservar correctamente el restante al pausar.
- **Criterio de verificación:** tras simular 90 segundos de suspensión de un descanso de 60, mostrar fin y emitir una sola alerta; comprobar también pausa/reanudación.
- **Límite:** se probó la suspensión simulada del algoritmo. No se probó un teléfono real con pantalla bloqueada; no se promete una notificación mientras el sistema suspenda la web.

### UI-1 · El modal de ejercicios no gestiona foco y etiquetas

**S2 Mayor · CONFIRMADO [V] · L3/L6 · Esfuerzo S.** Afecta a personal que crea ejercicios con teclado o tecnologías de apoyo.

- **Dónde:** `resources/views/ejercicios/index.blade.php:17` y `:166`; helper disponible en `resources/js/animations.js:40`.
- **Hecho:** el botón abre el modal quitando `hidden`. No registra el modal con el helper que gestiona foco, Escape y semántica. Las cinco etiquetas del formulario son hermanas de los inputs y no están asociadas mediante `for`/`id` o envoltura.
- **Prueba en Chrome:** clic real en Nuevo Ejercicio → `visible:true`, `role:null`, `ariaModal:null`, `focusInside:false`; después de Tab, `focusInsideAfterTab:false`; después de Escape, `closedByEscape:false`.
- **Etiquetas:** cinco inputs tienen `labels.length=0`. Los dos selectores de archivo se exponen como «Seleccionar archivo», sin identificar foto frente a músculos. Los campos de texto pueden recibir un nombre de respaldo desde su placeholder; no se afirma que todos carezcan de nombre accesible.
- **Impacto:** el foco permanece en el fondo y los dos uploads no comunican su propósito programáticamente.
- **Corrección recomendada:** usar el helper compartido para abrir/cerrar y restaurar foco, asignar título del diálogo y asociar las etiquetas. Aplicar la misma revisión al modal de edición.
- **Criterio de verificación:** foco dentro al abrir, Tab/Shift+Tab contenidos, Escape cierra, foco vuelve al disparador y cada campo expone un nombre propio.
- **Evidencia:** [resultado de navegador](evidence-2026-10-24/followup-result.json) y [captura](evidence-2026-10-24/exercise-modal.png). No se ejecutó lector de pantalla real ni auditoría axe.

Se contrastó el hallazgo con [WCAG 2.2](https://www.w3.org/TR/WCAG22/) y el [patrón de diálogo modal de WAI-ARIA](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/), consultados el 24 de octubre de 2026. El informe no declara conformidad WCAG completa.

## Comprobaciones ejecutadas

Todos los comandos se ejecutaron desde la raíz del repositorio, salvo los scripts temporales indicados. Se inspeccionaron sus efectos antes de ejecutarlos.

| Comprobación | Resultado y alcance |
|---|---|
| `C:/xampp/php/php.exe -l <archivo>` | 167 archivos PHP, 0 fallos; salida 0. |
| `node tools/frontend_regression_check.cjs` | 8 grupos aprobados; salida 0. |
| `node tools/daily_frontend_check.cjs` | 12 escenarios aprobados; salida 0. |
| `npm run build -- --outDir <TEMP>` | 8 módulos compilados con Vite 7.3.7; salida 0. No reemplazó el build existente. |
| `C:/xampp/php/php.exe vendor/bin/phpunit --do-not-cache-result --colors=never` | **264 pruebas, 2,097 aserciones, salida 0**, 06:18.579, 84 MB; sin omitidos reportados. |
| Browser harness con Puppeteer local y Chrome | Login y pantallas de cliente en 320/390/1440 px; inicio oscuro, texto al 200 %, menú móvil con Escape y creación de ejercicio como administrador. Salida 0. |
| Stock antiguo y temporizador | Dos reproducciones adicionales confirmaron defectos que la suite existente no detecta. Salida 0 en ambas. |

PHPUnit se ejecutó con `APP_ENV=testing`, SQLite `:memory:`, `DB_URL` vacío, correo/caché/sesiones `array`, cola `sync` y logs descartados. No se ejecutaron migraciones contra la base real.

El build aislado y los assets existentes de `public/build` coinciden byte por byte:

- CSS `app-CGClRAYr.css`: `5f26c70c6bf1477b4498e53e3b31ced8c6978300c1647411f29b8af2332786e6`.
- JS `app-Bt-2RQPB.js`: `f6271523809ed6aeca2cfeda0d583d7847799130f43cd51731a5a0eae5f57bd0`.

El navegador consumió esa compilación actual. El warning Vite de `/images/gym-bg.webp` se descartó como defecto: el archivo existe y respondió HTTP 200.

## UI, estados y puntuación

Las vistas de cliente comprobadas fueron inicio, rutinas, ejercicios, membresías, progreso, configuración y `/mi-asistencia`. Las peticiones a las rutas correctas respondieron 200 y no hubo desbordamiento horizontal global a 320, 390 y 1440 px. El inicio oscuro conservó el diseño tras guardar apariencia; a 200 % de tamaño de fuente no hubo desbordamiento global. El menú móvil abrió con `aria-expanded=true` y Escape lo cerró con `false`. No se registraron excepciones JavaScript en el primer recorrido.

El primer harness consultó por inferencia `/cliente/asistencia`, que devolvió 404. Se comprobó `routes/web.php:60`, se repitió la prueba con `/mi-asistencia` y pasó en los tres tamaños. Es un error del harness, no un fallo de la aplicación. Los JSON originales se conservan sin ocultarlo.

El harness usó un directorio público temporal para leer el manifest del build. Esto puede activar fallbacks de imágenes en código que comprueba existencia con `public_path`; por eso las miniaturas ausentes de ejercicios en la captura no se atribuyen al producto. No se evaluó la integridad de todos los medios del catálogo.

| Dimensión de la rúbrica | Evaluación de la muestra |
|---|---|
| D1 Propósito / D2 Jerarquía / D3 Consistencia | 3/4: tareas y navegación distinguibles, composición coherente entre inicio y acceso. |
| D4 Usabilidad | 2/4: modal de ejercicio con barreras de teclado. No se midieron tiempos de tarea con usuarios. |
| D5 Estados y límites | 2/4: vacíos útiles y errores cubiertos por la suite; quedan stock antiguo y suspensión sin resolver. |
| D6 Accesibilidad | 2/4 provisional: idioma, skip link, menú y etiquetas presentes en varias vistas; modal con deficiencias confirmadas. Sin certificación completa. |
| D7 Contenido / D8 Identidad | 3/4 en pantallas vistas: acciones comprensibles e identidad conservada. Sin investigación de reconocimiento de marca. |
| D9 Retroalimentación | 2/4: apariencia comunica guardado; el descanso puede avisar tarde. |
| D10 Rendimiento y fiabilidad | No puntuado: se comprobó ejecución, no Core Web Vitals, red lenta ni dispositivos reales. |
| D11 Detalle | 3/4 en muestra visual; falta cobertura completa de roles y estados. |

**Sin puntaje global:** una dimensión no medida y una revisión parcial de accesibilidad no permiten presentar honestamente una cifra sobre todo el producto. La integridad de inventario pendiente impide usar el resultado parcial como autorización de lanzamiento.

## Invariantes y seguridad

| Invariante | Evidencia y resultado |
|---|---|
| La venta calcula total en centavos y el reintento no descuenta dos veces | `VentaController.php:145`, transacción, locks ordenados e índice UUID; pruebas de integridad existentes aprobadas. |
| Editar metadatos no repone existencias vendidas | **Incumple: LOG-2**, reproducción aislada 10 → 8 → 10. |
| Una activación no cobra dos veces y los períodos no se solapan | Bloqueos y validaciones de `MembresiaController.php`; pruebas existentes aprobadas. |
| La sesión corresponde a su usuario/rutina y su UUID no duplica registros | `RutinaController.php:517`, propiedad y unicidad; pruebas existentes aprobadas. |
| El descanso representa el tiempo transcurrido | **Incumple: LOG-1**, suspensión simulada. |
| Los datos de cada rol están acotados por permisos | Matriz central, guards y comprobación de propietario/vínculo revisados estáticamente, con suite de permisos aprobada. |

Se encontraron defensas explícitas: permisos centrales que rechazan roles desconocidos/inactivos; guards y brokers separados; propiedad de rutinas; progreso del entrenador condicionado a vínculo por ID; Google con estado y correo verificado; vinculación local con contraseña; revocación por versión de acceso de clientes; imágenes recodificadas; campos de tarjeta prohibidos y excluidos del flash de validación.

Contra-revisión: no se denunciaron como defectos la asistencia sin membresía vigente, la asignación de un cliente nuevo por entrenador ni el descuento de stock en ventas simuladas: son contratos deliberados. Invalidar definitivamente sesiones de personal al desactivar/reactivar es una propuesta de endurecimiento, no un fallo confirmado bajo el contrato actual.

## Prioridades y límites

1. **Primero:** resolver LOG-2 y añadir regresión de formulario antiguo frente a venta intermedia. Reejecutar ventas/productos y suite pertinente.
2. **Después:** resolver LOG-1 y UI-1 con pruebas de suspensión, pausa, teclado y etiquetas. El helper de modales ya existe, lo que reduce el alcance del cambio UI.
3. **Antes de publicar:** comprobar despliegue real, HTTPS, retención y recuperación, Google/correo, MySQL y concurrencia, dispositivos/red, contraste y lector de pantalla en flujos críticos.

No se alcanzó el techo de pulido porque quedan fallos funcionales. No se recomienda un rediseño visual general a partir de esta evidencia. Conservar paleta, identidad, jerarquía del inicio y navegación que ya se comprobaron.

No se ejecutaron auditorías de dependencias por red, escáner de secretos, axe, Lighthouse, lector de pantalla, pruebas en teléfono real, concurrencia MySQL ni recuperación de respaldos. No se hicieron benchmark competitivo, entrevistas ni evaluación legal de un mercado. IA, wearables, reservas de clases y pagos bancarios no se evaluaron como funciones presentes. No se generaron diagramas nuevos.

La auditoría concluye con evidencia parcial explícita; los tres defectos quedan abiertos. Cualquier corrección posterior debe revalidar las comprobaciones afectadas sobre el nuevo snapshot.

## Evidencia conservada

- [Recorrido inicial de navegador](evidence-2026-10-24/result.json).
- [Asistencia corregida y modal de ejercicios](evidence-2026-10-24/followup-result.json).
- [Snapshot de fuentes y estado Git](evidence-2026-10-24/snapshot.json).
- [Acceso escritorio](evidence-2026-10-24/login-1440.png), [inicio móvil](evidence-2026-10-24/dashboard-320.png), [inicio oscuro](evidence-2026-10-24/dashboard-dark.png) y [modal](evidence-2026-10-24/exercise-modal.png).

Las capturas contienen únicamente cuentas sintéticas de auditoría. Los secretos efímeros de fixtures no se copiaron al informe ni a su carpeta de evidencia.
