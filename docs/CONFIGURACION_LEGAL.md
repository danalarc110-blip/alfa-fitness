# Configurar documentos y conservar su versión aceptada

Antes de uso público, un abogado de El Salvador debe revisar los documentos y el responsable debe completar los datos reales. Mantener marcadores pendientes permite identificar lo que falta, pero no acredita un aviso completo ni cumplimiento.

## Completar los datos

Edita las variables `LEGAL_*` de tu `.env` privado: nombre y dirección del responsable, correo y teléfono, persona de contacto, proveedores y países, períodos de retención por categoría, cancelaciones, horarios y atención de accidentes. No pongas contraseñas, claves o información interna secreta en esos campos: su contenido aparece en documentos públicos. Conserva `.env` fuera de GitHub. Los responsables técnico y del expediente de incidentes se completan en el procedimiento interno de brechas.

Los marcadores del aviso se muestran como texto; no admiten insertar HTML ni enlaces Markdown desde la configuración. Para cambiar el contenido o añadir un enlace, edita el documento correspondiente bajo `docs` y revísalo antes de publicar.

## Versión y archivo de evidencia

`LEGAL_VERSION` identifica el conjunto de los cuatro documentos públicos: privacidad, términos, seguridad/lesiones y derechos sobre datos. Admite hasta 32 caracteres: letras, números, punto, guion o guion bajo; empieza con una letra o número. Ejemplo de identificación: `2026-10-04.1`. No reutilices una versión que ya fue aceptada cuando cambias el texto o un dato legal de la configuración.

Al aceptar una nueva alta de cliente, el sistema guarda en `versiones_legales` los cuatro textos con los datos de configuración ya resueltos y una huella SHA-256. El cliente conserva fecha y versión aceptada. El archivo se crea una vez por versión, no una copia por cliente. Los finales de línea se normalizan para evitar diferencias artificiales entre Windows y Linux; la huella usa un orden estable para los documentos.

Los textos archivados permanecen en la base de datos y sus copias protegidas, aunque después se cambien `.env` o los documentos fuente. Desde la aplicación no se permite modificar ni eliminar una versión ya archivada. Si el mismo identificador tiene contenido distinto, se rechazan nuevas aceptaciones y el responsable debe publicar una versión nueva. Las cuentas anteriores pueden seguir iniciando sesión y sus columnas históricas no se rellenan con aceptaciones inventadas.

La huella facilita comprobar integridad; no es una firma electrónica ni un sello de tiempo independiente. El administrador de la base puede alterar datos por fuera de la aplicación, por lo que se necesitan permisos mínimos, copias protegidas y control de cambios para conservar la evidencia. La migración es reversible, pero revertirla elimina estos archivos de aceptación: no ejecutes un rollback en una instalación con aceptaciones reales sin preservar antes su evidencia.

## Publicar una actualización legal

1. Revisa con el abogado los cambios de texto y completa los datos reales de `.env`.
2. Asigna un valor nuevo a `LEGAL_VERSION`, por ejemplo una fecha con una revisión. Registra fuera del repositorio público quién aprobó el contenido y cuándo.
3. Ejecuta `php artisan optimize:clear`. Abre los cuatro enlaces legales del login y verifica responsable, correo, retención y versión mostrada.
4. Comunica los cambios relevantes a las personas afectadas por el medio previsto en el aviso. La aplicación no envía esa comunicación automáticamente. Un cambio de versión no supone que clientes antiguos hayan aceptado una nueva finalidad; las finalidades opcionales requieren su propia autorización.
5. Comprueba un nuevo registro en una base de prueba y verifica la versión guardada; conserva los respaldos de la instalación real.

El servicio `ConsentimientoLegal` proporciona `contenidoActual(slug)` sin acceso a base de datos; `contenido(slug, version)` devuelve una versión archivada específica; y `aceptar(cliente)` guarda el archivo y la aceptación en una transacción. Solo el flujo de registro con aceptación afirmativa debe llamar `aceptar`. Los cuatro slugs válidos son `privacidad`, `terminos`, `lesiones` y `derechos`. Una versión inexistente no se sustituye silenciosamente por el texto actual.
