# Verificación opcional del administrador

Desactivada por defecto: el acceso existente no cambia. Solo el administrador puede configurar su propio factor.

En el menú **Verificación en dos pasos**, escribir la contraseña actual y pulsar **Preparar autenticador**. En una aplicación autenticadora elegir **Añadir cuenta → Introducir clave**, nombre Alpha Fitness, seis dígitos, SHA-1, 30 segundos. Copiar la clave manual, escribir el código y la contraseña y pulsar **Confirmar y activar**.

Guardar los ocho códigos de recuperación que se muestran UNA VEZ en un lugar privado fuera del equipo. Cada uno tiene 128 bits aleatorios y se consume una vez. No guardarlos en GitHub, correo compartido, informes ni capturas. Si se pierde el teléfono y todos los códigos, no existe un botón público de recuperación: será necesario recuperar un respaldo protegido con asistencia técnica acreditando identidad.

Al activar se revocan cookies recordadas y las otras sesiones deben iniciar nuevamente. Los nuevos accesos requieren contraseña y código; el mismo código temporal no puede reutilizarse. No se emiten nuevas cookies «recordarme» para omitir el factor. Cambiar contraseña o factor invalida la comprobación de sesiones anteriores. Desactivar exige contraseña y código vigente o de recuperación.

El secreto y los hashes de recuperación se cifran con APP_KEY; jamás regenerarla durante una actualización. Respaldar clave y base juntos, con acceso privado. La hora del servidor/teléfono debe estar sincronizada. El factor protege accesos nuevos, no corrige malware en un dispositivo ya autorizado ni phishing.

Algoritmo verificado con los vectores SHA-1 del [RFC 6238](https://www.rfc-editor.org/rfc/rfc6238), incluyendo fechas de 64 bits. Implementación propia pequeña, sin servicios QR externos; no es una certificación criptográfica independiente.
