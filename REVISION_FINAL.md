# Revisión de Alpha Fitness — 28 de octubre de 2026

La aplicación se revisó como proyecto estudiantil. Se conservaron los cambios locales existentes y se completaron mejoras de privacidad, permisos, navegación y rendimiento. El responsable y el correo de la política son ficticios y están identificados como ejemplos.

## Problemas encontrados y corregidos

| Problema | Resultado |
| --- | --- |
| La política era un enlace y podía omitirse al iniciar sesión. | Paso previo con confirmación explícita y versión vigente, comprobado en el servidor para personal, clientes, registro y Google. |
| No quedaba constancia de qué aviso se había mostrado. | La cuenta guarda versión y fecha de lectura. Cerrar sesión elimina la confirmación de la sesión; cambiar la versión obliga a revisarla en el siguiente acceso explícito. |
| El aviso no cubría suficientemente cookies, derechos, proveedores, menores y conservación. | Política ampliada con datos y finalidades, permisos, Google, avatares públicos, cookies, protección, conservación, solicitudes y límites del proyecto académico. |
| Administración podía gestionar pausas mediante peticiones directas aunque no tenía ese permiso en la interfaz. | Las acciones usan el permiso común de operaciones. Secretaría opera; el cliente conserva el control autorizado sobre su propia membresía. |
| Existía cancelación de membresías en el servidor, sin botón para usarla. | Secretaría dispone de «Cancelar membresía» con confirmación. Se conservan pago e historial y se explica que no genera reembolso. |
| Una pausa futura aprobada podía anularse en el servidor, pero faltaba la acción en pantalla. | Cliente propietario y Secretaría ven «Anular pausa programada», su fecha y una confirmación. No se consumen días y no se ofrece una segunda pausa mientras haya otra pendiente o aprobada. |
| Las pantallas cargaban los módulos de ventas y apariencia aunque no los utilizaban. | Entradas independientes de Vite, cargadas solamente en sus pantallas. |
| Había animaciones repetitivas de entrada y elevación en tarjetas estáticas. | Se concentran las transiciones en cambios de estado, modales, controles y avisos; sin librerías nuevas ni esperas de entrada. |
| El menú móvil no aislaba correctamente el fondo y tenía gestión frágil del foco y del scroll. | Fondo inerte mientras está abierto, navegación por teclado, cierre con Escape, restauración de foco y scroll, y adaptación al volver a escritorio. |
| En móvil se repetían controles de cuenta y tema. | Se utiliza la cabecera móvil y se evita mostrar la barra de escritorio duplicada. |
| La barra de series animaba el ancho y no sincronizaba el estado accesible. | Progreso mediante `transform`, valores accesibles y estado de las series actualizado. |
| Entrenar una rutina vacía podía provocar una excepción JavaScript. | Se reconoce el estado sin ejercicios y no se buscan controles de progreso inexistentes. |
| Había estilos del acceso anterior, atributos de animación huérfanos y una prueba de plantilla sin utilidad. | Se retiraron estilos y atributos sin consumidores y la prueba que solo afirmaba `true`. |
| La guía de instalación indicaba importar un CSS eliminado. | Instrucciones actualizadas y coherentes con el README. |
| La información pública parecía corresponder a un negocio real. | Horarios, servicios y contactos se identifican como ejemplos académicos; el contacto usa la configuración compartida. |
| Composer y npm detectaron dependencias vulnerables. | Actualizaciones compatibles: Symfony YAML 7.4.21, Symfony MIME 7.4.22 y Vite 7.3.7. Auditorías finales sin avisos conocidos. |

## Roles comprobados

| Rol | Funciones principales | Restricciones verificadas |
| --- | --- | --- |
| Administrador | Cuentas, personal, catálogos, existencias, ventas, asistencia, consulta de membresías, planes y analítica financiera. | No hereda las operaciones de pagos y pausas reservadas a Secretaría. |
| Secretaría | Asistencia, ventas y operaciones de membresías, incluidas pausas y cancelación. | Sin administración de cuentas, personal, analítica financiera ni edición manual de existencias. |
| Entrenador | Rutinas, asignaciones y seguimiento de sus clientes. | No puede consultar progreso de clientes ajenos; la relación se comprueba por identificadores, no por nombre. |
| Cliente | Perfil, rutinas, sesiones, progreso, membresías y asistencia propios. | No accede a información ajena ni adquiere permisos de empleado si coinciden dos sesiones. |

Las cuentas inactivas pierden acceso. Las invitaciones requieren establecer contraseña. Las copias de rutinas asignadas conservan su contenido propio; editar la rutina de origen no las sincroniza automáticamente.

## Rendimiento y apariencia

| Recurso compilado | Antes | Después |
| --- | ---: | ---: |
| JavaScript común | 19,31 kB | 9,37 kB |
| JavaScript común comprimido | 6,14 kB | 3,12 kB |
| CSS común | 96,03 kB | 92,91 kB |

El JavaScript inicial se redujo aproximadamente un 51 %. Ventas carga sus 9,74 kB solo en esa pantalla y apariencia sus 1,86 kB en Configuración. Se conservan los diseños Elegante y Verde, los modos de color y la preferencia de movimiento reducido.

Estas cifras miden los archivos de compilación. No equivalen a una medición de milisegundos de carga, Core Web Vitals ni rendimiento de una red móvil real.

## Verificación y datos

- Suite integrada: 240 pruebas y 1.901 aserciones correctas, con SQLite en memoria y ZIP habilitado para el proceso.
- Diez grupos de regresión de JavaScript correctos: rutinas vacías, series, sesiones expiradas, reintentos, carrito, envíos repetidos y foco de modales.
- Navegador Chrome: acceso y política, cuatro roles, pantallas autorizadas, apariencia persistida, menú móvil por teclado y entrenamiento de una rutina vacía; sin errores JavaScript en ese recorrido.
- Compilación de producción, caché de vistas y Pint correctos.
- Detector visual ejecutado: se corrigieron dos bordes de acento; la otra advertencia corresponde a dos ramas excluyentes de un selector de días, que usan colores distintos según su estado. Se contrastó con la plantilla y los temas existentes.
- `composer audit` y `npm audit`: sin vulnerabilidades conocidas notificadas en la consulta final.
- Migración de privacidad aplicada en la base local después de crear un respaldo consistente en almacenamiento privado. No se reinició ni se borró la base.

Las capturas y el resultado del recorrido están en `outputs/revision-2026-10-28/`. Los registros detallados de pruebas están en almacenamiento privado.

## Cosas que todavía mejoraría o requieren configuración

La preparación para Render añade una imagen Docker, un Blueprint gratuito, importación cifrada de SQLite a PostgreSQL y cuentas ficticias de Secretaría, Entrenador y Cliente. El administrador conserva su contraseña. La suite ampliada ejecutó 261 pruebas y 2.017 aserciones sin fallos; dos pruebas de integración PostgreSQL se omitieron por requerir un servidor PostgreSQL. La verificación del despliegue se realiza por separado y se describe en `docs/RENDER.md`.

1. **Verificación de correo al registrarse.** Actualmente el registro local no verifica la propiedad del correo. La recuperación comprueba un enlace cuando se utiliza, pero no establece un estado de correo verificado en el registro. Añadir verificación requiere correo saliente funcional.
2. **Reembolsos.** Cancelar una membresía conserva el cobro registrado; no hay un flujo de devoluciones ni procesamiento de pagos en línea.
3. **Conservación automática.** El plazo académico del aviso es una regla de demostración. La eliminación y las solicitudes de derechos se atienden por el equipo responsable; no hay tarea automática de borrado.
4. **Información del gimnasio configurable.** Horarios y servicios están en una plantilla. Un panel de edición facilitaría sustituir los ejemplos si se utiliza para un gimnasio real.
5. **Aforo actualizado por recarga.** Es una consulta del momento indicado, no una transmisión en tiempo real. Las horas pico son históricas.
6. **Relación entrenador-cliente ligada a rutinas.** El acceso al seguimiento depende de conservar una asignación del entrenador. Un módulo de asignación de clientes independiente permitiría administrar esa relación por separado.
7. **Imágenes originales duplicadas.** Se detectaron 37 pares idénticos, aproximadamente 26,4 MB. Se conservaron las fuentes y las rutas históricas porque existen fallbacks y referencias de datos. El navegador recibe las variantes ligeras; no descarga todas las copias.
8. **Código inline y CSP.** Algunas pantallas mantienen scripts y estilos inline; migrarlos a módulos y nonces permitiría retirar `unsafe-inline` de la política de seguridad.
9. **PHP del equipo.** XAMPP utiliza PHP 8.2.12 y ZIP está deshabilitado por defecto. El README incluye un arranque local que habilita ZIP para el proceso. Para un despliegue público debe prepararse un intérprete actualizado con las extensiones requeridas.
10. **Servicios externos y concurrencia.** Google y correo se probaron con sustitutos, sin enviar mensajes reales. No se certificó entrega SMTP, OAuth real ni concurrencia sobre MySQL.
11. **Pruebas de rendimiento en dispositivos.** Quedan por medir red lenta, equipo móvil real y carga concurrente. La reducción del tamaño de archivos está comprobada; los tiempos reales dependen de ese entorno.
12. **Uso fuera del aula.** Requiere identidad y contacto efectivos del operador, proveedores, plazos reales y revisión de la política para ese despliegue. Los ejemplos actuales están rotulados para la demostración.

Esta lista distingue limitaciones funcionales y decisiones del producto de los defectos corregidos. No se cambió el modelo de permisos ni se inventaron integraciones de cobro, entrega de correo o credenciales de Google.
