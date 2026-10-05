# Administrador y datos ficticios de prueba

Las migraciones y los seeders ordinarios no crean contraseñas públicas. Usa estos comandos desde una consola en la raíz del proyecto después de `php artisan migrate`.

## Crear el administrador real

```powershell
php artisan alpha:crear-admin
```

Escribe el nombre y correo real cuando la consola los solicite. Introduce una contraseña privada de 12 a 72 caracteres con mayúsculas, minúsculas, número y símbolo, y repítela. Si incluyes caracteres que ocupan más espacio, como emojis, puede ser necesario acortarla: nunca se guarda una contraseña truncada. La consola oculta la contraseña y el comando no la imprime ni admite pasarla como argumento. Si aparece `Administrador creado`, abre el login, selecciona **Personal** e ingresa con esos datos. Si ya existe un administrador, el comando se detiene sin cambiar ninguna cuenta. El índice del administrador único impide crear un segundo administrador.

Este comando sirve también en producción para la creación inicial. No lo uses como restablecimiento de contraseña. Conserva la contraseña en un gestor privado.

## Preparar una instalación de demostración

Solo en una base local separada de datos reales, con `APP_ENV=local`:

```powershell
php artisan alpha:datos-prueba
```

Si no existe administrador, crea uno ficticio; agrega secretaria, entrenador y cliente con correos bajo `alpha.example.test`. Las contraseñas son aleatorias y se muestran únicamente en la consola después de crear las cuentas. Guárdalas de forma privada: al repetir el comando no se muestran ni se cambian. Nunca pegues esa salida en GitHub ni en un informe.

También agrega un ejercicio y un plan exclusivos de demostración, solicitud activada, membresía y pago ficticio, una visita ya cerrada, una rutina con un día y ejercicio, y un récord. No afirma que un cliente ficticio haya aceptado documentos legales. Los catálogos y cuentas existentes se preservan. Los mismos registros se reutilizan al repetir el comando; no se renueva automáticamente una membresía de demostración vencida ni se reactiva una cuenta desactivada.

Para añadir una venta ficticia de una unidad de un producto exclusivo de demostración:

```powershell
php artisan alpha:datos-prueba --con-venta
```

Se crea `Producto Demo Alpha` con cinco unidades si no existía y se descuenta una sola unidad al registrar la venta. Esta opción requiere una base de demostración con menos de cinco productos, o que su producto exclusivo ya exista; si los cinco espacios están ocupados por el catálogo, se detiene y revierte toda la operación. No borra productos para hacer espacio ni vende inventario real. Repetir el comando no duplica la venta ni vuelve a descontarla. No afecta el inventario de los demás productos ni envía comprobantes por correo. La operación se ejecuta en una transacción. En una base con el catálogo completo, usa el comando sin `--con-venta`.

El comando rechaza entornos distintos de `local` y `testing`. No cambies `APP_ENV` de un servidor real para permitir datos de prueba. Estos registros son ficticios y no deben mezclarse con reportes comerciales. No se proporciona un borrado masivo: elimina una base de demostración únicamente cuando hayas comprobado que es esa base y que no contiene datos reales.
