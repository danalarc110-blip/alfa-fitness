# Documentación del proyecto Alpha Fitness

Edición 2.0, 19 de octubre de 2026. Describe la base `06d31c2` y los cambios locales de ventas simuladas, progreso, entrenadores y navegación móvil. Las huellas de las fuentes están en [REVISION.json](REVISION.json); esta edición no acredita que esos cambios ya estén publicados en GitHub.

## Documentos

| Documento | Contenido | PDF | Fuente editable |
| --- | --- | --- | --- |
| Proyecto y requisitos | Contexto, objetivos, alcance, actores, requisitos, reglas y aceptación. | [Leer](PROYECTO_REQUISITOS.pdf) | [Markdown](PROYECTO_REQUISITOS.md) |
| Manual de usuario | Acceso y uso por Administrador, Secretaria, Entrenador y Cliente; ejemplos y capturas móviles. | [Leer](MANUAL_USUARIO.pdf) | [Markdown](MANUAL_USUARIO.md) |
| Manual técnico | Arquitectura implementada, módulos, contratos, permisos, servicios y configuración. | [Leer](MANUAL_TECNICO.pdf) | [Markdown](MANUAL_TECNICO.md) |
| Diccionario de datos | Tablas, campos relevantes, relaciones, índices y conservación del historial. | [Leer](DICCIONARIO_DATOS.pdf) | [Markdown](DICCIONARIO_DATOS.md) |
| Operación y despliegue | Instalación, actualización, correo, Google, publicación, respaldos y recuperación. | [Leer](OPERACION_DESPLIEGUE.pdf) | [Markdown](OPERACION_DESPLIEGUE.md) |
| Verificación y pruebas | Comandos ejecutados, escenarios, resultados, reproducción y límites reales. | [Leer](VERIFICACION_PRUEBAS.pdf) | [Markdown](VERIFICACION_PRUEBAS.md) |

Para aprender a usar el sistema, empiece por el manual de usuario y su rol. Para instalarlo o actualizarlo, lea primero operación y despliegue; no utilice procedimientos de instalación nueva sobre una base existente. Para desarrollar, consulte el manual técnico, el diccionario y los requisitos. El informe de pruebas distingue comprobaciones ejecutadas de controles pendientes.

Los PDF tienen texto seleccionable y marcadores de navegación. Los Markdown pueden editarse en un editor de texto sin modificar el formato de la aplicación. Las imágenes de [capturas](capturas/) contienen exclusivamente datos sintéticos del entorno de revisión; se conservan nueve capturas finales.

El inventario [RUTAS.json](RUTAS.json) contiene las 93 rutas propias obtenidas con `php artisan route:list --json --except-vendor`, con verbo, URI, acción y middleware. Es una referencia interna del sistema web; no declara una API pública estable.

## Datos que debe completar el responsable

- Responsable y correo de privacidad, y criterio de conservación de datos y respaldos.
- Proveedor y remitente de correo real, seguido de una prueba de entrega. `MAIL_MAILER=log` genera mensajes privados de desarrollo y no los entrega.
- Dominio, HTTPS y configuración del servidor de publicación.
- Credenciales y callback de Google solo si se ofrecerá esa alternativa de acceso.

Los nuevos cobros realizados desde Ventas son simulados: ajustan stock y generan comprobantes, pero no mueven dinero ni se suman a los ingresos reales. La activación de membresías registra el pago presencial confirmado por Secretaria. No hay integración bancaria.

## Actualizar y generar los PDF

Edite la fuente correspondiente y compruebe primero el comportamiento real. El generador `tools/build_project_docs.py` lee solo los seis manuales y sus capturas; no abre `.env`, Laravel ni bases de datos. Requiere Python con ReportLab (generación comprobada con 4.4.9) y fuentes Arial y Consolas disponibles; estos requisitos son para generar documentación, no para ejecutar la aplicación.

```sh
python tools/build_project_docs.py --font-dir C:/Windows/Fonts
```

En otro sistema, indique una carpeta de fuentes compatible o adapte el registro de fuentes del generador. Antes de distribuir, renderice los PDF a imágenes con Poppler y revise tablas, cortes de página, capturas y caracteres. Compruebe enlaces, datos de revisión y resultados de pruebas; no copie resultados antiguos como si fueran actuales. Los PDF deben regenerarse si se modifica su Markdown.

Para una entrega, incluya esta carpeta con sus fuentes, PDF, capturas y `REVISION.json`. Mantenga los logs, bases de datos, `.env`, contraseñas y respaldos fuera del paquete público. El generador vive en `tools/` dentro del repositorio; la copia de documentación por sí sola no es una copia instalable de la aplicación.

## Historial documental

- Edición 1.0, 7 de octubre: manuales originales para la revisión `26b511f`.
- Edición 2.0, 19 de octubre: actualización de manuales, cuatro documentos complementarios, capturas de celular y revisión de fuentes y resultados actuales.

`docs/AUDITORIA_INTEGRAL.md` se conserva como informe histórico. La carpeta previa `docs/presentacion-2026-10-14/` se preserva y no es la documentación vigente de esta edición.
