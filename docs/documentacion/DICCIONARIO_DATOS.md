# Alpha Fitness Diccionario de datos

**Edición:** 2.0 · 19 de octubre de 2026.

**Revisión:** base `06d31c2984133a9596bff0db21935399eb8c658c` más árbol local del 19 de octubre, identificado en [REVISION.json](REVISION.json).

**Fuente:** reconstrucción estática de migraciones ordenadas y modelos; no contiene registros de personas ni extracción de una base privada.

Se describen 28 tablas declaradas por las migraciones y la tabla de control `migrations` de Laravel: 29 en total. Tipos físicos, representación de booleanos, índices implícitos y aplicación de restricciones pueden variar por motor. Esta edición incorpora pagos simulados y valoraciones de entrenadores.

## 1. Convenciones

| Notación | Significado |
|---|---|
| `id` | Salvo excepción indicada, BIGINT UNSIGNED autoincremental, clave primaria |
| `string` | VARCHAR(255) de Blueprint; otra longitud aparece entre paréntesis |
| `N` | Admite NULL. Sin N, el campo es obligatorio en esquema |
| `U` | Restricción única, sola o compuesta según descripción |
| `FK` | Clave foránea; columna destino `id` si no se indica otra |
| `C`, `R`, `S` | Al borrar padre: cascade, restrict y set null |
| `timestamps` | `created_at` y `updated_at` TIMESTAMP N, gestionados por Eloquent |
| `remember_token` | VARCHAR(100) N, token de sesión recordada |
| `decimal(p,s)` | Precisión/escala declaradas; importes con dos decimales |

Los valores de roles, estados, `user_type`, estrellas y rangos se validan principalmente en aplicación. No se deben confundir con ENUM o CHECK de SQL inexistentes. FK de tipo `foreignId` es BIGINT UNSIGNED. Se omiten índices implícitos del motor y se señalan los explícitos importantes. Los datos derivados se enumeran al final y no son columnas.

## 2. Identidad y cuentas

### `users` — personal

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `name` | string; nombre mostrado |
| `email` | string U; acceso y notificaciones |
| `email_verified_at` | TIMESTAMP N; no existe verificación local obligatoria actual |
| `password` | string; hash, nunca contraseña en claro |
| `remember_token` | Convención común; oculto al serializar |
| `rol` | string; default Secretaria; aplicación admite Administrador, Secretaria, Entrenador |
| `activo` | BOOLEAN default true; controla acceso |
| `password_establecida` | BOOLEAN default true; false en personal nuevo hasta completar invitación |
| `admin_key` | string(20) N U; valor `unico` únicamente para administrador |
| Campos de personalización | Los ocho campos de la sección siguiente |

El modelo recalcula `admin_key` y protege la cuenta administradora contra baja, desactivación o cambio de rol. El índice único protege unicidad, pero no convierte escrituras SQL externas en operaciones autorizadas.

### Personalización común en `users` y `clientes`

| Campo | Tipo y default |
|---|---|
| `color_acento` | string; `#facc15` |
| `avatar_piel` | string; `claro` |
| `avatar_cabello` | string; `corto` |
| `avatar_barba` | string; `ninguna` |
| `avatar_atuendo` | string; `basica` |
| `avatar_color_atuendo` | string; `#1f2937` |
| `avatar` | string N; nombre de archivo propio en `public/images/avatars` |
| `apariencia` | JSON N; modelo lo convierte a array |

`avatar` se cuenta una vez por tabla, aunque su migración de incorporación sea distinta. Los valores permitidos de personalización y contraste se controlan en `Apariencia` y el controlador de configuración.

### `clientes` — miembros del gimnasio

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps`, `remember_token` | Convenciones comunes |
| `nombre` | string; identidad mostrada |
| `correo` | string U; equivalente de email para el guard cliente |
| `password` | string N; hash, nullable para cuenta solo Google |
| `google_id` | string N U; subject de Google; oculto en serialización |
| `activo` | BOOLEAN default true |
| `baneado_en` | TIMESTAMP N; auditoría del bloqueo actual |
| `baneado_por` | FK users N S; administrador que bloqueó |
| Campos de personalización | Ocho campos comunes anteriores |

Índice de `created_at`. Restaurar limpia la auditoría actual de ban; no existe tabla de historial de todos los bloqueos. El cliente tiene relaciones con rutinas, marcas, asistencia, membresías, solicitudes y ventas.

### `password_reset_tokens` y `cliente_password_reset_tokens`

Son tablas independientes, con la misma estructura. La primera pertenece al broker de personal y la segunda al de clientes.

| Campo | Tipo y uso |
|---|---|
| `email` | string PK; en clientes corresponde al valor de `correo` |
| `token` | string; hash del token de recuperación |
| `created_at` | TIMESTAMP N |

No tienen `id`, `updated_at` ni FK de identidad. Los brokers aplican expiración de 60 minutos y throttle de 60 segundos. La migración de separación eliminó tokens antiguos de personal para no reutilizar tokens de proveedor ambiguo.

## 3. Catálogo, rutinas y progreso

### `ejercicios`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `nombre`, `grupo_muscular` | string; identidad y clasificación |
| `subgrupo` | string N |
| `imagen`, `imagen_musculos` | string N; nombres de imágenes propias |
| `activo` | BOOLEAN default true |

El controlador valida nombre único, sin índice unique de esquema. No borrar ejercicios históricos mediante SQL: varias relaciones tienen cascada. La interfaz permite desactivar.

### `ejercicio_calificaciones`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `ejercicio_id` | FK ejercicios C |
| `user_id` | BIGINT UNSIGNED; sin FK a una sola tabla |
| `user_type` | string; `web` o `cliente` |
| `estrellas` | TINYINT UNSIGNED; aplicación exige 1–5 |

U(`ejercicio_id`, `user_id`, `user_type`) con nombre `ejercicio_user_unique`: cada identidad actualiza su mismo voto. No es un voto nuevo semanal.

### `entrenador_calificaciones`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `entrenador_id` | FK users C; la aplicación exige rol Entrenador activo |
| `cliente_id` | FK clientes C |
| `estrellas` | TINYINT UNSIGNED; aplicación exige 1–5 |

U(`entrenador_id`, `cliente_id`). El controlador exige vínculo por rutina; el índice no verifica roles ni asignaciones. Reintentos actualizan el mismo voto mediante upsert.

### `rutinas`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `user_id` | BIGINT UNSIGNED; propietario, sin FK por doble proveedor |
| `user_type` | string; guard `web` o `cliente` |
| `nombre`, `objetivo`, `nivel` | string; la aplicación añade longitudes/domino de nivel |
| `dias_por_semana` | TINYINT UNSIGNED default 1; interfaz valida 1–7 |
| `activa` | BOOLEAN default true |
| `asignado_por` | string N; nombre histórico del asignador |
| `asignado_por_id` | FK users N S; identidad estable para autorización |

Índices (`user_id`, `user_type`) y (`asignado_por_id`, `user_type`, `user_id`) llamado `rutinas_asignador_propietario_idx`. No hay FK única al propietario. Una rutina del cliente asignada por entrenador acredita vínculo, incluso si la consulta incluye historial; no hay columna `entrenador_id` en clientes.

### `rutina_dias`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `rutina_id` | FK rutinas C |
| `orden` | TINYINT UNSIGNED; orden de pestañas |
| `titulo` | string |
| `duracion_estimada_min`, `duracion_estimada_max` | SMALLINT UNSIGNED N; minutos |

El orden se administra en aplicación; no existe unique compuesto de orden.

### `rutina_ejercicios`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `rutina_dia_id` | FK rutina_dias C |
| `ejercicio_id` | FK ejercicios C |
| `orden` | TINYINT UNSIGNED |
| `series` | TINYINT UNSIGNED default 3 |
| `repeticiones` | string default `8-10`; permite rango descriptivo |
| `peso` | DECIMAL(6,2) N; null significa no especificado |
| `descanso_segundos` | SMALLINT UNSIGNED default 60 |

La reordenación exige la lista completa sin IDs duplicados ni ajenos y se aplica en transacción. Los límites del formulario no son constraints SQL.

### `sesiones_entrenamiento`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `user_id`, `user_type` | BIGINT UNSIGNED + string; propietario por guard |
| `rutina_id` | FK rutinas N S |
| `rutina_nombre` | string; snapshot conservado al borrar rutina |
| `dia_id` | FK rutina_dias N S |
| `dia_titulo` | string default `Entrenamiento`; snapshot |
| `iniciado_en`, `finalizado_en` | TIMESTAMP N |
| `duracion_segundos` | INTEGER UNSIGNED default 0 |
| `series_completadas`, `total_series` | INTEGER UNSIGNED default 0 |
| `estado` | string default `completado` |
| `notas` | TEXT N |
| `sesion_uuid` | UUID N U; reintento de finalización |

Índice (`user_type`, `user_id`). Aplicación comprueba propietario y rutina del UUID; series completadas ≤ total. No se persiste aquí cada casilla o segundo del entrenamiento todavía abierto.

### `personal_records`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `cliente_id` | FK clientes C |
| `ejercicio_id` | FK ejercicios C |
| `peso_kg` | DECIMAL(6,2); aplicación admite 0.5–999 |
| `repeticiones` | TINYINT UNSIGNED default 1; aplicación 1–100 |
| `verificado` | BOOLEAN default false |
| `verificado_por` | FK users N S |
| `fecha_verificacion` | TIMESTAMP N |
| `notas` | string N; aplicación ≤255 |

Índices (`cliente_id`, `ejercicio_id`, `created_at`), (`ejercicio_id`, `verificado`) y (`cliente_id`, `ejercicio_id`). Los campos de verificación no tienen flujo de certificación expuesto en esta revisión. El rango se deriva del conteo global; no hay columna rango ni tabla de insignias.

## 4. Asistencia

### `asistencias`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `cliente_id` | FK clientes C |
| `registrado_por` | FK users N S; actor de entrada |
| `salida_registrada_por` | FK users N S; actor de salida/cierre |
| `fecha_hora` | TIMESTAMP default CURRENT_TIMESTAMP |
| `fecha_salida` | TIMESTAMP N; null identifica visita abierta |
| `tipo_acceso` | string(30) default `entrada` |

Índices explícitos: (`cliente_id`, `fecha_hora`), `fecha_hora`, (`fecha_salida`, `cliente_id`), (`cliente_id`, `fecha_hora`, `fecha_salida`) y (`fecha_salida`, `fecha_hora`). La migración de rendimiento añade otro índice equivalente a (`cliente_id`, `fecha_hora`) con nombre distinto. No hay unique parcial de visita abierta; se protege con bloqueo del cliente y transacción en los flujos de asistencia. La fecha estimada del cierre automático no tiene columna adicional de certificación.

## 5. Membresías y cobros manuales

### `planes_membresia`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `nombre` | string(100) U |
| `precio` | DECIMAL(10,2) |
| `duracion_dias` | SMALLINT UNSIGNED |
| `condiciones` | TEXT N |
| `activo` | BOOLEAN default true |

La migración inicial incorpora Mensual, Trimestral y Anual. Son datos iniciales editables, no precios obligatorios del negocio.

### `solicitudes_membresia`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `cliente_id` | FK clientes C |
| `plan_id` | FK planes_membresia N S |
| `plan_nombre` | string(100); snapshot |
| `precio_acordado` | DECIMAL(10,2); snapshot |
| `duracion_dias` | SMALLINT UNSIGNED; snapshot |
| `condiciones` | TEXT N; snapshot |
| `estado` | string(20) default `pendiente`; también activada/cancelada según flujo |
| `resuelta_en` | TIMESTAMP N |
| `resuelta_por` | FK users N S |

Índices (`estado`, `created_at`) y (`cliente_id`, `estado`). La aplicación evita pendientes duplicadas del mismo plan bloqueando cliente; no existe unique SQL de ese caso.

### `membresias`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `cliente_id` | FK clientes C |
| `solicitud_id` | FK solicitudes_membresia N U S |
| `plan` | string(100); nombre histórico |
| `importe` | DECIMAL(10,2); importe de la membresía |
| `inicio`, `fin` | DATE; extremos inclusivos |
| `cancelada` | BOOLEAN default false |
| `activada_por` | FK users N S |

Índice (`cliente_id`, `cancelada`, `fin`). Estado Vigente/Programada/Vencida/Cancelada/En Pausa es derivado del modelo. No existe constraint SQL general de períodos sin solapamiento. La cancelación conserva pagos.

### `pagos_membresia`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `membresia_id` | FK membresias U R |
| `solicitud_id` | FK solicitudes_membresia U R |
| `registrado_por` | FK users N S |
| `importe` | DECIMAL(10,2); ingreso registrado |
| `pagado_en` | TIMESTAMP; fecha usada en reportes |
| `referencia` | string(100) N; dato manual |

Cada pago corresponde a una única solicitud/membresía. No hay método de pago, ID bancario, número de tarjeta ni bandera de simulación en esta tabla. La referencia escrita no acredita por sí sola liquidación bancaria. Las ventas simuladas no se almacenan aquí.

### `pausas_membresia`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `membresia_id` | FK membresias C |
| `cliente_id` | FK clientes C; debe concordar con la membresía en aplicación |
| `dias` | INTEGER UNSIGNED; días concedidos/efectivos según reanudación |
| `motivo` | string(255) |
| `estado` | string(30) default `pendiente` |
| `inicio_pausa`, `fin_pausa_estimada` | DATE |
| `fecha_reanudacion` | DATE N |
| `aprobada_por` | FK users N S |

Estados usados: pendiente, aprobada, rechazada, finalizada, reanudada_anticipada. Índices (`membresia_id`, `estado`) y (`cliente_id`, `estado`). Fechas y reglas de días se validan en aplicación. Una pausa aprobada deja de ser vigente por fechas aunque no exista transición automática a finalizada.

## 6. Inventario y ventas

### `productos`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `nombre` | string; no se copia como snapshot en detalle de venta |
| `precio` | DECIMAL(8,2); precio actual |
| `imagen` | string N; archivo propio |
| `categoria` | string(100) N |
| `stock` | INTEGER UNSIGNED default 0 |
| `activo` | BOOLEAN default true |
| `orden` | TINYINT UNSIGNED default 0 |

Índice (`activo`, `orden`). Precio/cantidad de catálogo se validan en controlador; solamente Admin puede editar existencias. Una simulación aprobada también descuenta stock. Las ventas guardadas impiden borrar su producto por FK restrict.

### `ventas`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `user_id` | FK users R; vendedor |
| `cliente_id` | FK clientes N S; null representa público general |
| `total` | DECIMAL(10,2); suma calculada en servidor |
| `metodo_pago` | string(50) default `Efectivo`; admite Efectivo/Tarjeta/Transferencia |
| `notas` | string(255) N |
| `venta_uuid` | UUID N U en esquema; obligatorio para POST de venta actual |
| `pago_simulado` | BOOLEAN default false, indexado; true en ventas de la interfaz actual |
| `efectivo_recibido` | DECIMAL(12,2) N; efectivo de prueba recibido |
| `cambio` | DECIMAL(12,2) N; diferencia calculada por servidor |
| `referencia_pago` | string(50) N; `SIM-` más UUID para prueba |

Índice de `created_at`, U(`venta_uuid`) y de `pago_simulado`. La migración de simulación mantiene filas existentes en false; no crea certificación bancaria. El scope `reales` filtra false y se aplica a indicadores/reportes. Campos recibido/cambio quedan nulos cuando no corresponden. Resultado de tarjeta rechazada no crea fila de venta. No se almacenan PAN, CVV ni vencimiento.

### `detalle_ventas`

| Campo | Tipo y uso |
|---|---|
| `id`, `timestamps` | Convenciones comunes |
| `venta_id` | FK ventas C |
| `producto_id` | FK productos R |
| `cantidad` | INTEGER UNSIGNED |
| `precio_unitario`, `subtotal` | DECIMAL(10,2); valores históricos calculados |

Índice (`venta_id`, `producto_id`). Cambiar precio actual no recalcula importes históricos; cambiar nombre puede cambiar la etiqueta presentada porque no existe snapshot del nombre. No hay ruta autorizada de eliminación de ventas, aunque borrar una venta directamente aún cascada sus detalles.

## 7. Auditoría e infraestructura

### `reportes_financieros_logs`

| Campo | Tipo y uso |
|---|---|
| `id` | Convención común |
| `user_id` | FK users C; quien solicita exportación |
| `tipo` | string(20); pdf o excel según controlador |
| `fecha_inicio`, `fecha_fin` | DATE; rango solicitado |
| `created_at` | TIMESTAMP default CURRENT_TIMESTAMP |

No tiene `updated_at`. Es log de intento, generado antes de producir archivo; no certifica descarga ni conserva el contenido exportado.

### `sessions`

| Campo | Tipo y uso |
|---|---|
| `id` | string PK; ID de sesión |
| `user_id` | BIGINT UNSIGNED N, indexado; sin FK |
| `ip_address` | string(45) N |
| `user_agent` | TEXT N |
| `payload` | LONGTEXT; sesión, cifrada según configuración |
| `last_activity` | INTEGER indexado; marca temporal |

El payload y cookies pueden contener contexto de guards; no interpretar `user_id` de esta tabla como FK inequívoca a users o clientes. No publicar ni transferir sesiones activas como material de pruebas.

### `cache` y `cache_locks`

| Tabla/campo | Tipo y uso |
|---|---|
| cache: `key` | string PK |
| cache: `value` | MEDIUMTEXT |
| cache: `expiration` | INTEGER indexado |
| cache_locks: `key` | string PK |
| cache_locks: `owner` | string |
| cache_locks: `expiration` | INTEGER indexado |

Son estado operativo del store de caché y bloqueos de Laravel. No tienen timestamps comunes. Vaciar caché puede afectar rate limiting y otros consumidores; no hacerlo como solución universal.

### `jobs`

| Campo | Tipo y uso |
|---|---|
| `id` | Convención común |
| `queue` | string indexado |
| `payload` | LONGTEXT |
| `attempts` | TINYINT UNSIGNED |
| `reserved_at` | INTEGER UNSIGNED N |
| `available_at`, `created_at` | INTEGER UNSIGNED; marcas temporales |

No tiene `updated_at`. Configurar colas no implica que todas las operaciones estén encoladas; los correos de venta actuales se envían desde el controlador de forma síncrona.

### `job_batches`

| Campo | Tipo y uso |
|---|---|
| `id` | string PK |
| `name` | string |
| `total_jobs`, `pending_jobs`, `failed_jobs` | INTEGER |
| `failed_job_ids` | LONGTEXT |
| `options` | MEDIUMTEXT N |
| `cancelled_at`, `finished_at` | INTEGER N |
| `created_at` | INTEGER |

Infraestructura de lotes; sin timestamps comunes.

### `failed_jobs`

| Campo | Tipo y uso |
|---|---|
| `id` | Convención común |
| `uuid` | string U |
| `connection`, `queue` | TEXT |
| `payload`, `exception` | LONGTEXT; puede contener contexto sensible |
| `failed_at` | TIMESTAMP default CURRENT_TIMESTAMP |

No publicar excepciones/payloads completos. Reintentar requiere evaluar si la operación es idempotente.

### `migrations`

| Campo | Tipo y uso |
|---|---|
| `id` | INTEGER UNSIGNED autoincremental PK |
| `migration` | string; nombre del archivo aplicado |
| `batch` | INTEGER; lote de aplicación |

Tabla del repositorio de migraciones de Laravel; no declara FK ni timestamps. No editar lotes manualmente para simular una actualización.

## 8. Datos derivados y límites de integridad

| Dato | Fuente de cálculo |
|---|---|
| Rango/porcentaje/insignia | Conteo global de personal_records y `RangoAtleta`; umbrales 0, 3, 8, 15 |
| Volumen de progreso | Suma de peso × repeticiones en registros filtrados |
| Mejor serie por ejercicio | Mayor peso × repeticiones; desempate por ID reciente |
| Estado de membresía | Cancelación, fechas y pausa vigente |
| Entrenadores del cliente | Rutinas `user_type=cliente` y `asignado_por_id` |
| Promedios/votos | Tablas de calificación; no campos editables en users/ejercicios |
| Ingresos financieros | Pagos de membresía y ventas con pago_simulado=false |
| Cambio simulado | Efectivo recibido menos total en centavos |

Las transacciones y `lockForUpdate` son parte del diseño; su comportamiento de concurrencia depende del motor. Las pruebas SQLite no acreditan bloqueos de fila MySQL. No hay restricciones SQL generales de visita abierta única o solapamiento de períodos. Mantener las escrituras dentro de servicios/controladores autorizados y comprobar migraciones más datos al restaurar.

## 9. Evolución relevante del esquema

| Fecha | Efecto y precaución |
|---|---|
| 09-09 | Normaliza Recepcionista a Secretaria; exige máximo un Admin; añade solicitudes, planes y pagos; no borrar cuentas para ocultar conflictos |
| 09-10 / 09-14 | Apariencia persistida y auditoría de bloqueo actual |
| 09-23 | Índices y pausas; no equivale a rendimiento medido |
| 10-07 | Tokens separados, eliminación de tokens antiguos ambiguos, FK financieras restrict, ID asignador y UUID de sesiones/ventas |
| 10-14 | Valoraciones entrenador-cliente y asociación de ilustraciones de productos; conserva imágenes de usuario según migración |
| 10-19 | Bandera y metadatos de pagos simulados; conserva ventas anteriores en false |

Revertir una migración puede eliminar columnas/datos o restaurar cascadas. `down` no es sustituto de respaldo. Para instalar y recuperar, seguir [Operación y despliegue](OPERACION_DESPLIEGUE.md).
