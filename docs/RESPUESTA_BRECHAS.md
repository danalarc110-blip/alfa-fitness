# Procedimiento de respuesta a brechas de datos

Versión: 2026-10-04. Uso interno. Base que debe revisar un abogado de El Salvador y el responsable técnico. No publicar expedientes de incidentes ni datos afectados en GitHub.

## Verificación normativa al 4 de octubre de 2026

El Decreto 144 se publicó en el Diario Oficial 219, tomo 445, el 15/11/2024; su art. 64 fija la vigencia ocho días después, de donde resulta el 23/11/2024. La ACE supervisa y sanciona conforme al art. 50. El art. 25 exige comunicar vulneraciones a ACE, Fiscalía General y titulares afectados en hasta 72 horas desde su conocimiento e iniciar en esa ventana la revisión. Incluye incidentes accidentales. [Texto oficial de la ley](https://www.ace.gob.sv/documentos/decretos/decreto_144_proteccion_datos.pdf).

La ACE publica como vigentes las [políticas de protección de datos](https://www.ace.gob.sv/politicas.php); su documento [001-0309025-DPDP, art. 4](https://www.ace.gob.sv/documentos/politicas/politicas_protecciondatos.pdf) reafirma los tres destinatarios y las 72 horas. La publicación consultada no permite identificar la fecha material original de publicación de esas políticas; no se deduce del código del documento. También hay lineamientos sobre delegado y procedimiento sancionador enlazados por la ACE.

Existe una reforma aprobada el 17/09/2026: Decreto 659. La [ficha oficial](https://www.asamblea.gob.sv/leyes-y-decretos/view/7022) asigna DO 173, tomo 452, 18/09/2026, pero advierte que la publicación material sigue pendiente. La [noticia oficial](https://www.asamblea.gob.sv/node/14116) describe solicitudes directamente al responsable y ajustes del delegado. No se logró comprobar la publicación material, el texto íntegro ni su fecha efectiva de vigencia: pendiente de verificar con ACE y el abogado. No se da por eliminada la obligación actual de delegado para una empresa privada a partir de una noticia. El canal directo del gimnasio se mantiene operativo para recibir y encauzar solicitudes en cualquier escenario.

## Personas y medios que se deben completar

Responsable que decide y firma comunicaciones: {{NOMBRE_RESPONSABLE}}. Coordinación de privacidad: {{PERSONA_CONTACTO_PRIVACIDAD}}. Técnico y suplencia: {{CONTACTO_TECNICO_BRECHAS}}. Correo disponible para titulares: {{CORREO_PRIVACIDAD}}. Teléfono de contacto: {{TELEFONO_RESPONSABLE}}. Lugar restringido del expediente: {{UBICACION_EXPEDIENTE_INCIDENTES}}.

Antes de operar, comprobar los canales oficiales vigentes y designar sustitutos. La aplicación no envía avisos legales automáticamente.

## Acciones y tiempos internos

1. Al detectar un posible acceso indebido, pérdida, alteración o divulgación, avisar de inmediato al responsable y al técnico. Registrar fecha y hora de detección y de conocimiento, con zona horaria de El Salvador. Abrir un expediente restringido y calcular el límite de 72 horas continuas; no esperar días hábiles. Este cálculo no se aplaza por desconocer todavía todos los detalles.
2. Contener el incidente de forma proporcionada: restringir el acceso comprometido, revocar las sesiones o claves afectadas y aislar el equipo si hace falta. Preservar logs, evidencia y copias en un lugar seguro. No destruir registros ni reinstalar todo antes de preservar evidencia. No enviar secretos por correo ni pegar datos reales en herramientas públicas.
3. Identificar datos, cuentas y personas posiblemente afectadas, sistemas, proveedores, momento y causa probable. Distinguir hechos confirmados y puntos en investigación. Revisar si hubo extracción, cambios de permisos, copias expuestas o imágenes publicadas. El responsable y el abogado prepararán los avisos sin esperar a que termine el análisis técnico.
4. Notificar dentro del máximo aplicable a los tres destinatarios. Guardar prueba de entrega, hora, canal, contenido y número de recepción. Si faltan hechos, indicar lo conocido y lo pendiente; no retrasar el primer aviso para completar una investigación. La forma de ampliar información se coordina con la autoridad.
5. Corregir la causa, comprobar permisos y recuperación, reforzar controles y comunicar actualizaciones pertinentes. Restaurar únicamente desde una copia verificada y comprobar integridad de pagos, inventario y cuentas. Documentar medidas inmediatas y definitivas, personas responsables y fecha de cierre. Revisar el procedimiento después de cada incidente y hacer un ejercicio de simulación periódico.

Los objetivos internos son escalamiento inmediato, evaluación preliminar durante el primer día y preparación de avisos antes de las 48 horas, dejando margen para completar la notificación. Son decisiones organizativas; no sustituyen ni amplían el máximo legal.

## Contenido de los avisos

Para ACE: naturaleza del incidente; datos comprometidos; medidas correctivas inmediatas; recomendaciones para protegerse; y contacto para ampliar información. Para los titulares afectados: naturaleza, datos, recomendaciones y contacto. Preparar para FGR la información del incidente y la evidencia pertinente mediante el canal seguro que indique. Referencia de contenidos: art. 25 de la ley enlazada arriba.

No adjuntar listas de clientes, contraseñas ni información íntima a un envío abierto. Informar individualmente o usando medios que no expongan las direcciones de otros titulares.

```text
Asunto: Notificación de vulneración de datos — Alpha Fitness
Responsable y contacto:
Fecha y hora en que se tuvo conocimiento:
Fecha y hora de envío:
Naturaleza del incidente y alcance conocido:
Categorías de datos comprometidos:
Medidas correctivas inmediatas (comunicación a ACE/FGR):
Recomendaciones concretas para las personas afectadas:
Medio para ampliar información:
Hechos aún en investigación y próximo seguimiento:
```

## Canales oficiales consultados

La [página de contacto de ACE](https://www.ace.gob.sv/contacto.php) publica teléfono (503) 7530-6114 y opciones de contacto. El [portal de políticas](https://www.ace.gob.sv/politicas.php) muestra contacto@ace.gob.sv. Confirmar el canal de recepción formal y pedir constancia; un formulario web genérico no garantiza que una notificación formal haya sido recibida.

La [FGR informa sus servicios de recepción de denuncias y directorio de oficinas](https://www.fiscalia.gob.sv/servicios/). Contactar una oficina fiscal y presentar la notificación por el canal que confirme; conservar constancia. No usar una solicitud de acceso a información pública como notificación de brecha. Estos contactos pueden cambiar y deben revisarse al producirse un incidente.

Los titulares se contactarán usando medios registrados y seguros; si un correo está comprometido, se acordará un medio alternativo. Dejar constancia de intentos y seguir las instrucciones de la autoridad cuando no sea posible localizar a una persona.
