<?php

namespace Tests\Feature;

use App\Support\RenderBootstrap;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDO;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ExportRenderCommandTest extends TestCase
{
    private string $directory;

    private string $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alpha-export-test-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        $this->source = $this->directory.DIRECTORY_SEPARATOR.'source.sqlite';
        $pdo = new PDO('sqlite:'.$this->source);
        $pdo->exec("CREATE TABLE marker (value TEXT); INSERT INTO marker VALUES ('datos conservados')");
        $pdo = null;
        config(['database.default' => 'export_test', 'database.connections.export_test' => [
            'driver' => 'sqlite', 'database' => $this->source, 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        app()->usePublicPath($this->directory.DIRECTORY_SEPARATOR.'public');
        mkdir(public_path('images/avatars'), 0750, true);
        mkdir(public_path('images/ejercicios'), 0750, true);
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        DB::purge('export_test');
        if (str_starts_with($this->directory, sys_get_temp_dir().DIRECTORY_SEPARATOR.'alpha-export-test-')) {
            (new Filesystem)->deleteDirectory($this->directory);
        }
        parent::tearDown();
    }

    private function image(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aRikAAAAASUVORK5CYII=');
    }

    public function test_exports_consistent_database_and_private_data_without_printing_secrets(): void
    {
        $before = hash_file('sha256', $this->source);
        file_put_contents(public_path('images/avatars/subido-demo.png'), $this->image());
        $manifest = json_encode(['version' => 1, 'cuentas' => [['password' => 'do-not-print-this-private-value']]]);
        Storage::disk('local')->put('demo-access.json', $manifest);
        $this->assertSame(0, Artisan::call('render:export'));
        $output = Artisan::output();
        $this->assertStringNotContainsString(config('app.key'), $output);
        $this->assertStringNotContainsString('do-not-print-this-private-value', $output);
        $transfer = json_decode(Storage::disk('local')->get('render-transfer.json'), true);
        $env = array_column($transfer['envVars'], 'value', 'key');
        $this->assertSame(config('app.key'), $env['APP_KEY']);
        $this->assertLessThanOrEqual(RenderBootstrap::CHUNK_SIZE, strlen($env['ALPHA_BOOTSTRAP_01']));
        $this->assertSame($before, hash_file('sha256', $this->source));
        $env['ALPHA_DATA_DIR'] = $this->directory.DIRECTORY_SEPARATOR.'restored';
        $env['DB_DATABASE'] = $env['ALPHA_DATA_DIR'].DIRECTORY_SEPARATOR.'database.sqlite';
        $this->assertSame('imported', RenderBootstrap::runFromEnvironment($env));
        $pdo = new PDO('sqlite:'.$env['DB_DATABASE']);
        $this->assertSame('datos conservados', $pdo->query('SELECT value FROM marker')->fetchColumn());
        $pdo = null;
        $this->assertSame($manifest, file_get_contents($env['ALPHA_DATA_DIR'].'/private/demo-access.json'));
        $this->assertSame($this->image(), file_get_contents($env['ALPHA_DATA_DIR'].'/images/avatars/subido-demo.png'));
        $this->assertSame([], glob(Storage::disk('local')->path('render-snapshot-*.sqlite')));
    }

    public function test_versioned_images_are_not_repeated_in_transfer_and_file_transport_is_available(): void
    {
        $git = new Process(['git', 'ls-files', '-z', '--', 'public/images/ejercicios'], base_path());
        $git->mustRun();
        $tracked = array_values(array_filter(explode("\0", $git->getOutput()), static fn ($path) => preg_match('/\.(png|jpg|jpeg|webp)$/i', $path)));
        $this->assertNotEmpty($tracked, 'El catálogo versionado debe estar presente en esta aplicación.');
        file_put_contents(public_path(substr($tracked[0], strlen('public/'))), $this->image());
        file_put_contents(public_path('images/avatars/subido-demo.png'), $this->image());
        $this->assertSame(0, Artisan::call('render:export', ['--file' => true]));
        $transfer = json_decode(Storage::disk('local')->get('render-transfer.json'), true);
        $env = array_column($transfer['envVars'], 'value', 'key');
        $this->assertSame(1, $transfer['uploaded_files']);
        $this->assertArrayHasKey('ALPHA_BOOTSTRAP_FILE', $env);
        $this->assertArrayNotHasKey('ALPHA_BOOTSTRAP_PARTS', $env);
        $env['ALPHA_BOOTSTRAP_FILE'] = Storage::disk('local')->path('render-bootstrap.enc');
        $env['ALPHA_DATA_DIR'] = $this->directory.DIRECTORY_SEPARATOR.'restored';
        $env['DB_DATABASE'] = $env['ALPHA_DATA_DIR'].DIRECTORY_SEPARATOR.'database.sqlite';
        $this->assertSame('imported', RenderBootstrap::runFromEnvironment($env));
        $this->assertFileDoesNotExist($env['ALPHA_DATA_DIR'].'/'.substr($tracked[0], strlen('public/')));
    }

    public function test_invalid_key_and_in_memory_database_do_not_produce_a_transfer_configuration(): void
    {
        config(['app.key' => 'invalid']);
        $this->assertSame(1, Artisan::call('render:export'));
        Storage::disk('local')->assertMissing('render-transfer.json');
        config(['database.connections.export_test.database' => ':memory:']);
        DB::purge('export_test');
        $this->assertSame(1, Artisan::call('render:export'));
        Storage::disk('local')->assertMissing('render-transfer.json');
        $this->assertSame([], glob(Storage::disk('local')->path('render-snapshot-*.sqlite')));
    }

    public function test_export_and_restore_keep_daily_entry_payments_retry_keys_and_unbilled_history(): void
    {
        $pdo = new PDO('sqlite:'.$this->source);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY); INSERT INTO users VALUES (201)');
        $pdo->exec('CREATE TABLE clientes (id INTEGER PRIMARY KEY); INSERT INTO clientes VALUES (301)');
        $pdo->exec('CREATE TABLE asistencias (id INTEGER PRIMARY KEY, cliente_id INTEGER REFERENCES clientes(id), registrado_por INTEGER REFERENCES users(id), entrada_uuid TEXT UNIQUE, fecha_hora TEXT)');
        $pdo->exec('CREATE TABLE pagos_entrada (id INTEGER PRIMARY KEY, asistencia_id INTEGER UNIQUE REFERENCES asistencias(id), importe NUMERIC, registrado_por INTEGER REFERENCES users(id), pagado_en TEXT)');
        $uuid = 'ed5506b4-7952-4d64-a2c5-a01d9320ee20';
        $insert = $pdo->prepare('INSERT INTO asistencias VALUES (?, 301, 201, ?, ?)');
        $insert->execute([401, null, '2026-10-01 08:00:00']);
        $insert->execute([402, $uuid, '2026-11-05 08:00:00']);
        $pdo->exec("INSERT INTO pagos_entrada VALUES (501, 402, 2.00, 201, '2026-11-05 08:00:00')");
        $pdo = null;

        $tables = RenderBootstrap::tablesFromSqlite($this->source);
        $map = array_column($tables, null, 'name');
        $this->assertContains('asistencias', $map['pagos_entrada']['depends_on']);
        $this->assertContains('users', $map['pagos_entrada']['depends_on']);
        $this->assertSame($uuid, $map['asistencias']['rows'][1][3]);
        $ordered = array_column(RenderBootstrap::orderedTables($tables), 'name');
        $this->assertLessThan(array_search('pagos_entrada', $ordered, true), array_search('asistencias', $ordered, true));
        $this->assertLessThan(array_search('pagos_entrada', $ordered, true), array_search('users', $ordered, true));

        $this->assertSame(0, Artisan::call('render:export'));
        $transfer = json_decode(Storage::disk('local')->get('render-transfer.json'), true);
        $env = array_column($transfer['envVars'], 'value', 'key');
        $env['ALPHA_DATA_DIR'] = $this->directory.DIRECTORY_SEPARATOR.'restored-daily-entries';
        $env['DB_DATABASE'] = $env['ALPHA_DATA_DIR'].DIRECTORY_SEPARATOR.'database.sqlite';
        $this->assertSame('imported', RenderBootstrap::runFromEnvironment($env));
        $restored = new PDO('sqlite:'.$env['DB_DATABASE']);
        $this->assertSame($uuid, $restored->query('SELECT entrada_uuid FROM asistencias WHERE id = 402')->fetchColumn());
        $this->assertNull($restored->query('SELECT entrada_uuid FROM asistencias WHERE id = 401')->fetchColumn());
        $this->assertSame(1, (int) $restored->query('SELECT COUNT(*) FROM pagos_entrada')->fetchColumn());
        $this->assertSame(402, (int) $restored->query('SELECT asistencia_id FROM pagos_entrada WHERE id = 501')->fetchColumn());
        $this->assertSame('2.00', number_format((float) $restored->query('SELECT importe FROM pagos_entrada WHERE id = 501')->fetchColumn(), 2, '.', ''));
        $this->assertSame(0, (int) $restored->query('SELECT COUNT(*) FROM pagos_entrada WHERE asistencia_id = 401')->fetchColumn());
        $restored = null;
    }
}
