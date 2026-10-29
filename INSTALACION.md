# Instalación de Alpha Fitness

La aplicación completa se instala siguiendo [Uso local en el README](README.md#uso-local). La guía incluye los requisitos de PHP y Node.js, la configuración del entorno, la base de datos y la creación del administrador.

Los estilos y las animaciones ya están integrados en el proyecto. No hace falta copiar paquetes visuales ni añadir imports CSS manuales. Después de instalar las dependencias o modificar el frontend, ejecuta `npm run build`.

Antes de publicar, revisa [Datos y despliegue](README.md#datos-y-despliegue) y ejecuta las [comprobaciones](README.md#verificación). En una instalación existente, respalda la base de datos antes de aplicar migraciones; no uses `migrate:fresh`.
