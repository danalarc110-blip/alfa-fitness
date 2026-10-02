# Corrección de fallos del equipo — 3.1

2 de octubre de 2026. Base examinada: `d71e336721aba517fa7fbb8fa3332961369fa00e`. Se corrigieron instrucciones y configuración de los diez agentes; no se modificó la aplicación Alpha Fitness.

| Hallazgo | Corrección |
| --- | --- |
| Worktrees podían compartir DB, puertos, colas/caché y outputs de pruebas | Propiedad de recursos, namespaces por tarea o ejecución serializada. Limpieza limitada a recursos propios. |
| Reasignar un archivo no detenía al escritor anterior | Confirmar fin de agente/comandos que escriben, preservar diff y transferir después. Si no puede detenerse, trabajar aislado sin integrar escrituras en colisión. |
| Un mensaje a agente idle podía reactivar trabajo antiguo | Distinguir nota informativa de nuevo encargo, conservar tarea/revisión y validar propiedad vigente antes de actuar. Reanudar tras inspección de estado real. |
| Orden fijo de unitarias antes de build fallaba con pruebas compiladas | Orden según dependencias: configurar/compilar antes de tests cuando la pila lo requiere. |
| QA esperaba otra decisión sobre un requisito ya aprobado | Cambiar expectativas trazablemente dentro de alcance/propiedad, conservando controles legítimos; consultar solo ambigüedad material. |
| Guía indicaba invocar directamente navegador integrado | Ruta web condicionada por capacidad real y mecanismo documentado `/browser`; E2E existente como alternativa. Ruta nativa separada. |
| Tabla de herramientas partida tras siete roles | Tabla continua con los diez agentes. |
| Diagramador podía sustituir un diagrama técnico por imagen generada | Se retiró generate_image del rol; fuentes exactas editables y salida según notación solicitada. |
| Propuestas futuras chocaban con obligación de existir en código | Estado actual respaldado por implementación; diseño solicitado respaldado por especificación y marcado como propuesta. |
| Plantillas/estructura/idioma podían ser reemplazados por defaults | Defaults subordinados a instrucciones explícitas. Changelog conserva convención real; no imponer semver o páginas extra. |

También se retiró schedule del líder: el paquete no requiere temporizadores ni vigilancia. Se aclaró que permisos de herramientas/sandbox no equivalen a un modo técnico de solo lectura, y que el coordinador no debe delegarse a sí mismo ni iniciar diez procesos solo por existir diez roles.

## Verificación

Dos revisores independientes buscaron errores accionables y releían las correcciones correspondientes. Sus conclusiones son estáticas, no ejecuciones del host. Validación local: YAML y campos/tipos, identificadores, herramientas declaradas, copias de los diez agent.md, protocolo duplicado, tabla continua, diff y ZIP regenerado con contenido exacto.

El inventario de herramientas se contrastó con fuentes oficiales: https://antigravity.google/docs/hooks y https://antigravity.google/docs/subagents/. Esa comprobación no certifica capacidades de una instalación particular. Falta ejecutar los agentes y sus herramientas dentro de Antigravity; no se afirma ausencia total de errores ni prueba E2E.
