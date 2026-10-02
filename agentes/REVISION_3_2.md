# Revisión adicional del equipo — 3.2

2 de octubre de 2026. Base examinada: `c8d0844c57340d8aff666c4d89c008a203a57c2b`.

Se corrigieron ocho carencias demostrables en las instrucciones de los agentes. Los escenarios siguientes explican el riesgo y el comportamiento exigido por el parche; no son incidentes observados ni ejecuciones reales de Antigravity. No se modificó la aplicación Alpha Fitness.

| Hallazgo | Escenario que faltaba cubrir | Corrección y ubicación |
| --- | --- | --- |
| H-REV32-01: éxito sin pruebas efectivas | Un filtro selecciona cero casos, todos los pertinentes quedan omitidos o solo se recoge la suite; el runner devuelve exit 0. | QA comprueba descubrimiento/selección, ejecución y controles pertinentes por requisito. Cero ejecutados, omitidos, fallos esperados o colección/dry-run no acreditan cumplimiento. Protocolo §5, QA §7 y líder §8. |
| H-REV32-02: ejecutable sin procedencia | El build termina correctamente, pero se prueba un binario anterior o un target distinto. | Registrar ruta/identidad del artefacto ejecutado y correspondencia con fuentes, configuración y toolchain. Reconstruir el target propio o invalidar su caché pertinente si no puede demostrarse; sin limpieza global por rutina. Protocolo §5 y QA «Aislamiento de la ejecución». |
| H-REV32-03: evidencia con contexto antiguo | Cambia configuración, lockfile o entorno, pero el proceso conserva valores o dependencias anteriores. | Invalidar solo comprobaciones dependientes de fuentes, configuración, scripts, dependencias, esquemas, datos o runtime. Confirmar contexto efectivo actualizado y recarga pertinente, sin exponer secretos. Protocolo §9, líder y QA. |
| H-REV32-04: Git compartido entre worktrees | Dos tareas actualizan una misma ref/tag de integración o configuración compartida. | Escritor único por recurso, refs propias cuando corresponda e integración coordinada. Serializar actualizaciones y comprobar valor esperado; reconciliar divergencias sin forzar ni borrar trabajo ajeno. Protocolo §10 y líder. |
| H-REV32-05: instalación sobre reglas existentes | Copiar el protocolo a la raíz reemplaza AGENTS.md del proyecto receptor o colisiona con agentes instalados. | Preservar reglas locales y agentes; comparar diff e integrar cláusulas compatibles o referencia subordinada al protocolo. La igualdad entre AGENTS.md raíz/distribución solo se exige en el repositorio mantenedor. README, protocolo §8 e introducción de los diez roles. |
| H-REV32-06: propuesta sin repositorio bloqueada | Se pide modelar una arquitectura futura con especificación, pero el descubrimiento exige manifests/código existentes. | Separar inspección de implementación y de propuesta. Para futuro, usar revisión de especificación, requisitos y restricciones, declarar supuestos y marcar commit/manifests no aplicables; no inventar implementación. Diagramador §0/§2 y protocolo §9. |
| H-REV32-07: documentación confundida con ejecución | Una revisión documental válida no puede ejecutar procedimientos y otra cláusula exige que todos funcionen realmente. | Cada procedimiento declara fuente, método y límites: ejecutado, contrastado o revisado manualmente. La revisión documental no acredita operación; ejecutar sigue pendiente si es criterio material del encargo. Documentador §7/§8. |
| H-REV32-08: determinismo obligatorio para ML | Un criterio sobre distribuciones o tasa de error se rechaza por no producir siempre la misma salida. | Criterios operacionales y verificables; evaluación con métricas, tolerancias, muestra e incertidumbre pertinentes y acordadas. No inventar umbrales ni confundir reproducibilidad con corrección. Requisitos §5, protocolo §2 y GUIA_PILAS.md. |

## Comprobación y límites

Dos revisores independientes examinaron coordinación/evidencia y portabilidad. Después releyeron el parche y confirmaron las ocho correcciones sin detectar contradicciones materiales nuevas en los cambios revisados. Su evaluación es estática y acotada.

Validación local del paquete: YAML y campos/tipos, nombres únicos, herramientas declaradas, diez pares de agent.md idénticos, protocolo duplicado, tablas continuas, enlaces locales, diff sin errores y ZIP regenerado con inventario y bytes exactos.

La regla de detener y confirmar finalización del escritor y sus comandos ya cubría el timeout: no se añadió como defecto nuevo. Las exclusiones existentes ya permiten manuales CLI sin login ni base de datos. Los agentes de manuales y diagramas conservan activación únicamente por orden explícita del usuario; editar sus instrucciones no los activa.

Queda pendiente comprobar descubrimiento, herramientas y comportamiento en una instalación real de Antigravity. La validación de archivos y escenarios no demuestra ejecución del host ni garantiza ausencia de errores.
