# Guía de uso de los módulos mejorados de Alpha Fitness

**Fecha:** 14 de octubre de 2026.\
**Alcance:** Productos, Punto de Venta, Entrenadores y seguimiento de clientes.\
**Estado:** `VERIFICADO_CON_LIMITES`; pasos contrastados por lectura con la versión y evidencia indicadas en el [Informe de presentación](INFORME_PRESENTACION.md). No es un manual completo de toda la aplicación.

## Contenido

- [Acceso y navegación](#acceso-y-navegación)
- [Cliente](#cliente)
- [Secretaria](#secretaria)
- [Administrador](#administrador)
- [Entrenador](#entrenador)
- [Problemas frecuentes](#problemas-frecuentes)
- [Comprobación y fuentes](#comprobación-y-fuentes)

## Acceso y navegación

**Antes de empezar:** el sistema debe estar disponible y la cuenta debe estar activa. El personal utiliza el acceso de personal; el miembro utiliza la pestaña **Clientes** de la pantalla de inicio de sesión. No utilizar credenciales reales en una presentación pública.

1. Abrir la dirección del sistema y entrar con el acceso correspondiente a la cuenta.
2. Consultar el menú lateral. En móvil, abrirlo mediante el control del menú.
3. Elegir **Productos**, **Entrenadores** o **Ventas (TPV)** según los permisos del perfil. El Entrenador dispone además de **Mis clientes**.
4. Al terminar en un dispositivo compartido, pulsar **Cerrar sesión**.

**Resultado:** el sistema muestra las funciones autorizadas. Cambiar manualmente una dirección no concede permisos.

**Qué hacer si no puede entrar:** comprobar el perfil de acceso y el correo. Usar **¿Olvidaste tu contraseña?** si corresponde. Una invitación pendiente requiere establecer la contraseña mediante el enlace privado. La recepción de correos depende de la configuración del servicio de correo; su entrega real no se verificó en esta actualización.

## Cliente

### Consultar productos

**Precondición:** sesión de Cliente activa.

1. Abrir **Productos**.
2. Escribir el nombre o la categoría en **Buscar producto** y pulsar **Buscar**.
3. Consultar nombre, categoría, precio, imagen y estado **Disponible** o **Agotado**.
4. Usar **Limpiar** para retirar la búsqueda y la paginación para recorrer los resultados.
5. Para comprar, acudir a recepción, donde el personal registra la venta.

**Resultado:** se muestran productos activos; el catálogo no expone cantidades exactas al Cliente. Una imagen con el texto **Imagen ilustrativa** es una representación del producto.

**Qué hacer si no aparece un producto:** limpiar el filtro y buscar por categoría. Puede estar inactivo o no estar registrado. Consultar con recepción.

### Calificar a un entrenador

**Precondición:** el entrenador está activo y existe una rutina del cliente asignada por ese entrenador. El sistema determina esa relación automáticamente; no hay una asignación independiente en este módulo.

1. Abrir **Entrenadores** y buscar por nombre si hace falta.
2. Localizar al entrenador vinculado y elegir el botón de 1 a 5 estrellas deseado.
3. Esperar la confirmación y comprobar **Tu valoración**.
4. Para cambiarla, elegir otro valor en **Actualiza tu valoración**.

**Resultado:** el sistema mantiene una valoración por cliente y entrenador. Cambiarla actualiza el mismo voto y el promedio. Los botones de estrellas pueden activarse con teclado.

**Qué hacer si no aparecen las estrellas:** el mensaje indica que podrá valorar cuando el entrenador le asigne una rutina. Solicitar al entrenador que compruebe la asignación. Si se elimina su última rutina asignada por él, se revoca el permiso para votar o actualizar el voto.

## Secretaria

### Crear o editar un producto

**Precondición:** sesión de Secretaria activa. El perfil puede gestionar el catálogo y realizar ventas; el Administrador establece las cantidades de existencias.

1. Abrir **Productos**.
2. Para crear, completar **Nuevo producto** con nombre, precio y, opcionalmente, categoría e imagen.
3. Pulsar **Guardar producto**. El nuevo producto se crea con existencias en cero.
4. Para editar, abrir **Editar producto** en su tarjeta, cambiar los campos necesarios y pulsar **Guardar cambios**.
5. Avisar al Administrador cuando deba establecer o corregir existencias.

**Resultado:** se guardan los datos del catálogo. No se ofrece un campo para modificar existencias a Secretaria. Las ventas sí descuentan unidades automáticamente.

La opción **Visible y disponible** controla si el producto está activo. Un producto activo con cero existencias aparece como agotado y no puede añadirse a una venta.

**Qué hacer si una imagen se rechaza:** usar JPG, PNG o WebP, de hasta 2 MB, un máximo de 5000 píxeles por lado y un máximo de 16.777.216 píxeles en total. La imagen es opcional. El precio admite hasta dos decimales, desde 0 hasta 999999.

### Registrar una venta presencial

**Precondición:** hay al menos un producto activo con existencias. Si se selecciona un cliente registrado, debe estar activo. La venta registra el método del pago presencial.

1. Abrir **Ventas (TPV)** o el botón **Punto de Venta (TPV)** desde Productos.
2. Pulsar **Registrar Venta** para abrir el formulario.
3. En **1. Añade productos**, buscar por nombre o categoría y pulsar **Añadir**.
4. En **2. Ajusta la venta**, usar `+`, `−` o el campo de cantidad. Añadir el mismo producto otra vez incrementa su línea existente. Usar **Quitar** para retirarlo.
5. Elegir un cliente activo o **Público General**. Seleccionar **Efectivo**, **Tarjeta** o **Transferencia**; completar notas si corresponde.
6. Pulsar **Revisar venta** y comprobar productos, cantidades, cliente, método y total.
7. Si necesita corregir, pulsar **Editar venta** y revisar nuevamente.
8. Pulsar **Registrar venta** una sola vez y esperar la confirmación.
9. Abrir **Ver / Enviar Comprobante** para consultar la venta registrada.

**Resultado:** el servidor calcula el total, registra la venta y descuenta existencias dentro de la misma transacción. El botón muestra **Registrando...** mientras se envía el formulario. Cada producto admite cantidades enteras de 1 a 999, limitadas además por las existencias; la venta admite hasta 100 productos distintos.

**Qué hacer si hay un error:** conservar el formulario recuperado, leer el mensaje, reducir cantidades o quitar el producto no disponible y revisar de nuevo. El sistema conserva los datos y el identificador de reintento tras errores de validación. Si se pierde la conexión y el resultado es incierto, comprobar primero el historial para evitar iniciar una venta nueva que corresponda al mismo cobro. La recuperación descrita no equivale a guardar un borrador permanente fuera de esa página.

### Consultar historial y comprobantes

**Precondición:** sesión autorizada para TPV; venta existente.

1. En **Historial de Ventas**, introducir un número de ticket, cliente, producto o texto de las notas; ajustar fechas y método de pago si corresponde.
2. Pulsar **Filtrar**. La fecha final debe ser igual o posterior a la inicial.
3. Abrir el comprobante desde la venta encontrada.
4. Para imprimir, usar **Imprimir Ticket** y el diálogo del navegador.
5. Para solicitar el envío por correo, usar **Enviar por Correo**, revisar el destinatario y pulsar **Enviar Ahora**.

**Resultado:** se muestra el detalle del ticket. La impresión depende del navegador y del dispositivo; la entrega del correo depende del servicio configurado.

**Qué hacer si falla el correo:** la venta sigue registrada. Comprobar el destinatario y solicitar al responsable que revise el servicio de correo. No registrar otra venta para reenviar su comprobante. La actualización no comprobó SMTP real ni una impresora física.

## Administrador

### Gestionar el catálogo y establecer existencias

**Precondición:** sesión de Administrador activa; cantidad real del producto comprobada por el responsable.

1. Abrir **Productos**.
2. Para crear un producto, completar los campos de **Nuevo producto**, incluida **Existencias**, y pulsar **Guardar producto**.
3. Para corregir o reponer un producto existente, abrir **Editar producto**.
4. Introducir en **Existencias** la cantidad total que debe quedar registrada; el campo representa el saldo final, no las unidades que se suman.
5. Revisar precio, categoría y **Visible y disponible**; pulsar **Guardar cambios**.
6. Comprobar la cantidad mostrada en la tarjeta.

**Resultado:** se guarda el saldo de existencias. Solo el Administrador dispone de ajuste manual; las ventas posteriores descuentan automáticamente las unidades vendidas.

**Qué hacer si se rechaza la cantidad:** usar un entero entre 0 y 999999. No usar números negativos ni decimales. Confirmar el saldo antes de guardar para no reemplazarlo por una cantidad de reposición equivocada.

El Administrador también puede seguir el procedimiento de venta e historial descrito para Secretaria.

### Gestionar el personal y consultar seguimiento

**Precondición:** sesión de Administrador activa. Para una invitación, debe existir un correo válido no utilizado por otro usuario del personal.

1. Abrir **Entrenadores** y usar los filtros **Todo el personal**, **Entrenadores** o **Secretarías**.
2. Para crear, desplegar **Agregar personal (Entrenador o Secretaria)**, completar nombre, correo, rol y foto opcional; pulsar **Crear e invitar**.
3. Leer el resultado: la creación de la cuenta y la entrega de la invitación son resultados distintos.
4. Para cambiar datos de una cuenta, desplegar **Editar personal**, revisar rol y acceso **Activo/Inactivo**, y pulsar **Guardar cambios**.
5. Para consultar clientes y progreso, abrir la ruta `/entrenador/clientes` en la misma dirección del sistema. El título es **Clientes y progreso**; esta ruta administrativa no tiene enlace propio en el menú lateral de la versión examinada.
6. Elegir **Ver rutinas** o **Ver progreso** en el cliente requerido.

**Resultado:** el Administrador puede gestionar personal y consultar seguimiento de cualquier cliente, aunque no tenga una rutina asignada. La búsqueda administrativa del personal admite nombre o correo.

**Qué hacer si queda Invitación pendiente:** comprobar que el colaborador estableció su contraseña. Si el sistema informa un problema de envío, revisar la configuración de correo con el responsable antes de crear otra cuenta. No compartir enlaces privados en la presentación.

## Entrenador

### Consultar Mis clientes, rutinas y progreso

**Precondición:** sesión de Entrenador activa y al menos una rutina asignada por él a un cliente. La vinculación se obtiene de esas rutinas, incluso si están archivadas.

1. Abrir **Mis clientes** en el menú.
2. Buscar por nombre y pulsar **Buscar**, o usar **Limpiar** para retirar el filtro.
3. Consultar la tarjeta del cliente, su estado y las rutinas que le ha asignado.
4. Pulsar **Ver rutinas** para consultar su objetivo, nivel, días y ejercicios. Las rutinas de otros entrenadores no aparecen en este detalle.
5. Pulsar **Ver progreso** para consultar marcas e historial del cliente. El progreso reúne sus registros, no solo los de una rutina específica.
6. Elegir un ejercicio y pulsar **Filtrar** si desea limitar la consulta; usar **Limpiar** para volver a todos.

**Resultado:** cada cliente aparece una sola vez aunque tenga varias rutinas del mismo entrenador. El seguimiento permite consultar registros, sin edición de esos datos ni información financiera en esta vista. El gráfico se muestra cuando hay al menos dos registros en la selección.

**Qué hacer si no aparece un cliente:** limpiar la búsqueda y comprobar la asignación desde **Entrenamientos**. Si el cliente no tiene una rutina asignada por ese entrenador, el sistema deniega también el acceso directo a su detalle o progreso. Eliminar la última asignación revoca ese acceso.

El Entrenador puede consultar Productos y el directorio Entrenadores, pero no registra ventas ni emite valoraciones en nombre de clientes.

## Problemas frecuentes

| Situación | Acción recomendada |
| --- | --- |
| El catálogo no tiene resultados | Limpiar la búsqueda y revisar nombre o categoría; comprobar con recepción si el producto está activo |
| Secretaria no ve el campo Existencias | Es el permiso previsto; solicitar al Administrador el ajuste del saldo |
| Un producto aparece agotado | Esperar reposición o elegir otro; estar activo no implica tener unidades |
| No se puede revisar una venta vacía | Añadir al menos un producto con existencias |
| Aparece stock insuficiente al registrar | Ajustar la cantidad a las existencias actuales o quitar el producto y volver a revisar |
| El cliente del carrito dejó de estar disponible | Elegir otro cliente activo o Público General |
| El resultado del envío de venta es incierto | Consultar el historial antes de iniciar otra venta; un reintento conserva la identidad de la venta recuperada |
| No aparecen botones para calificar | Comprobar que es una sesión de Cliente y existe una rutina asignada por ese entrenador activo |
| Mis clientes está vacío | Confirmar asignaciones del Entrenador y quitar filtros; consultar el estado vacío y su acceso a Entrenamientos |
| Una ruta devuelve acceso denegado | Volver al menú autorizado y comprobar perfil/asignación; no cambiar identificadores para intentar acceder a otros clientes |
| No aparece gráfico de progreso | Verificar que hay al menos dos registros para el filtro elegido |
| No llega el comprobante por correo | Revisar destinatario y configuración de correo; consultar la venta ya registrada sin duplicarla |

## Comprobación y fuentes

Los procedimientos se contrastaron con controles visibles de las vistas, validaciones de controladores, rutas y matriz de permisos. La validación funcional anterior cubrió catálogo, valoración actualizable, seguimiento autorizado, venta y recuperación de errores; se detalla en [Evidencia y límites](INFORME_PRESENTACION.md#evidencia-y-límites).

| Procedimiento | Fuentes revisadas |
| --- | --- |
| Acceso, menú y cierre de sesión | [inicio de sesión](../../resources/views/auth/login.blade.php), [menú](../../resources/views/partials/sidebar.blade.php), [rutas](../../routes/web.php) |
| Productos y existencias | [ProductoController.php](../../app/Http/Controllers/ProductoController.php), [vista Productos](../../resources/views/productos/index.blade.php), [permisos](../../app/Support/Acceso.php), [ImagenSegura.php](../../app/Services/ImagenSegura.php), [esquema inicial de productos](../../database/migrations/2026_08_27_110000_create_productos_table.php) |
| Venta y recuperación | [VentaController.php](../../app/Http/Controllers/VentaController.php), [vista POS](../../resources/views/ventas/index.blade.php), [ventas.js](../../resources/js/ventas.js) |
| Comprobante | [vista del comprobante](../../resources/views/ventas/comprobante.blade.php), método `enviarCorreo` de [VentaController.php](../../app/Http/Controllers/VentaController.php) |
| Personal y valoraciones | [EntrenadorController.php](../../app/Http/Controllers/EntrenadorController.php), [vista Entrenadores](../../resources/views/entrenadores/index.blade.php) |
| Mis clientes y progreso | [ClientesEntrenador.php](../../app/Services/ClientesEntrenador.php), [vista clientes](../../resources/views/entrenador/clientes.blade.php), [vista rutinas](../../resources/views/entrenador/cliente.blade.php), [ProgresoController.php](../../app/Http/Controllers/ProgresoController.php), [vista progreso](../../resources/views/progreso/entrenador.blade.php) |

**Límites de esta guía:** no se repitieron los procedimientos en producción para redactarla. No se incluyen credenciales, datos reales, nuevos diagramas ni nuevas capturas. SMTP real, impresora física y concurrencia real siguen sin verificación en esta actualización. Si cambia la versión del sistema, revisar los pasos afectados antes de utilizar la guía.
