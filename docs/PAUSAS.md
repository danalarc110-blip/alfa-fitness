# Pausas y renovaciones ya pagadas

La pausa aprobada extiende la vigencia actual y desplaza por igual el inicio y fin de las renovaciones futuras no canceladas del mismo cliente. Conserva duración, huecos, importes y pagos: no cobra otra vez ni consume simultáneamente dos períodos pagados.

Reanudar anticipadamente retira los días de pausa no usados y adelanta los períodos futuros sin solaparlos con los anteriores. No modifica períodos legados que ya empezaron; tampoco mueve una pausa hacia el pasado. Las pausas futuras de una renovación se desplazan con ese período.

Solicitud pendiente o rechazada no cambia fechas. Se mantiene el máximo existente de 30 días acumulados. Todas las operaciones bloquean primero al cliente y después sus filas relacionadas, dentro de transacciones. Los datos existentes no se reparan ni recalculan automáticamente.

Prueba de regresión: `php artisan test tests/Feature/PausasRenovacionTest.php`. La concurrencia real bajo MySQL requiere una instancia con credenciales válidas; SQLite no sustituye esa comprobación.
