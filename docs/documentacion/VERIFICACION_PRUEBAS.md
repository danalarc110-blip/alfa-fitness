# Alpha Fitness Verificación y pruebas

**Edición 2.0** · 19 de octubre de 2026. Tareas `T-REVISION-20261019` y `T-DOC-20261019`. Revisión base `06d31c2984133a9596bff0db21935399eb8c658c` más las fuentes identificadas en `REVISION.json`.

## Resultado y alcance

La revisión comprobó ventas simuladas, progreso, permisos y navegación por los cuatro roles. Los últimos ajustes documentales corrigen el texto de recuperación para clientes y personal y la descripción del almacenamiento del navegador en la política pública. Los módulos verificados funcionan en el entorno local examinado. La publicación sigue pendiente de configuración y validación de servicios externos.

**Estado: VERIFICADO CON LÍMITES.** Las pruebas utilizaron PHP 8.2.12, PHPUnit 11.5.56, SQLite y Chrome con tamaños móviles emulados. No se probaron cargos reales, entrega SMTP, Google con credenciales reales, concurrencia de MySQL, dispositivos físicos ni carga de producción. La implementación y QA funcional fueron realizadas por el mismo responsable; hubo contraste documental adicional, que no constituye una auditoría de seguridad independiente.

## Evidencia ejecutada

Los comandos se ejecutaron desde la raíz del repositorio. En Windows se utilizó `C:/xampp/php/php.exe` y `npm.cmd`; los comandos genéricos siguientes suponen que PHP y Node están en PATH.

| Comprobación | Resultado | Qué demuestra |
| --- | --- | --- |
| `php vendor/phpunit/phpunit/phpunit` | 234 pruebas, 1842 aserciones, 0 fallos, salida 0. | Reglas, permisos, validaciones y regresión automatizada en SQLite aislado. Se repitió tras los últimos ajustes de texto. |
| `node tools/frontend_regression_check.cjs` | Ocho grupos aprobados, salida 0. | Reintentos, fallos, doble envío, nombres maliciosos, botones con nombre y modales por teclado en el entorno controlado del script. |
| `npm.cmd run build` | Salida 0. | Construcción Vite de CSS y JavaScript utilizados por la revisión visual. |
| `node tools/mobile_review_check.cjs` | Cuatro grupos de rol aprobados y cero errores JavaScript de página; salida 0. | Recorridos de aplicación con recursos compilados, permisos y estado de la base sintética. |
| `node tools/mobile_review_check.cjs --captures-only` | Salida 0; nueve imágenes finales seleccionadas. | Capturas de pantallas con datos sintéticos, sin registrar nuevas ventas para obtenerlas. |
| Pint en los PHP modificados de la revisión funcional | Salida 0. | Estilo de los controladores, servicios, migración, fixture y pruebas afectadas; no se reformateó todo el repositorio. |
| `git diff --check` | Salida 0. | Ausencia de errores de espacios en el diff revisado. |
| Migración incremental del 19 de octubre | Salida 0 con respaldo previo; comprobación SQLite `quick_check` correcta. | Nuevas columnas de simulación disponibles y ventas originales conservadas sin reclasificar. |

Vite informó que `/images/gym-bg.webp` no se resolvió durante la compilación. El archivo existe y se sirvió correctamente en los recorridos; el aviso queda registrado, sin afirmar que faltó el recurso. No se actualizaron dependencias para ocultarlo.

Los logs completos y el JUnit se conservaron en el almacenamiento privado de desarrollo. No se distribuyen bases de datos, credenciales de QA ni mensajes con enlaces de contraseña en este paquete. `REVISION.json` contiene huellas de las fuentes y un resumen público de resultados. Las auditorías `composer audit` y `npm audit` figuran como procedimientos recomendados en el README; no se presentan como ejecutadas de nuevo en esta revisión.

## Cobertura funcional relevante

| Área | Escenarios y referencias |
| --- | --- |
| Efectivo de práctica | Importe insuficiente rechazado; total $2.00, recibido $10.00 y cambio $8.00; cálculo exacto en servidor. `PagosSimuladosTest`. |
| Tarjeta virtual | Rechazo sin venta ni descuento; aprobación posterior; reenvío del mismo UUID sin duplicación. Rechazo de campos de número de tarjeta y CVV. `PagosSimuladosTest`, `VentaIntegridadTest`. |
| Finanzas | Simulaciones identificadas en comprobantes y excluidas de agregados reales y exportaciones. Ingreso de membresía basado en pagos registrados. `PagosSimuladosTest`, `AnaliticaFinancieraTest`, `ReportesGerencialesTest`. |
| Productos | Permisos diferentes para editar catálogo y establecer stock. Archivo de imágenes validado. `ProductosPermisosTest`, `ProductosTest`. |
| Entrenadores | Invitaciones pendientes, cuentas individuales, clientes asignados y rechazo de consultas ajenas. `AccesoEntrenadoresTest`, `GestionPersonalTest`, `ProgresoEntrenadorTest`. |
| Progreso | Rango global estable al filtrar, gráfica del mismo ejercicio, cuatro insignias propias, registros privados y formularios móviles. `ProgresoMovilTest`, `ProgresoTest`, `PrEstadisticasTest`. |
| Membresías | Solicitud, términos capturados, activación por Secretaria, rechazo de doble resolución, renovación y pausas sin superposiciones indebidas. `MembresiasTest`, `PausaRenovacionIntegridadTest`. |
| Asistencia | Cuenta activa y ausencia de pausa vigente, visita única abierta, salida, cierre de visitas huérfanas y exportación segura. La aplicación no exige membresía vigente para registrar una entrada. `AsistenciaTest`, `AsistenciaAvanzadaTest`, `AsistenciaExportSeguridadTest`. |
| Acceso y privacidad | Permisos, cuentas inactivas, recuperación con proveedores aislados y protección de recursos ajenos. `SeguridadTest`, `PasswordResetIsolationTest`, `HardeningRegressionTest`. |
| Rutinas y sesiones | Propiedad, copia al asignar, normalización del UUID y finalización sin duplicado al reintentar. `RutinasAvanzadasTest`, `SesionEntrenamientoTest`, `SesionUuidNormalizacionTest`. |

Los archivos de pruebas se encuentran en `tests/Feature/`; la suite también incluye las pruebas de `tests/Unit/`. La tabla destaca riesgos relevantes y no representa un porcentaje de cobertura de líneas.

## Recorridos de navegador

**Cliente:** diez módulos a tamaño móvil, carga de las cuatro insignias, filtro sin alterar rango, gráfica coherente, nueva marca y solicitud de plan. El progreso se revisó además a 320 y 360 píxeles.

**Entrenador:** ocho módulos, clientes vinculados y su progreso. Se verificó el rechazo de acceso al progreso de otro cliente, ventas, asistencia y reportes financieros.

**Secretaria:** siete módulos, activación de membresía, efectivo con cambio, tarjeta rechazada y aprobada, reintento del UUID, comprobante e historial móvil. Se rechazó acceso a administración de cuentas, reportes y progreso.

**Administrador:** diez módulos, cuentas, catálogo y reportes; se conservó la separación de activación de membresía. También se examinó la distribución de escritorio a 1440 píxeles.

La anchura ordinaria de los recorridos móviles fue 390 píxeles. Los tamaños emulados no acreditan interacción táctil física, teclados de iOS ni compatibilidad de todos los navegadores. La base sintética terminó con dos ventas, ambas simuladas, cero ventas en el reporte real, nueve marcas deportivas y un pago de membresía. Estos números son controles del escenario de prueba, no datos del gimnasio.

## Reproducir las comprobaciones

Use una copia de desarrollo del proyecto con dependencias instaladas y sin configuración productiva en caché. `phpunit.xml` utiliza SQLite en memoria y servicios de prueba; revise ese archivo antes de cambiar el entorno de ejecución.

```sh
php vendor/phpunit/phpunit/phpunit
node tools/frontend_regression_check.cjs
npm run build
```

Para el navegador se requiere Chrome instalado, puerto local 8034 libre y Puppeteer Core ya incluido en el lockfile. Los scripts crean fixtures en una base temporal y guardan la configuración de QA en `storage/app/private/`; no publique esos archivos porque contienen una contraseña aleatoria de prueba.

```sh
php tools/prepare_mobile_qa.php
node tools/mobile_review_check.cjs
```

En otra instalación, ajuste `ALPHA_QA_PHP` y `ALPHA_QA_BROWSER` a sus ejecutables. El script tiene valores Windows para XAMPP y Chrome, inicia su propio servidor local y cierra los procesos que creó al terminar. La preparación ejecuta migraciones y seeders únicamente en su base temporal; no se debe adaptar para utilizar la base del gimnasio. No ejecute dos suites que compartan el puerto o los mismos archivos de fixtures al mismo tiempo.

## Criterios de cierre y pendientes

| Criterio | Estado en esta revisión |
| --- | --- |
| Cobros de práctica y reintentos íntegros | Verificado en pruebas automatizadas y navegador. |
| Simulaciones separadas de ingresos | Verificado en pruebas y base sintética. |
| Progreso, rangos y relación de entrenadores | Verificado en tests y recorridos móviles. |
| Permisos de los cuatro roles | Verificado en escenarios positivos y negativos examinados. |
| Migración conservadora de la instalación local | Verificado con respaldo y conservación de registros originales. |
| Documentación y capturas | Actualizadas al contenido identificado; PDFs revisados visualmente antes de entrega. |
| Correo real | Pendiente de configuración y prueba de entrega. |
| Responsable y contacto de privacidad | Pendiente de datos del gimnasio y criterios de conservación. |
| Google real | Opcional; pendiente si se decide ofrecerlo. |
| Producción, carga y recuperación completa | No ejecutados en un servidor productivo; requieren su propio entorno y aceptación. |
| Concurrencia MySQL y móviles físicos | No ejecutados. |

El cierre local no transforma estos pendientes en resultados aprobados. Los cambios de interfaz, pagos simulados y esta documentación permanecen en el árbol de trabajo hasta que se publiquen; el SHA base por sí solo no contiene esta edición.
