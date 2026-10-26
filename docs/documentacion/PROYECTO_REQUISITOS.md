# Alpha Fitness Proyecto y requisitos

**Edición 2.0** · 19 de octubre de 2026. Revisión: base `06d31c2` más el contenido identificado en `REVISION.json`.

## Propósito del proyecto

Alpha Fitness reúne la operación de recepción y el seguimiento del entrenamiento en una aplicación web. Permite que el gimnasio gestione personal, clientes, membresías, asistencia, productos y registros de venta, mientras cada cliente consulta sus rutinas y guarda sus marcas deportivas. La interfaz prioriza el uso desde celular y también ofrece vistas de escritorio.

Esta especificación describe el sistema implementado. Las propuestas futuras se identifican al final; no se presentan como capacidades disponibles. No se dispone de una cifra comprobada de ahorro, presupuesto, usuarios simultáneos o impacto comercial, por lo que no se inventan esos resultados.

## Objetivos verificables

- Facilitar ventas de práctica con catálogo, carrito y revisión del cobro, conservando inventario y trazabilidad.
- Separar las responsabilidades de Administrador, Secretaria, Entrenador y Cliente mediante autorización en servidor.
- Relacionar entrenadores y clientes a través de asignaciones de rutinas verificables.
- Presentar progreso comparable por ejercicio y rangos de constancia comprensibles.
- Conservar solicitudes, cobros registrados, visitas y sesiones para consulta y reportes.
- Permitir instalación y mantenimiento reproducibles a partir del repositorio y sus archivos de dependencias bloqueadas.

## Alcance y contexto

El producto incluye acceso y recuperación, configuración personal, directorio de entrenadores, cuentas, ejercicios, rutinas, entrenamiento guiado, progreso, productos, ventas, membresías, asistencia y reportes. Los correos dependen del proveedor configurado. Google es una alternativa opcional para cuentas de clientes.

La pantalla actual de ventas genera operaciones simuladas. Puede probar efectivo, aprobación o rechazo de tarjeta y transferencia, pero no cobra dinero. Estas operaciones sí afectan el inventario de la instalación donde se ejecutan. La capacitación debe realizarse en un entorno con datos sintéticos. La activación de membresías registra un cobro presencial confirmado manualmente por Secretaria; tampoco utiliza una pasarela.

No se incluyen una aplicación nativa, funcionamiento completo sin conexión, contabilidad fiscal, cobro bancario, suscripciones automáticas, compras a proveedores ni reembolsos. El proyecto no incorpora telemetría publicitaria ni certifica por sí mismo el cumplimiento normativo de una instalación.

## Actores y responsabilidades

| Actor | Responsabilidad principal |
| --- | --- |
| Administrador | Personal, acceso de clientes, catálogos, existencias, supervisión y reportes financieros. |
| Secretaria | Recepción, asistencia, solicitud y activación de membresías, pausas y ventas. No puede corregir existencias ni abrir reportes financieros. |
| Entrenador | Crear rutinas propias, asignar copias a clientes y consultar el progreso de quienes tienen una asignación suya. |
| Cliente | Gestionar su perfil, rutinas y marcas; solicitar planes y pausas; consultar sus visitas y sus entrenadores. |
| Responsable técnico | Instalar, configurar servicios externos, custodiar claves y respaldos, actualizar y comprobar el despliegue. No constituye un quinto rol de la aplicación. |

El Administrador y Secretaria tienen funciones diferentes. La confirmación de pagos de membresía corresponde a Secretaria. Ningún rol obtiene permiso para editar una rutina ajena por el simple hecho de ser empleado.

## Requisitos funcionales implementados

| ID | Requisito | Criterio observable |
| --- | --- | --- |
| RF01 | Acceso separado de personal y clientes. | Cada pestaña autentica su tipo de cuenta y rechaza cuentas inactivas. |
| RF02 | Registro público exclusivo de clientes. | Registrarse crea una cuenta individual sin membresía ni privilegios de personal. |
| RF03 | Recuperación e invitaciones privadas. | Cada titular establece su contraseña; los tokens de clientes y personal están separados. La respuesta pública no revela si existe una cuenta. |
| RF04 | Gestión de personal y acceso de clientes. | Solo el Administrador crea o edita empleados, reenvía invitaciones pendientes y banea o restaura clientes. El historial permanece. |
| RF05 | Catálogo de ejercicios. | Todos los roles autenticados consultan y valoran ejercicios activos; solo Administración los crea y administra. |
| RF06 | Rutinas individuales. | El titular organiza días y ejercicios; puede imprimir y seguir su rutina. Entrenador o Administrador asigna una copia propia a un cliente activo. |
| RF07 | Seguimiento del entrenador. | Mis clientes se obtiene de asignaciones por ID; un entrenador no consulta progreso de clientes sin vínculo. Mis entrenadores muestra vínculos activos del cliente. |
| RF08 | Sesiones de entrenamiento. | Finalizar guarda sesión, duración y series. Reintentar la misma operación con su UUID no duplica la sesión. |
| RF09 | Marcas deportivas privadas. | Solo el cliente crea y elimina sus marcas; Entrenador vinculado o Administrador consulta según permiso. |
| RF10 | Progreso comparable. | La gráfica usa hasta ocho registros del mismo ejercicio; el rango global permanece al filtrar y muestra umbrales 0, 3, 8 y 15. |
| RF11 | Planes y solicitudes. | Solo planes activos pueden solicitarse; la solicitud guarda sus términos y permanece pendiente hasta resolución de recepción. |
| RF12 | Cobro manual de membresía. | Solo Secretaria confirma importe y activación. Resolver de nuevo una solicitud no genera otro pago. Renovar crea un período nuevo. |
| RF13 | Pausas de membresía. | Una solicitud pendiente no congela el acceso. Al aprobar se ajustan fechas y se respeta el límite acumulado; las superposiciones se validan. |
| RF14 | Control de visitas. | Administración o Secretaria registra entrada/salida, sin segunda entrada abierta; el cliente solo consulta su historial. |
| RF15 | Productos y existencias. | Administración y Secretaria mantienen el catálogo; solo Administración establece stock. La venta aprobada descuenta la cantidad validada. |
| RF16 | Pago de práctica. | Efectivo insuficiente y tarjeta rechazada no crean ventas. Efectivo aprobado guarda cambio; no se solicitan datos bancarios. |
| RF17 | Venta y comprobante íntegros. | El servidor fija precios y total, valida stock y guarda encabezado/detalle en transacción. El reintento con el mismo UUID no descuenta otra vez. |
| RF18 | Separación financiera. | Ventas simuladas llevan etiqueta y quedan fuera de los agregados reales, incluidos PDF y Excel. Los pagos de membresía registrados aportan ingreso. |
| RF19 | Reportes por período. | Solo Administración obtiene analítica de hasta 90 días y exporta PDF o Excel con un registro de la exportación. |
| RF20 | Preferencias personales. | Cada cuenta conserva apariencia; puede cambiar su nombre y avatar dentro de los límites admitidos. |
| RF21 | Navegación móvil. | La barra inferior cambia según el rol y las vistas de ventas, membresías y progreso se pueden usar sin una tabla ancha obligatoria. |
| RF22 | Privacidad e incidencias visibles. | La política es pública y el Administrador ve configuraciones pendientes sin revelar secretos. |

## Reglas de negocio que deben conservarse

1. La aplicación admite un único Administrador. Las altas de personal solo permiten Secretaria y Entrenador.
2. Un cliente activo puede tener varios entrenadores por distintas rutinas. El nombre del asignador no sustituye su identificador para autorizar consultas.
3. La asignación es una copia: cambiar el original no modifica automáticamente la rutina del cliente.
4. El catálogo puede archivarse sin eliminar las referencias de sesiones, ventas o marcas históricas.
5. Los precios de venta se toman del servidor; las cantidades acumuladas por producto se validan antes de descontar inventario.
6. La venta simulada aceptada afecta stock y conserva comprobante; el rechazo deja ambos sin cambios. El mismo UUID solo representa una operación.
7. Los importes de venta se calculan en centavos enteros y se persisten con dos decimales. Las gráficas son presentación, no la autoridad del cobro.
8. Los pagos de membresía son registros manuales del cobro presencial. El precio del plan o una solicitud pendiente no son por sí mismos ingresos.
9. Renovar conserva períodos anteriores. Las pausas aprobadas no deben superponerse indebidamente con una renovación.
10. El rango depende del número de marcas existentes, no del peso levantado, del filtro ni del número de sesiones finalizadas.

## Requisitos de calidad y evidencia

| Área | Condición de aceptación y alcance |
| --- | --- |
| Uso móvil | Sin desbordamiento horizontal en las rutas examinadas a 390 px; Progreso también revisado a 320 y 360 px. Navegación inferior, controles de al menos 44 px e inputs legibles. No equivale a una certificación de todos los dispositivos. |
| Accesibilidad | Etiquetas, foco, confirmaciones y controles de teclado en los componentes revisados; contraste validado para colores personalizados. Falta una auditoría integral con lectores de pantalla. |
| Seguridad | Permisos en servidor, protección CSRF, límites de solicitudes, hash de contraseñas, aislamiento de proveedores y validación de imágenes. HTTPS y custodia de datos dependen del despliegue. |
| Integridad | Transacciones y reintentos para ventas y sesiones; conservación del historial financiero. Los tests SQLite no demuestran concurrencia real de MySQL. |
| Mantenibilidad | Laravel 12, servicios por responsabilidad, migraciones incrementales, lockfiles y pruebas automatizadas. La documentación referencia los contratos implementados. |
| Recuperación | Respaldo consistente antes de migrar; restauración ensayada en copia aislada. No hay un objetivo de recuperación contractual medido. |
| Rendimiento | Paginación y recursos compilados. No se ha medido capacidad concurrente ni latencia de producción; se requiere una prueba de carga con el entorno objetivo. |
| Compatibilidad | PHP 8.2 y navegador moderno con JavaScript y cookies. La revisión visual utilizó Chrome con tamaños móviles; iOS físico y otros motores no se certifican. |

## Recorridos de aceptación

**Recepción y venta:** iniciar como Secretaria, abrir Ventas, agregar un producto y comprobar efectivo insuficiente. Indicar $10 para un total de $2 y verificar $8 de cambio. Crear otra operación con tarjeta rechazada, confirmar ausencia de venta y stock intacto, cambiar a aprobada y verificar una única venta incluso al reintentar. Consultar comprobante e historial; Administración confirma que esas simulaciones no elevan sus ingresos.

**Entrenador y cliente:** Administración invita al entrenador, este establece su clave y asigna una rutina propia. El cliente consulta Mis entrenadores y el entrenador encuentra al cliente en Mis clientes. Un entrenador distinto recibe acceso denegado al intentar abrir ese progreso.

**Progreso móvil:** el cliente guarda dos marcas del mismo ejercicio, revisa su evolución y consulta un segundo ejercicio. El rango permanece global al filtrar y cambia solo cuando cambia el total de registros. Una marca ajena no puede eliminarse.

**Membresía y visita:** el cliente solicita un plan, Secretaria confirma el cobro presencial y el período aparece activado. Recepción registra entrada y salida; una segunda entrada abierta se rechaza. El cliente consulta sus propias visitas sin controles de operación del conjunto.

Los resultados de la ejecución realizada y las referencias a tests se encuentran en `VERIFICACION_PRUEBAS.md`; estos recorridos también sirven como lista de aceptación para otra instalación.

## Pendientes y evolución del producto

**Necesarios para una publicación real:** definir responsable/contacto y conservación de datos; configurar y probar entrega de correo; revisar HTTPS, respaldo y restauración con el responsable del servidor. Google puede permanecer sin configurar si no se ofrece ese acceso.

**Capacidades futuras que requieren decisiones propias:** pasarela bancaria y conciliación, devoluciones con trazabilidad, cancelación visible de membresías ya activadas, compras y movimientos de inventario, preservación del entrenamiento en curso tras recarga y automatización de retención. No se implementan silenciosamente como parte de una corrección visual o documental.

**Comprobaciones adicionales recomendadas:** dispositivo iOS y Android físicos, lector de pantalla, carga de producción, recuperación completa y concurrencia con el motor elegido. Estas comprobaciones no se marcan como realizadas.

## Mantenimiento de esta especificación

Cuando cambie un requisito, revisar autorización, ruta/controlador, modelo y migraciones, vista y pruebas relacionadas. Actualizar el manual de usuario si cambian pasos visibles y la guía operativa si cambian configuración o datos. Registrar fecha y contenido de la revisión; un SHA base no identifica cambios locales posteriores.
