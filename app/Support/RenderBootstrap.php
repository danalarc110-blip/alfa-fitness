<?php

namespace App\Support;

use PDO;
use RuntimeException;

/** Initial data transfer only. This class must work before Laravel is bootstrapped. */
final class RenderBootstrap
{
    public const CHUNK_SIZE = 16000;

    public const MAX_ENV_BYTES = 1000000;

    private const MAX_PLAIN_BYTES = 67108864;

    private const MAX_TRANSFER_BYTES = 94371840;

    private const AAD = 'alpha-fitness-render-bootstrap-v1';

    private const TRANSIENT_TABLES = ['migrations', 'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs',
        'password_reset_tokens', 'cliente_password_reset_tokens'];

    public static function seal(array $payload, string $appKey): string
    {
        $plain = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (strlen($plain) > self::MAX_PLAIN_BYTES) {
            throw new RuntimeException('El paquete supera el tamaño admitido para la transferencia inicial.');
        }
        $compressed = gzencode($plain, 6);
        if ($compressed === false) {
            throw new RuntimeException('No se pudo comprimir el paquete inicial.');
        }
        $iv = random_bytes(12);
        $encrypted = openssl_encrypt($compressed, 'aes-256-gcm', self::encryptionKey($appKey), OPENSSL_RAW_DATA, $iv, $tag, self::AAD);
        if ($encrypted === false) {
            throw new RuntimeException('No se pudo cifrar el paquete inicial.');
        }

        return json_encode(['version' => 1, 'iv' => base64_encode($iv), 'tag' => base64_encode($tag),
            'data' => base64_encode($encrypted)], JSON_THROW_ON_ERROR);
    }

    public static function runFromEnvironment(?array $environment = null): string
    {
        $env = static fn (string $name): string => (string) ($environment === null ? (getenv($name) ?: '') : ($environment[$name] ?? ''));
        $directory = rtrim($env('ALPHA_DATA_DIR'), '/\\');
        $database = $env('DB_DATABASE');
        if ($env('DB_CONNECTION') === 'pgsql') {
            return self::bootstrapPostgres($directory, $env);
        }
        if (! self::absolutePath($directory) || str_replace('\\', '/', $database) !== str_replace('\\', '/', $directory).'/database.sqlite') {
            throw new RuntimeException('ALPHA_DATA_DIR y DB_DATABASE deben señalar el directorio persistente y su database.sqlite.');
        }
        self::directory($directory);
        $lock = fopen($directory.DIRECTORY_SEPARATOR.'.bootstrap.lock', 'c');
        if ($lock === false || ! flock($lock, LOCK_EX)) {
            throw new RuntimeException('No se pudo bloquear el directorio de importación.');
        }
        $temporaryDatabase = null;
        try {
            clearstatcache(true, $database);
            if (file_exists($database) || is_link($database)) {
                if (is_link($database)) {
                    throw new RuntimeException('La base persistente no puede ser un enlace simbólico.');
                }
                self::validateDatabase($database);

                return 'existing';
            }

            $sealed = self::readTransport($env);
            $payload = self::open($sealed, $env('APP_KEY'));
            [$databaseBytes, $files] = self::validatePayload($payload);
            $temporaryDatabase = tempnam($directory, '.bootstrap-db-');
            if ($temporaryDatabase === false || file_put_contents($temporaryDatabase, $databaseBytes, LOCK_EX) !== strlen($databaseBytes)) {
                throw new RuntimeException('No se pudo guardar la base de datos provisional.');
            }
            @chmod($temporaryDatabase, 0600);
            self::validateDatabase($temporaryDatabase);
            foreach ($files as $path => $bytes) {
                self::publishFile($directory, $path, $bytes);
            }
            // link() publishes atomically on the same filesystem and never replaces a destination.
            if (! @link($temporaryDatabase, $database)) {
                throw new RuntimeException('No se publicó la base: el destino ya existe o el disco no admite publicación atómica.');
            }

            return 'imported';
        } finally {
            if ($temporaryDatabase && is_file($temporaryDatabase)) {
                unlink($temporaryDatabase);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public static function validateDatabase(string $path): void
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('La base SQLite no se puede leer.');
        }
        $header = fread($handle, 16);
        fclose($handle);
        if ($header !== "SQLite format 3\0") {
            throw new RuntimeException('La base persistente no tiene una cabecera SQLite válida; no se sustituirá.');
        }
        try {
            $pdo = new PDO('sqlite:'.$path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $result = $pdo->query('PRAGMA quick_check')->fetchColumn();
            $pdo = null;
        } catch (\Throwable) {
            throw new RuntimeException('No se pudo comprobar la integridad de SQLite; no se sustituirá la base.');
        }
        if ($result !== 'ok') {
            throw new RuntimeException('SQLite no superó quick_check; no se sustituirá la base.');
        }
    }

    public static function validFilePath(string $path): bool
    {
        if ($path === 'private/demo-access.json') {
            return true;
        }
        if (! preg_match('~^images/(avatars|productos|ejercicios)/([^/\\\\]+)$~u', $path, $matches)) {
            return false;
        }
        $name = $matches[2];

        return strlen($name) <= 240 && preg_match('/^[\pL\pN][\pL\pN\pM ._-]*\.(png|jpe?g|webp)$/iu', $name) === 1;
    }

    private static function encryptionKey(string $appKey): string
    {
        $bytes = str_starts_with($appKey, 'base64:') ? base64_decode(substr($appKey, 7), true) : $appKey;
        if ($bytes === false || strlen($bytes) !== 32) {
            throw new RuntimeException('APP_KEY debe contener la clave original válida de 32 bytes.');
        }

        return hash_hkdf('sha256', $bytes, 32, self::AAD);
    }

    private static function bootstrapPostgres(string $directory, callable $env): string
    {
        if (! self::absolutePath($directory)) {
            throw new RuntimeException('ALPHA_DATA_DIR debe ser un directorio absoluto.');
        }
        self::directory($directory);
        $pdo = self::postgresConnection($env('DB_URL'), $env('DB_SSLMODE'));
        $hasPackage = $env('ALPHA_BOOTSTRAP_FILE') !== '' || $env('ALPHA_BOOTSTRAP_PARTS') !== '';
        if (! $hasPackage && self::postgresHasData($pdo)) {
            return 'existing';
        }
        $payload = self::open(self::readTransport($env), $env('APP_KEY'));
        [$databaseBytes, $files] = self::validatePayload($payload);
        if (! is_array($payload['tables'] ?? null)) {
            throw new RuntimeException('El paquete no incluye las tablas portables para PostgreSQL.');
        }
        $source = tempnam($directory, '.portable-source-');
        try {
            if ($source === false || file_put_contents($source, $databaseBytes, LOCK_EX) !== strlen($databaseBytes)) {
                throw new RuntimeException('No se pudo verificar la base de origen de la transferencia.');
            }
            @chmod($source, 0600);
            self::validateDatabase($source);
            $actualTables = self::tablesFromSqlite($source);
            if (! hash_equals(hash('sha256', json_encode($actualTables, JSON_THROW_ON_ERROR)), hash('sha256', json_encode($payload['tables'], JSON_THROW_ON_ERROR)))) {
                throw new RuntimeException('Las tablas portables no coinciden con la base SQLite original.');
            }
        } finally {
            if ($source && is_file($source)) {
                unlink($source);
            }
        }
        $tables = self::orderedTables($payload['tables']);
        if (! in_array('users', array_column($tables, 'name'), true) || ! in_array('clientes', array_column($tables, 'name'), true)) {
            throw new RuntimeException('Faltan las tablas de cuentas de la aplicación en el paquete portable.');
        }
        $result = self::importPostgres($pdo, $tables, hash('sha256', $databaseBytes));
        // Free web instances have ephemeral files: restore missing uploads on every start.
        foreach ($files as $path => $bytes) {
            self::publishFile($directory, $path, $bytes);
        }

        return $result;
    }

    private static function postgresConnection(string $url, string $sslmode): PDO
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ! in_array($parts['scheme'] ?? '', ['postgres', 'postgresql', 'pgsql'], true)
            || ! preg_match('/^[A-Za-z0-9_.-]+$/', $parts['host'] ?? '') || empty($parts['path']) || empty($parts['user'])) {
            throw new RuntimeException('DB_URL no contiene una conexión PostgreSQL válida.');
        }
        parse_str($parts['query'] ?? '', $query);
        $mode = $query['sslmode'] ?? ($sslmode ?: 'prefer');
        if (! is_string($mode) || ! in_array($mode, ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'], true)) {
            throw new RuntimeException('El modo TLS de PostgreSQL no es válido.');
        }
        $name = rawurldecode(ltrim($parts['path'], '/'));
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $name)) {
            throw new RuntimeException('El nombre de la base PostgreSQL no es válido.');
        }
        try {
            return new PDO('pgsql:host='.$parts['host'].';port='.($parts['port'] ?? 5432).';dbname='.$name.';sslmode='.$mode,
                rawurldecode($parts['user']), rawurldecode($parts['pass'] ?? ''),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
        } catch (\Throwable $error) {
            $state = preg_match('/^[0-9A-Z]{5}$/', (string) $error->getCode()) ? ' SQLSTATE '.$error->getCode().'.' : '';
            throw new RuntimeException('No se pudo conectar a PostgreSQL para la transferencia inicial.'.$state);
        }
    }

    /** Also used by isolated-schema integration tests; never call against a non-empty business database. */
    public static function importPostgres(PDO $pdo, array $tables, string $hash): string
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'pgsql') {
            throw new RuntimeException('La importación portable requiere PostgreSQL.');
        }
        $tables = self::orderedTables($tables);
        $pdo->beginTransaction();
        try {
            $pdo->query('SELECT pg_advisory_xact_lock(195134985, 1)');
            if (self::postgresHasData($pdo)) {
                $pdo->commit();

                return 'existing';
            }
            $available = $pdo->query('SELECT tablename FROM pg_tables WHERE schemaname = current_schema()')->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                if (! in_array($table['name'], $available, true)) {
                    throw new RuntimeException('El esquema PostgreSQL no está preparado; ejecuta primero las migraciones.');
                }
            }
            // A migration seeds these exact three plans. Clear only those untouched initial rows.
            if (in_array('planes_membresia', $available, true)) {
                $pdo->exec('DELETE FROM "planes_membresia"');
            }
            foreach ($tables as $table) {
                if ($table['rows'] === []) {
                    continue;
                }
                $schema = $pdo->prepare('SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ?');
                $schema->execute([$table['name']]);
                $types = $schema->fetchAll(PDO::FETCH_KEY_PAIR);
                foreach ($table['columns'] as $column) {
                    if (! isset($types[$column])) {
                        throw new RuntimeException('El esquema PostgreSQL no coincide con las columnas exportadas.');
                    }
                }
                $columnNames = implode(', ', array_map(static fn ($column) => '"'.$column.'"', $table['columns']));
                $insert = $pdo->prepare('INSERT INTO "'.$table['name'].'" ('.$columnNames.') VALUES ('.implode(', ', array_fill(0, count($table['columns']), '?')).')');
                foreach ($table['rows'] as $row) {
                    foreach ($row as $index => $value) {
                        $boolean = $types[$table['columns'][$index]] === 'boolean';
                        if ($boolean && $value !== null) {
                            if (! in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true)) {
                                throw new RuntimeException('Un valor booleano exportado no es válido.');
                            }
                            $value = in_array($value, [true, 1, '1', 'true'], true);
                        }
                        $type = $value === null ? PDO::PARAM_NULL : (is_bool($value) ? PDO::PARAM_BOOL : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR));
                        $insert->bindValue($index + 1, $value, $type);
                    }
                    $insert->execute();
                }
            }
            foreach ($tables as $table) {
                if (! in_array('id', $table['columns'], true)) {
                    continue;
                }
                $sequence = $pdo->prepare('SELECT pg_get_serial_sequence(?, ?)');
                $sequence->execute([$table['name'], 'id']);
                $name = $sequence->fetchColumn();
                if ($name) {
                    $maximum = (int) $pdo->query('SELECT COALESCE(MAX("id"), 0) FROM "'.$table['name'].'"')->fetchColumn();
                    $reset = $pdo->prepare('SELECT setval(CAST(? AS regclass), CAST(? AS bigint), CAST(? AS boolean))');
                    $reset->execute([$name, max(1, $maximum), $maximum > 0 ? 'true' : 'false']);
                }
            }
            $pdo->exec('CREATE TABLE IF NOT EXISTS alpha_bootstrap_imports (source_sha256 varchar(64) PRIMARY KEY, imported_at timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP)');
            $marker = $pdo->prepare('INSERT INTO alpha_bootstrap_imports (source_sha256) VALUES (?)');
            $marker->execute([$hash]);
            $pdo->commit();

            return 'imported';
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($error instanceof RuntimeException && ! $error instanceof \PDOException) {
                throw $error;
            }
            $state = preg_match('/^[0-9A-Z]{5}$/', (string) $error->getCode()) ? ' SQLSTATE '.$error->getCode().'.' : '';
            throw new RuntimeException('La importación PostgreSQL se revirtió; no se sustituyeron datos existentes.'.$state);
        }
    }

    private static function postgresHasData(PDO $pdo): bool
    {
        $names = $pdo->query('SELECT tablename FROM pg_tables WHERE schemaname = current_schema()')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($names as $name) {
            if (in_array($name, self::TRANSIENT_TABLES, true)) {
                continue;
            }
            if (! preg_match('/^[a-z][a-z0-9_]{0,62}$/', $name)) {
                throw new RuntimeException('El destino contiene una tabla no reconocida para la importación segura.');
            }
            if ($name === 'planes_membresia') {
                $plans = $pdo->query('SELECT id, nombre, precio, duracion_dias, condiciones, activo FROM "planes_membresia" ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
                $defaults = ['Anual' => ['220.00', 365, 3], 'Mensual' => ['25.00', 30, 1], 'Trimestral' => ['65.00', 90, 2]];
                if ($plans === []) {
                    continue;
                }
                if (count($plans) !== 3) {
                    return true;
                }
                foreach ($plans as $plan) {
                    if (! isset($defaults[$plan['nombre']]) || number_format((float) $plan['precio'], 2, '.', '') !== $defaults[$plan['nombre']][0]
                        || (int) $plan['duracion_dias'] !== $defaults[$plan['nombre']][1] || (int) $plan['id'] !== $defaults[$plan['nombre']][2]
                        || $plan['condiciones'] !== 'Acceso al gimnasio durante '.$defaults[$plan['nombre']][1].' dias.'
                        || ! in_array($plan['activo'], [true, 1, '1', 't', 'true'], true)) {
                        return true;
                    }
                }

                continue;
            }
            if ((int) $pdo->query('SELECT COUNT(*) FROM "'.$name.'"')->fetchColumn() > 0) {
                return true;
            }
        }

        return false;
    }

    public static function tablesFromSqlite(string $snapshot): array
    {
        $pdo = new PDO('sqlite:'.$snapshot, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $tables = [];
        $names = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($names as $name) {
            if (in_array($name, [...self::TRANSIENT_TABLES, 'alpha_bootstrap_imports'], true)) {
                continue;
            }
            if (! preg_match('/^[a-z][a-z0-9_]{0,62}$/', $name)) {
                throw new RuntimeException('Una tabla no admite exportación portable segura.');
            }
            $columns = array_column($pdo->query('PRAGMA table_info("'.$name.'")')->fetchAll(PDO::FETCH_ASSOC), 'name');
            $dependencies = array_values(array_unique(array_column($pdo->query('PRAGMA foreign_key_list("'.$name.'")')->fetchAll(PDO::FETCH_ASSOC), 'table')));
            if (in_array($name, ['rutinas', 'sesiones_entrenamiento', 'ejercicio_calificaciones'], true)) {
                $dependencies = array_values(array_unique([...$dependencies, 'users', 'clientes']));
            }
            $tables[] = ['name' => $name, 'columns' => $columns, 'depends_on' => $dependencies,
                'rows' => $pdo->query('SELECT * FROM "'.$name.'"')->fetchAll(PDO::FETCH_NUM)];
        }
        $pdo = null;

        return $tables;
    }

    public static function orderedTables(array $tables): array
    {
        $pending = [];
        foreach ($tables as $table) {
            if (! is_array($table) || ! preg_match('/^[a-z][a-z0-9_]{0,62}$/', $table['name'] ?? '')
                || ! is_array($table['columns'] ?? null) || $table['columns'] === [] || ! is_array($table['rows'] ?? null)
                || ! is_array($table['depends_on'] ?? null) || isset($pending[$table['name']])) {
                throw new RuntimeException('Las tablas portables no tienen un formato válido.');
            }
            foreach ($table['columns'] as $column) {
                if (! is_string($column) || ! preg_match('/^[a-z][a-z0-9_]{0,62}$/', $column)) {
                    throw new RuntimeException('Una columna portable no está autorizada.');
                }
            }
            if (count(array_unique($table['columns'])) !== count($table['columns'])) {
                throw new RuntimeException('Una tabla portable repite columnas.');
            }
            foreach ($table['depends_on'] as $dependency) {
                if (! is_string($dependency) || ! preg_match('/^[a-z][a-z0-9_]{0,62}$/', $dependency)) {
                    throw new RuntimeException('Una dependencia portable no está autorizada.');
                }
            }
            foreach ($table['rows'] as $row) {
                if (! is_array($row) || ! array_is_list($row) || count($row) !== count($table['columns'])) {
                    throw new RuntimeException('Una fila portable no coincide con sus columnas.');
                }
                foreach ($row as $value) {
                    if ($value !== null && ! is_scalar($value)) {
                        throw new RuntimeException('Una fila portable contiene un valor no válido.');
                    }
                }
            }
            $pending[$table['name']] = $table;
        }
        $ordered = [];
        while ($pending !== []) {
            $progress = false;
            foreach ($pending as $name => $table) {
                if (array_intersect(array_keys($pending), array_diff($table['depends_on'], [$name])) !== []) {
                    continue;
                }
                $ordered[] = $table;
                unset($pending[$name]);
                $progress = true;
            }
            if (! $progress) {
                throw new RuntimeException('Las dependencias de las tablas portables contienen un ciclo.');
            }
        }

        return $ordered;
    }

    private static function readTransport(callable $env): string
    {
        $file = $env('ALPHA_BOOTSTRAP_FILE');
        if ($file !== '') {
            if (! self::absolutePath($file) || ! is_file($file) || filesize($file) > self::MAX_TRANSFER_BYTES) {
                throw new RuntimeException('El archivo secreto de importación no está disponible o supera el límite.');
            }
            $data = file_get_contents($file);
            if ($data === false || $data === '') {
                throw new RuntimeException('El archivo secreto de importación está vacío o no se puede leer.');
            }

            return $data;
        }
        $parts = $env('ALPHA_BOOTSTRAP_PARTS');
        if (! preg_match('/^[1-9][0-9]{0,2}$/', $parts) || (int) $parts > 100) {
            throw new RuntimeException('No existe una base persistente ni un paquete inicial completo.');
        }
        $data = '';
        for ($index = 1; $index <= (int) $parts; $index++) {
            $part = $env('ALPHA_BOOTSTRAP_'.str_pad((string) $index, 2, '0', STR_PAD_LEFT));
            if ($part === '' || strlen($part) > self::CHUNK_SIZE) {
                throw new RuntimeException('Falta un fragmento del paquete inicial o su tamaño no es válido.');
            }
            $data .= $part;
            if (strlen($data) > self::MAX_ENV_BYTES) {
                throw new RuntimeException('El paquete requiere transporte por archivo secreto.');
            }
        }

        return $data;
    }

    private static function open(string $sealed, string $appKey): array
    {
        try {
            $envelope = json_decode($sealed, true, 16, JSON_THROW_ON_ERROR);
            $iv = base64_decode($envelope['iv'] ?? '', true);
            $tag = base64_decode($envelope['tag'] ?? '', true);
            $data = base64_decode($envelope['data'] ?? '', true);
            if (($envelope['version'] ?? null) !== 1 || $iv === false || strlen($iv) !== 12 || $tag === false || strlen($tag) !== 16 || $data === false) {
                throw new RuntimeException;
            }
            $compressed = openssl_decrypt($data, 'aes-256-gcm', self::encryptionKey($appKey), OPENSSL_RAW_DATA, $iv, $tag, self::AAD);
            if ($compressed === false) {
                throw new RuntimeException;
            }
            $plain = @gzdecode($compressed, self::MAX_PLAIN_BYTES + 1);
            if ($plain === false || strlen($plain) > self::MAX_PLAIN_BYTES) {
                throw new RuntimeException;
            }
            $payload = json_decode($plain, true, 64, JSON_THROW_ON_ERROR);
            if (! is_array($payload)) {
                throw new RuntimeException;
            }

            return $payload;
        } catch (\Throwable) {
            throw new RuntimeException('El paquete no se pudo autenticar o descifrar con la APP_KEY original.');
        }
    }

    private static function validatePayload(array $payload): array
    {
        if (($payload['version'] ?? null) !== 1 || ! is_array($payload['database'] ?? null) || ! is_array($payload['files'] ?? null) || count($payload['files']) > 200) {
            throw new RuntimeException('La estructura del paquete inicial no es válida.');
        }
        $database = self::checkedBytes($payload['database'], 33554432);
        if (! str_starts_with($database, "SQLite format 3\0")) {
            throw new RuntimeException('El paquete no contiene una base SQLite válida.');
        }
        $files = [];
        foreach ($payload['files'] as $file) {
            if (! is_array($file) || ! is_string($file['path'] ?? null) || ! self::validFilePath($file['path']) || isset($files[$file['path']])) {
                throw new RuntimeException('El paquete contiene una ruta no autorizada o repetida.');
            }
            $bytes = self::checkedBytes($file, 6291456);
            if ($file['path'] === 'private/demo-access.json') {
                $manifest = json_decode($bytes, true);
                if (! is_array($manifest) || ($manifest['version'] ?? null) !== 1 || ! is_array($manifest['cuentas'] ?? null)) {
                    throw new RuntimeException('El manifiesto privado de demostración no es válido.');
                }
            } else {
                $image = @getimagesizefromstring($bytes);
                if ($image === false || ! in_array($image['mime'], ['image/png', 'image/jpeg', 'image/webp'], true)) {
                    throw new RuntimeException('El paquete contiene una imagen no válida.');
                }
            }
            $files[$file['path']] = $bytes;
        }

        return [$database, $files];
    }

    private static function checkedBytes(array $value, int $limit): string
    {
        $data = isset($value['data']) && is_string($value['data']) ? base64_decode($value['data'], true) : false;
        $hash = $value['sha256'] ?? '';
        if ($data === false || $data === '' || strlen($data) > $limit || ! is_string($hash) || ! hash_equals(hash('sha256', $data), $hash)) {
            throw new RuntimeException('Un archivo del paquete no superó la comprobación de integridad.');
        }

        return $data;
    }

    private static function publishFile(string $root, string $relative, string $bytes): void
    {
        $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
        self::directory(dirname($path), $root);
        if (file_exists($path) || is_link($path)) {
            return;
        }
        $temporary = tempnam(dirname($path), '.bootstrap-file-');
        try {
            if ($temporary === false || file_put_contents($temporary, $bytes, LOCK_EX) !== strlen($bytes)) {
                throw new RuntimeException('No se pudo guardar un archivo de la importación.');
            }
            @chmod($temporary, str_starts_with($relative, 'private/') ? 0600 : 0644);
            if (! @link($temporary, $path) && ! file_exists($path)) {
                throw new RuntimeException('No se pudo publicar un archivo de la importación.');
            }
        } finally {
            if ($temporary && is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    private static function absolutePath(string $path): bool
    {
        return preg_match('~^(?:[A-Za-z]:[/\\\\]|/)~', $path) === 1 && ! str_contains($path, "\0");
    }

    private static function directory(string $directory, ?string $root = null): void
    {
        if ($root !== null) {
            $suffix = substr($directory, strlen($root));
            $current = $root;
            foreach (preg_split('~[/\\\\]+~', trim($suffix, '/\\')) as $part) {
                if ($part === '') {
                    continue;
                }
                $current .= DIRECTORY_SEPARATOR.$part;
                if (is_link($current)) {
                    throw new RuntimeException('No se permiten enlaces simbólicos dentro de la importación.');
                }
            }
        }
        if (! is_dir($directory) && ! @mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar el directorio persistente.');
        }
    }
}
