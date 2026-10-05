# Historial y correcciones operativas

Estas pantallas adicionales completan correcciones y bajas de ventas y asistencias sin modificar sus flujos originales de creación, pagos o control de entrada/salida.

- Historial de ventas: `/gestion/historial/ventas`.
- Historial de asistencias: `/gestion/historial/asistencias`.
- Acceso: administrador y secretaria. Entrenadores y clientes no pueden consultarlo ni modificarlo, incluso si los dos guards coexisten.
- Las listas incluyen registros vigentes y anulados, con búsqueda, filtros por fecha/estado y paginación.

## Ventas

Se puede corregir únicamente la nota, con un motivo obligatorio. No se puede editar el total, método de pago, productos, cantidades, precios, cliente ni operador. Los comprobantes ya emitidos no son reemplazados: una corrección se documenta en la bitácora.

La anulación requiere administrador, motivo y casilla explícita de confirmación. Está destinada a un registro erróneo, no a una devolución comercial. Dentro de una transacción se bloquean los productos por ID y luego la venta; se comprueba nuevamente si ya estaba anulada. Se restituye exactamente la cantidad de cada detalle una sola vez, incluso cuando existan varias líneas del mismo producto. Las líneas y los importes originales se conservan.

Si faltan detalles/productos o el stock resultante supera la capacidad de la columna, la operación se rechaza sin modificar inventario ni venta. Una segunda solicitud de anulación no vuelve a sumar existencias ni crea otra entrada en la bitácora.

No se mueve dinero ni se comunica con un banco. Cuando hubo cobro real, el responsable debe tramitar y documentar la devolución según su política y asesoría contable/legal. Esta bitácora no es factura fiscal, nota de crédito ni sustituto de los documentos exigibles. Los archivos o correos de comprobantes ya emitidos siguen existiendo. El registro anulado permanece disponible en este historial; no se reemite como venta vigente.

## Asistencias

Administrador y secretaria pueden corregir las horas de entrada/salida con motivo obligatorio. La salida debe ser igual o posterior a la entrada. Se preservan cliente y operadores originales. Se bloquea primero al cliente y después su asistencia para serializar con el registro normal de entrada/salida. Se rechazan horarios superpuestos y correcciones que dejarían dos entradas abiertas.

Se permite anular un registro erróneo con motivo y confirmación, sin borrarlo. La anulación no cambia las fechas originales. Se invalida la caché de aforo después de una corrección o anulación, y los registros anulados dejan de contar en métricas y visitas abiertas.

## Conservación y auditoría

La migración `2026_10_04_400000_add_anulaciones_operativas.php` añade solamente columnas nullable (`anulada_en`, `anulada_por`, `motivo_anulacion`) e índices a ambas tablas, además de `historial_correcciones`. Los registros existentes siguen vigentes por tener `anulada_en = NULL`.

La bitácora se escribe en la misma transacción que la corrección. Guarda tabla e ID internos, acción, empleado, guard, motivo y valores anteriores/posteriores permitidos: horarios, nota, fecha de anulación y cantidades restituidas por producto. No copia nombre, correo, contraseña ni datos de tarjeta del cliente. Notas y motivos no deben contener diagnósticos ni datos sensibles.

Los modelos aplican el scope `vigentes` automáticamente a las consultas normales para excluir anulados; solo este historial utiliza `withoutGlobalScope('vigentes')`. Consultas SQL directas deben filtrar `anulada_en IS NULL` cuando calculen indicadores de operaciones vigentes.

Revertir la migración elimina estas adiciones y su bitácora; exportarlas antes si ya se usaron. No hay opción automática de desanular: debe revisarse el registro y documentarse la corrección posterior para evitar doble movimiento de inventario.

## Verificación

`php artisan test tests/Feature/HistorialOperativoTest.php` comprueba permisos/guards, conservación de importes e identidades, corrección de horarios, solapamientos, aforo, anulación y restitución idempotente de stock.

SQLite en memoria verifica invariantes secuenciales. La concurrencia de bloqueos real corresponde a MySQL y debe comprobarse en ese motor; no se presenta como verificada por SQLite.
