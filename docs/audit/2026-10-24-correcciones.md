# Correcciones verificadas · 24 de octubre de 2026

Se corrigieron los tres hallazgos de la auditoría: LOG-2 (inventario), LOG-1 (descanso) y UI-1 (modales). La publicación contiene exclusivamente las correcciones, sus regresiones y esta evidencia; los cambios anteriores del árbol de trabajo se conservaron sin incorporarlos al commit.

## Comportamiento corregido

| Hallazgo | Corrección | Criterio comprobado |
|---|---|---|
| LOG-2 | El formulario conserva `stock_original`. Bajo bloqueo, editar metadatos preserva el stock actual. Un ajuste explícito con una base antigua se rechaza antes de escribir metadatos. | Abrir con 10, vender 2 y guardar el nombre conserva **8**. Un ajuste antiguo a 15 se rechaza. Secretaría no puede enviar el stock original. |
| LOG-1 | El cronómetro calcula el restante desde un vencimiento; sincroniza en callbacks, `visibilitychange` y `pageshow`. Pausa conserva milisegundos; fin/reinicio cancelan el vencimiento. | Descanso de 60 segundos tras 90 segundos de suspensión simulada: **00:00 y una sola alerta**. Pausa, reinicio y doble inicio cubiertos. |
| UI-1 | Alta y edición usan los helpers compartidos de diálogo. Ambos tienen título asociado, cierres nombrados y cinco etiquetas conectadas a sus controles. | Foco dentro, Tab/Shift+Tab contenidos, Escape cierra, foco vuelve al disparador y diez etiquetas asociadas. Sin desbordamiento global del modal a 320 px. |

El cambio de contrato del formulario de administrador exige `stock_original` en `PUT /productos/{id}`. No hay migraciones nuevas ni cambios de permisos. Los tests de ajuste manual existentes conservan sus aserciones y ahora envían el valor original.

## Evidencia de regresión

Antes de corregir producción se ejecutaron las pruebas nuevas:

- Stock: **6 tests, 22 aserciones, 5 fallos**, salida 1. Se reprodujeron la reposición de stock vendido, el ajuste antiguo aceptado y la falta de validación del valor original.
- Temporizador/modales: **1 grupo aprobado, 10 fallos y 0 errores del harness**, salida 1, sobre copias exactas anteriores de las vistas.

Después de la integración, desde la raíz del proyecto:

| Comando o comprobación | Resultado |
|---|---|
| `php vendor/bin/phpunit --do-not-cache-result --colors=never` | **270 tests, 2,133 aserciones**, 0 errores/fallos/omitidos, salida 0. |
| PHPUnit enfocado en stock, permisos, productos y ventas | **46 tests, 346 aserciones**, salida 0; seis tests nuevos de stock con 36 aserciones. |
| `node tools/audit_fix_regression_check.cjs` | **11 grupos aprobados**, 0 fallos/errores, salida 0. |
| `node tools/frontend_regression_check.cjs` | **8 grupos aprobados**, salida 0. |
| `node tools/daily_frontend_check.cjs` | **12 escenarios aprobados**, salida 0. |
| Sintaxis PHP/JS | 6 archivos PHP y 2 harness JS sin errores, salida 0. |
| Compilación Blade a TEMP | 102 vistas compiladas, salida 0. |
| `npm run build -- --outDir <TEMP>` | 8 módulos compilados, salida 0. |

Entorno de pruebas: PHP 8.2.12, PHPUnit 11.5.56, Node 24.15.0; SQLite en memoria, correo/cache/sesiones en memoria y vistas/caches temporales. No se usaron bases, cuentas ni servicios reales. QA comprobó 248 archivos antes y después de la suite: SHA-256 idéntico `ac62e540f3f9f6e1bc8b018fb4c7012d0000421ae6740e1ed2c112ee21a8d67b`.

## Comprobación del contenido que se publica

Se preparó un índice Git separado desde `06d31c2984133a9596bff0db21935399eb8c658c`, aplicando solo los cambios de esta tarea. Se exportó el árbol de código `d3d05510536cbcedbc732ef432a8c889d32dc24d`: **360 blobs coinciden** con Git. La documentación y evidencia se agregan después sin cambiar esos ocho archivos de código/tests.

Un cargador PHP antepuesto y `APP_BASE_PATH` fijaron las fuentes exportadas; `ReflectionClass` confirmó la procedencia del controlador y de `Tests\TestCase`, además de comprobar el directorio base de Laravel. Las dependencias se reutilizaron desde la instalación local, sin modificar los lockfiles. Laravel, PHPUnit, dompdf y PhpSpreadsheet coinciden con el lock publicado; Vite instalado es 7.3.7 frente a 7.3.6 en el lock de esa base. No se ejecutó una instalación limpia desde los locks.

Resultados sobre esta exportación exacta:

- Regresión de stock: **6 tests, 36 aserciones, salida 0**.
- Regresiones JavaScript nuevas y existentes: **11 + 8 grupos, salida 0**.
- Build desde las fuentes exportadas: **8 módulos, salida 0**.
- Suite completa del árbol publicable: **229 tests, 1,741 aserciones, 0 errores/fallos/omitidos, salida 0**, 02:18.853.

Una primera ejecución de la suite exportada falló por rutas de caches mal interpretadas en el harness Windows. Se corrigieron únicamente las variables de entorno de aislamiento, se comprobó un caso representativo y se repitió la suite. No se modificaron fuentes de aplicación ni aserciones para resolver ese fallo de entorno.

Chrome consumió el build del árbol exportado y verificó mediante cuentas/SQLite sintéticas:

1. Una venta de dos unidades entre la apertura y el guardado real del formulario: stock final 8 y nombre guardado.
2. Alta y edición de ejercicios: semántica, cinco etiquetas por diálogo, foco, Tab/Shift+Tab, Escape y retorno al disparador.
3. Modal en pantalla de 320 px sin desbordamiento global.
4. Temporizador renderizado tras adelantar 90 segundos el reloj de prueba: 00:00, «¡A entrenar!» y una alerta.
5. Cero excepciones JavaScript registradas. Proceso de navegador: salida 0.

Build publicado comprobado: manifest SHA-256 `19a12be9a928721a2745ace549623bee150661474331083c24e6df0d31f112b3`; CSS `6118e764a505a747f85acd5b2c242123ad7dc64a8ad25348c84f1bc3eb808d2c`; JS `0121f155bc4910aa97911c9ba017d9dbe313ede972f6bc0c5b34be2d8cf0fa79`.

[Resultado del navegador](evidence-2026-10-24/fixes/browser-result.json), [stock conservado](evidence-2026-10-24/fixes/stock-preserved.png), [modal con foco](evidence-2026-10-24/fixes/exercise-modal-keyboard.png) y [descanso terminado](evidence-2026-10-24/fixes/timer-completed.png).

## Habilidades instaladas

Se revisaron sus instrucciones y recursos antes de instalar mediante el helper de `skill-installer`, con commits fijados y destino personal `%USERPROFILE%/.agents/skills`. No se ejecutaron scripts de terceros ni se agregaron dependencias al proyecto.

| Habilidad | Fuente y motivo |
|---|---|
| `frontend-design` | [Anthropic, revisión fijada](https://github.com/anthropics/skills/tree/dbd4588f9e1033efb41dad4bef2f7947c8993d44/skills/frontend-design). Útil para futuros ajustes visuales en Blade; no se usó para rediseñar esta corrección. |
| `webapp-testing` | [Anthropic, revisión fijada](https://github.com/anthropics/skills/tree/dbd4588f9e1033efb41dad4bef2f7947c8993d44/skills/webapp-testing). Se aplicó su flujo de inspección y comprobación en navegador mediante Puppeteer ya disponible, sin instalar Playwright. |
| `verification-before-completion` | [Superpowers, revisión fijada](https://github.com/obra/superpowers/tree/2ce8aa15d05bd57213e6f746f953aafb7ded7a8b/skills/verification-before-completion). Aplicada para vincular las afirmaciones a comandos y fuentes efectivamente ejecutados. |

Verificación independiente: tres manifiestos y diez archivos comparados por SHA-256; seis referencias locales completas. La habilidad `product-design-integrity-audit` preexistente se preservó idéntica. [Recibo de instalación y hashes](evidence-2026-10-24/skills-installed.json).

No se instaló el plugin completo de Superpowers. `systematic-debugging` quedó fuera por exigir otra habilidad ausente de la selección. Las habilidades de React, Next.js y Postgres no corresponden a Laravel/Blade/SQLite de este proyecto; tampoco se añadieron servicios de despliegue o Firecrawl. Las tres habilidades nuevas estarán disponibles en el siguiente turno; si no aparecen, puede reiniciarse Codex según la [documentación oficial](https://learn.chatgpt.com/docs/build-skills).

## Límites

Los tres síntomas están corregidos en las comprobaciones descritas. No se certifica ausencia global de defectos, WCAG completo ni preparación de producción. La suspensión se simuló; una web completamente suspendida no puede garantizar sonido hasta reanudar JavaScript, y cambiar manualmente el reloj puede alterar la duración medida. SQLite comprueba la intercalación reproducida, no la concurrencia real de MySQL. No se probaron Google, SMTP ni pagos bancarios.

Las capturas y fixtures contienen únicamente datos sintéticos. Las rutas personales se normalizaron en los artefactos publicables. Las habilidades personales y los paquetes de agentes permanecen fuera del repositorio, respetando sus exclusiones existentes.
