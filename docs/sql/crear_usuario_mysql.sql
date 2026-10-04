-- Alpha Fitness / MySQL y MariaDB (XAMPP).
-- Ejecutar desde phpMyAdmin > SQL, autenticado como administrador de la base.
-- Sustituir TODOS los marcadores antes de ejecutar. No guardar contraseñas reales en Git.
-- {{BASE_DATOS_EXISTENTE}}: nombre de la base actual, sólo letras, números y guion bajo.
-- {{CONTRASENA_MYSQL_SEGURA}}: contraseña aleatoria propia, mínimo 24 caracteres.
-- En un literal SQL, una comilla simple se escribe dos veces: ''.
-- El script NO crea, modifica ni borra tablas/datos del gimnasio.

CREATE USER IF NOT EXISTS 'alfa_fitness_app'@'127.0.0.1'
    IDENTIFIED BY '{{CONTRASENA_MYSQL_SEGURA}}';
CREATE USER IF NOT EXISTS 'alfa_fitness_app'@'localhost'
    IDENTIFIED BY '{{CONTRASENA_MYSQL_SEGURA}}';

-- Permisos limitados a la base del gimnasio; permiten también artisan migrate/rollback.
-- Nunca conceder permisos globales, GRANT OPTION, FILE, PROCESS ni SUPER.
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES, DROP
    ON `{{BASE_DATOS_EXISTENTE}}`.* TO 'alfa_fitness_app'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES, DROP
    ON `{{BASE_DATOS_EXISTENTE}}`.* TO 'alfa_fitness_app'@'localhost';

-- En producción, tras migrar y antes de reabrir el servicio, reducir permisos con:
-- REVOKE CREATE, ALTER, INDEX, REFERENCES, DROP
--     ON `{{BASE_DATOS_EXISTENTE}}`.* FROM 'alfa_fitness_app'@'127.0.0.1';
-- REVOKE CREATE, ALTER, INDEX, REFERENCES, DROP
--     ON `{{BASE_DATOS_EXISTENTE}}`.* FROM 'alfa_fitness_app'@'localhost';
-- Las siguientes migraciones requieren temporalmente volver a concederlos,
-- o ejecutarse con una cuenta distinta reservada al administrador de la base.

SHOW GRANTS FOR 'alfa_fitness_app'@'127.0.0.1';
SHOW GRANTS FOR 'alfa_fitness_app'@'localhost';

-- Actualizar únicamente el .env LOCAL (no versionado):
-- DB_CONNECTION=mysql
-- DB_HOST=127.0.0.1
-- DB_PORT=3306
-- DB_DATABASE={{BASE_DATOS_EXISTENTE}}
-- DB_USERNAME=alfa_fitness_app
-- DB_PASSWORD="{{CONTRASENA_MYSQL_SEGURA}}"
-- Después: php artisan optimize:clear
-- No ejecutar migrate:fresh en una instalación con datos reales.
