<?php

namespace Tests\Unit;

use App\Support\RenderBootstrap;
use Illuminate\Filesystem\Filesystem;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

class RenderBootstrapTest extends TestCase
{
    private string $root;

    private string $source;

    private string $key;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alpha-bootstrap-test-'.bin2hex(random_bytes(8));
        mkdir($this->root, 0700);
        $this->source = $this->root.DIRECTORY_SEPARATOR.'source.sqlite';
        $pdo = new PDO('sqlite:'.$this->source);
        $pdo->exec("CREATE TABLE marker (value TEXT); INSERT INTO marker VALUES ('preservado')");
        $pdo = null;
        $this->key = 'base64:'.base64_encode(random_bytes(32));
    }

    protected function tearDown(): void
    {
        // Only the unique test directory created above is eligible for recursive cleanup.
        if (str_starts_with($this->root, sys_get_temp_dir().DIRECTORY_SEPARATOR.'alpha-bootstrap-test-')) {
            (new Filesystem)->deleteDirectory($this->root);
        }
        parent::tearDown();
    }

    private function record(string $path, string $data): array
    {
        return ['path' => $path, 'data' => base64_encode($data), 'sha256' => hash('sha256', $data)];
    }

    private function payload(array $files = []): array
    {
        return ['version' => 1, 'database' => $this->record('database.sqlite', file_get_contents($this->source)), 'files' => $files];
    }

    private function environment(array $payload): array
    {
        $data = RenderBootstrap::seal($payload, $this->key);
        $parts = str_split($data, RenderBootstrap::CHUNK_SIZE);
        $env = ['ALPHA_DATA_DIR' => $this->root.DIRECTORY_SEPARATOR.'persistent',
            'DB_DATABASE' => $this->root.DIRECTORY_SEPARATOR.'persistent'.DIRECTORY_SEPARATOR.'database.sqlite',
            'APP_KEY' => $this->key, 'ALPHA_BOOTSTRAP_PARTS' => (string) count($parts)];
        foreach ($parts as $index => $part) {
            $env['ALPHA_BOOTSTRAP_'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)] = $part;
        }

        return $env;
    }

    private function image(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aRikAAAAASUVORK5CYII=');
    }

    public function test_imports_authenticated_database_uploads_and_private_manifest(): void
    {
        $manifest = json_encode(['version' => 1, 'cuentas' => [['tipo' => 'cliente', 'password' => 'private-test-value']]]);
        $env = $this->environment($this->payload([$this->record('images/avatars/cliente.png', $this->image()),
            $this->record('private/demo-access.json', $manifest)]));
        $this->assertSame('imported', RenderBootstrap::runFromEnvironment($env));
        $this->assertSame(hash_file('sha256', $this->source), hash_file('sha256', $env['DB_DATABASE']));
        $this->assertSame($this->image(), file_get_contents($env['ALPHA_DATA_DIR'].'/images/avatars/cliente.png'));
        $this->assertSame($manifest, file_get_contents($env['ALPHA_DATA_DIR'].'/private/demo-access.json'));
        $pdo = new PDO('sqlite:'.$env['DB_DATABASE']);
        $this->assertSame('preservado', $pdo->query('SELECT value FROM marker')->fetchColumn());
        $pdo = null;
    }

    public function test_existing_database_wins_before_reading_any_bundle_and_is_never_replaced(): void
    {
        $env = $this->environment($this->payload());
        mkdir($env['ALPHA_DATA_DIR']);
        copy($this->source, $env['DB_DATABASE']);
        $before = hash_file('sha256', $env['DB_DATABASE']);
        unset($env['APP_KEY'], $env['ALPHA_BOOTSTRAP_01'], $env['ALPHA_BOOTSTRAP_PARTS']);
        $this->assertSame('existing', RenderBootstrap::runFromEnvironment($env));
        $this->assertSame($before, hash_file('sha256', $env['DB_DATABASE']));
    }

    public function test_existing_corrupt_database_is_not_replaced_even_by_a_valid_bundle(): void
    {
        $env = $this->environment($this->payload());
        mkdir($env['ALPHA_DATA_DIR']);
        file_put_contents($env['DB_DATABASE'], 'existing-data-do-not-overwrite');
        try {
            RenderBootstrap::runFromEnvironment($env);
            $this->fail('Debe rechazar la base existente que no es SQLite.');
        } catch (RuntimeException) {
            $this->assertSame('existing-data-do-not-overwrite', file_get_contents($env['DB_DATABASE']));
        }
    }

    public function test_wrong_key_and_missing_bundle_fail_without_creating_an_empty_database(): void
    {
        $env = $this->environment($this->payload());
        $env['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
        try {
            RenderBootstrap::runFromEnvironment($env);
            $this->fail('Debe rechazar una clave diferente.');
        } catch (RuntimeException) {
            $this->assertFileDoesNotExist($env['DB_DATABASE']);
        }
        unset($env['ALPHA_BOOTSTRAP_PARTS']);
        $this->expectException(RuntimeException::class);
        RenderBootstrap::runFromEnvironment($env);
    }

    public function test_traversal_hash_mismatch_and_broken_sqlite_are_rejected_before_publication(): void
    {
        $payloads = [];
        $payloads[] = $this->payload([$this->record('images/avatars/../../escape.png', $this->image())]);
        $badHash = $this->payload();
        $badHash['database']['sha256'] = str_repeat('0', 64);
        $payloads[] = $badHash;
        $badDatabase = $this->payload();
        $badDatabase['database'] = $this->record('database.sqlite', substr(file_get_contents($this->source), 0, 100));
        $payloads[] = $badDatabase;
        foreach ($payloads as $payload) {
            $env = $this->environment($payload);
            try {
                RenderBootstrap::runFromEnvironment($env);
                $this->fail('No debe publicar el paquete inválido.');
            } catch (RuntimeException) {
                $this->assertFileDoesNotExist($env['DB_DATABASE']);
                $this->assertFileDoesNotExist($env['ALPHA_DATA_DIR'].'/escape.png');
            }
        }
    }

    public function test_existing_upload_is_preserved_and_secret_file_transport_works(): void
    {
        $payload = $this->payload([$this->record('images/productos/producto.png', $this->image())]);
        $env = $this->environment($payload);
        mkdir($env['ALPHA_DATA_DIR'].'/images/productos', 0750, true);
        file_put_contents($env['ALPHA_DATA_DIR'].'/images/productos/producto.png', 'existing-upload');
        $file = $this->root.DIRECTORY_SEPARATOR.'secret.enc';
        file_put_contents($file, RenderBootstrap::seal($payload, $this->key));
        $env['ALPHA_BOOTSTRAP_FILE'] = $file;
        unset($env['ALPHA_BOOTSTRAP_PARTS'], $env['ALPHA_BOOTSTRAP_01']);
        $this->assertSame('imported', RenderBootstrap::runFromEnvironment($env));
        $this->assertSame('existing-upload', file_get_contents($env['ALPHA_DATA_DIR'].'/images/productos/producto.png'));
    }

    public function test_standalone_script_bootstraps_without_booting_laravel_or_exposing_secrets(): void
    {
        $env = $this->environment($this->payload());
        $script = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'docker'.DIRECTORY_SEPARATOR.'bootstrap-data.php';
        $process = new Process([PHP_BINARY, $script], dirname(__DIR__, 2), $env);
        $process->mustRun();
        $this->assertStringContainsString('Datos iniciales importados y verificados.', $process->getOutput());
        $this->assertStringNotContainsString($this->key, $process->getOutput().$process->getErrorOutput());
        $this->assertFileExists($env['DB_DATABASE']);
    }

    public function test_portable_table_export_preserves_ids_hashes_and_filters_only_transient_tables(): void
    {
        $pdo = new PDO('sqlite:'.$this->source);
        $pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, password TEXT, activo BOOLEAN); INSERT INTO users VALUES (201, 'exact-existing-hash', 1)");
        $pdo->exec('CREATE TABLE clientes (id INTEGER PRIMARY KEY)');
        $pdo->exec('CREATE TABLE rutinas (id INTEGER PRIMARY KEY, user_id INTEGER)');
        $pdo->exec('CREATE TABLE sessions (id TEXT PRIMARY KEY); CREATE TABLE migrations (id INTEGER PRIMARY KEY); CREATE TABLE cliente_password_reset_tokens (email TEXT PRIMARY KEY)');
        $pdo = null;
        $tables = RenderBootstrap::tablesFromSqlite($this->source);
        $map = array_column($tables, null, 'name');
        $this->assertSame([[201, 'exact-existing-hash', 1]], $map['users']['rows']);
        $this->assertArrayNotHasKey('sessions', $map);
        $this->assertArrayNotHasKey('migrations', $map);
        $this->assertArrayNotHasKey('cliente_password_reset_tokens', $map);
        $this->assertContains('users', $map['rutinas']['depends_on']);
        $this->assertContains('clientes', $map['rutinas']['depends_on']);
        $ordered = array_column(RenderBootstrap::orderedTables($tables), 'name');
        $this->assertLessThan(array_search('rutinas', $ordered, true), array_search('users', $ordered, true));
        $this->assertLessThan(array_search('rutinas', $ordered, true), array_search('clientes', $ordered, true));
    }

    public function test_portable_dependency_cycle_and_invalid_columns_are_rejected(): void
    {
        $base = ['columns' => ['id'], 'rows' => [[1]]];
        try {
            RenderBootstrap::orderedTables([['name' => 'one', 'depends_on' => ['two'], ...$base], ['name' => 'two', 'depends_on' => ['one'], ...$base]]);
            $this->fail('Debe rechazar una dependencia cíclica.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }
        $this->expectException(RuntimeException::class);
        RenderBootstrap::orderedTables([['name' => 'users', 'columns' => ['id; DROP TABLE users'], 'rows' => [[1]], 'depends_on' => []]]);
    }
}
