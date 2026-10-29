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
}
