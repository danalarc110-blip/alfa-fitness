# Gestión complementaria

Los módulos añadidos viven en `/gestion` y conservan las rutas y pantallas anteriores. Requieren sesión autenticada y mantienen el tema claro/oscuro y diseño responsive del layout común.

| Módulo | Quién puede usarlo | Qué incluye |
| --- | --- | --- |
| `/gestion/usuarios` | Administrador | Buscar, paginar, crear, invitar, editar nombre/correo/rol, desactivar y reactivar empleados. |
| `/gestion/clientes` | Administrador y Secretaria | Buscar, paginar, crear, editar nombre/correo/entrenador, desactivar y reactivar clientes. |
| `/gestion/planes` | Administrador | Buscar, paginar, crear, editar precio/duración/condiciones, desactivar y reactivar planes. |
| `/gestion/sesiones` | Administrador y Secretaria: todas; Entrenador: propias; cliente: solo lectura de propias | Agenda con búsqueda, filtros y paginación; crear, editar, completar y cancelar sesiones. |

## Decisiones tomadas

- Todas las bajas son lógicas: los botones desactivan cuentas/planes o cancelan sesiones. No eliminan pagos, asistencias, solicitudes ni rutinas.
- El administrador único no se puede editar, desactivar, reinvitar ni reemplazar desde la gestión de empleados. Puede modificar su nombre y contraseña desde sus ajustes originales.
- Los roles asignables son exclusivamente `Secretaria` y `Entrenador`; nunca se puede crear otro administrador.
- Un empleado nuevo recibe el enlace privado del broker Laravel existente, válido por 60 minutos. No se muestra ni se envía una contraseña generada. Sin SMTP operativo se conserva la cuenta pendiente y se informa que hay que configurar correo y reenviar la invitación.
- Alta de clientes por recepción: contraseña robusta de 12 caracteres con mayúsculas, minúsculas, números y símbolos. Debe introducirla el titular. No se establece contraseña común ni se falsifica su aceptación legal; las columnas de consentimiento permanecen sin aceptación. Para autoservicio sigue disponible el registro original con consentimiento.
- `clientes.entrenador_id` es opcional y solo admite empleados activos con rol `Entrenador`. Clientes existentes siguen sin entrenador asignado. Desactivar un entrenador conserva sus referencias e historial.
- La agenda impide solapamientos tanto del entrenador como del cliente, permite citas contiguas y conserva sesiones canceladas. Una sesión completada conserva su intervalo histórico; una cancelada libera horario.
- El guard `cliente` prevalece cuando ambos guards coexisten. Una sesión de cliente nunca adquiere permisos de empleado.
- Se admiten fechas históricas para registrar sesiones realizadas; finalización siempre debe ser posterior al inicio. La agenda interpreta horas en la zona configurada del gimnasio.
- Las notas son de coordinación, opcionales, con máximo de 500 caracteres. No se deben incorporar diagnósticos ni datos médicos.

## Migración y reversibilidad

`2026_10_04_200000_add_gestion_complementaria.php` agrega `clientes.entrenador_id` y la tabla `sesiones_entrenador`, con claves foráneas e índices para agenda. No modifica registros anteriores. Su reversión elimina únicamente estas adiciones; una vez que existan sesiones, exportarlas antes de revertir.

La creación/actualización usa transacciones y bloquea los entrenadores y clientes implicados en orden estable. MySQL proporciona la serialización entre escrituras concurrentes. Las pruebas automáticas SQLite verifican comportamiento e invariantes secuenciales, no certifican bloqueo concurrente real de MySQL.

## Pruebas

`php artisan test tests/Feature/GestionComplementariaTest.php` verifica invitaciones, validación, admin protegido, bajas con historial, snapshot de precio, agenda, conflictos horarios, IDOR y coexistencia de guards.

Los formularios contienen CSRF, validación de servidor y confirmación antes de desactivar/cancelar. La entrega de correos reales requiere el servidor SMTP del gimnasio.
