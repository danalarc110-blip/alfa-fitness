# Revisión de licencias, imágenes y procedencia

Fecha: 4 de octubre de 2026. Base revisada: commit 2895047. Revisión de archivos y metadatos; no acredita titularidad de activos ni sustituye revisión jurídica.

## Código y dependencias

`composer.json` declara MIT para el proyecto, pero no se encontró un archivo LICENSE propio que establezca autores y condiciones para el código de Alpha Fitness. La declaración del esqueleto Laravel no demuestra que el responsable pueda licenciar todas las aportaciones del repositorio. Decisión: no inventar autores ni introducir una licencia nueva sin determinar titularidad.

El `composer.lock` revisado contiene 117 paquetes incluyendo desarrollo: 82 MIT, 32 BSD-3-Clause, 1 Apache-2.0 y 2 Nette con alternativas BSD-3-Clause/GPL-2.0/GPL-3.0. Los archivos `vendor/nette/schema/license.md` y `vendor/nette/utils/license.md` permiten escoger BSD; esto no impone automáticamente GPL al proyecto. Deben conservarse los avisos de cada dependencia al distribuirla.

`package-lock.json` declara licencias para 136 entradas de dependencias, incluyendo paquetes opcionales por plataforma: 113 MIT, 12 MPL-2.0, 6 ISC, 2 Apache-2.0, 2 0BSD y 1 BSD-3-Clause. El número no equivale a paquetes instalados en Windows. `lightningcss` y sus variantes son MPL-2.0; `detect-libc` y `rxjs`, Apache-2.0. Los paquetes principales Tailwind CSS, Vite y el complemento Laravel declaran MIT. Revalidar los metadatos cuando cambien los lockfiles.

Mantener textos de licencia y avisos al redistribuir dependencias o binarios. Si se modifican o redistribuyen archivos de una biblioteca MPL, revisar sus obligaciones específicas de fuente y avisos. No afirmar que una compilación elimina toda obligación de atribución. El ZIP de actualización no necesita incluir `vendor` o `node_modules`; la instalación reconstruye esas dependencias desde sus lockfiles.

## Activos que requieren acreditar permiso

| Activo | Evidencia en el repositorio | Acción del responsable |
| --- | --- | --- |
| `public/images/LOGO.png`, `logo-sidebar.png` y `.webp` | Marca Alpha Fitness; no consta contrato de autor o licencia | Acreditar diseño propio o permiso y verificar uso de la marca |
| `public/images/gym-bg.png` y `.webp` | Fondo visual; procedencia no documentada | Conservar prueba de licencia comercial o sustituir por foto propia autorizada |
| Fotos y diagramas de ejercicios en `public/images` y `public/images/ejercicios` | Archivos JPG/PNG/WebP y duplicados; sin atribución individual encontrada | Identificar autor, fuente y permiso por imagen; no asumir permiso por estar en internet |
| `public/images/avatars/cliente_1_*` y `cliente_2_*` | Seis PNG/WebP de perfiles versionados; no se confirmó si son ejemplos o personas reales | Verificar procedencia y consentimiento antes de publicar o redistribuir; si son personas reales, tratar la exposición y acordar su sustitución |
| Avatares y fotos que se suban después | El sistema permite incorporar nuevas imágenes | Exigir titularidad o permiso; no añadir archivos personales al control de versiones |

Cambiar JPG/PNG a WebP no cambia los derechos de autor ni elimina datos personales. Un placeholder solo soluciona una imagen faltante, no autoriza usar una imagen protegida. Esta auditoría no atribuye procedencia ficticia ni elimina muestras cuyo origen se desconoce.

## Registro mínimo de permisos

El responsable debe conservar, fuera del repositorio público, una ficha por activo con nombre del archivo, autor o proveedor, URL de origen, fecha, licencia y versión, comprobante o autorización, límites de uso, atribución necesaria y responsable de la revisión. Para imágenes de personas, registrar también el alcance autorizado de publicación y su retiro. Si no puede demostrar permiso, sustituir ese activo con uno propio o debidamente licenciado antes de uso comercial.

Los documentos legales entregados son una base a revisar por un abogado; no certifican derechos de propiedad intelectual ni cumplimiento integral.
