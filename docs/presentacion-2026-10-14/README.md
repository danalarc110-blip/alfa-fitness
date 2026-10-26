# Documentos para presentar Alpha Fitness

Esta entrega contiene un prompt listo para copiar en Antigravity y fuentes editables en español para explicar las mejoras de Productos, Punto de Venta, Entrenadores y Mis clientes.

| Archivo | Uso |
| --- | --- |
| [Prompt para Antigravity](PROMPT_ANTIGRAVITY.txt) | Solicitar la actualización de los manuales y preparar la documentación de presentación con alcance, fuentes, evidencia y límites definidos. |
| [Informe y guion de presentación](INFORME_PRESENTACION.md) | Exponer el problema, los cambios, los permisos, la demostración y las comprobaciones realizadas. |
| [Guía de uso de los módulos](GUIA_USO_MODULOS.md) | Seguir los procedimientos actuales según el rol y corregir los errores de operación habituales. |

## Cómo usar el prompt

1. Abra el proyecto `alfa-fitness` en Antigravity.
2. Copie el contenido completo de `PROMPT_ANTIGRAVITY.txt` en una nueva solicitud del proyecto.
3. Revise los documentos generados y complete los datos de portada que no se hayan suministrado.

La orden del prompt autoriza documentación. La actualización de la aplicación requiere un encargo separado. Los formatos Word y PDF quedan sujetos a la capacidad de generación y verificación visual del entorno de Antigravity.

## Revisión y alcance

Fecha de la evidencia de implementación: **14 de octubre de 2026**. Referencia: commit base `db338cd0b67410baf61ad7459f91a34a61a92ad8` más cambios sin commit, identificados por el snapshot de 35 archivos con SHA256 agregado `c9bb7e6fded081081178c1ca2527a9665445fb3e8ac0b56da9857aa7385d9dd2`.

Los manuales anteriores de [documentación integral](../documentacion/README.md) corresponden a otra revisión. Esta carpeta explica los módulos mejorados; no certifica una actualización completa de aquellos manuales ni un despliegue de producción. Las capturas incluidas proceden de la aplicación ejecutada con datos ficticios.

Las pruebas dirigidas se solapan con la suite general. Sus conteos se presentan por ejecución y no deben sumarse como casos distintos. La verificación de correo externo, impresora física, concurrencia real y restauración externa quedó pendiente.
