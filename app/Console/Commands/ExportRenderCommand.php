<?php

namespace App\Console\Commands;

use App\Support\RenderBootstrap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ExportRenderCommand extends Command
{
    protected $signature = 'render:export {--file : Usa un archivo secreto en vez de fragmentos de variables de entorno}';

    protected $description = 'Exporta SQLite y archivos subidos en un paquete inicial cifrado para Render';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $snapshot = null;
        try {
            $connection = DB::connection();
            if ($connection->getDriverName() !== 'sqlite' || $connection->getDatabaseName() === ':memory:') {
                throw new RuntimeException('La exportación requiere la base SQLite existente en disco.');
            }
            $source = realpath($connection->getDatabaseName());
            if (! $source) {
                throw new RuntimeException('La base SQLite configurada no existe.');
            }
            RenderBootstrap::validateDatabase($source);
            $disk->makeDirectory('.');
            $snapshot = $disk->path('render-snapshot-'.Str::uuid().'.sqlite');
            $pdo = $connection->getPdo();
            $pdo->exec('VACUUM INTO '.$pdo->quote($snapshot));
            RenderBootstrap::validateDatabase($snapshot);
            if (filesize($snapshot) > 33554432) {
                throw new RuntimeException('La base supera 32 MB; requiere un procedimiento de transferencia específico.');
            }
            $database = file_get_contents($snapshot);
            if ($database === false) {
                throw new RuntimeException('No se pudo leer el respaldo consistente.');
            }
            $files = $this->uploadedFiles();
            if ($disk->exists('demo-access.json')) {
                $manifest = $disk->get('demo-access.json');
                $decoded = json_decode($manifest, true);
                if (! is_array($decoded) || ($decoded['version'] ?? null) !== 1 || ! is_array($decoded['cuentas'] ?? null)) {
                    throw new RuntimeException('El manifiesto privado de demostración no es válido.');
                }
                $files[] = $this->fileRecord('private/demo-access.json', $manifest);
            }
            if (count($files) > 200) {
                throw new RuntimeException('El paquete supera el límite de 200 archivos adicionales.');
            }
            $key = (string) config('app.key');
            $sealed = RenderBootstrap::seal(['version' => 1, 'exported_at' => now()->toIso8601String(),
                'database' => $this->fileRecord('database.sqlite', $database), 'tables' => RenderBootstrap::tablesFromSqlite($snapshot), 'files' => $files], $key);
            if (! $disk->put('render-bootstrap.enc', $sealed, ['visibility' => 'private'])) {
                throw new RuntimeException('No se pudo guardar el paquete cifrado privado.');
            }
            $variables = [['key' => 'APP_KEY', 'value' => $key]];
            if ($this->option('file')) {
                $variables[] = ['key' => 'ALPHA_BOOTSTRAP_FILE', 'value' => '/etc/secrets/alpha-bootstrap.enc'];
            } else {
                if (strlen($sealed) > RenderBootstrap::MAX_ENV_BYTES) {
                    throw new RuntimeException('El paquete supera 1 MB. El archivo cifrado está preparado; repite con --file y úsalo como archivo secreto.');
                }
                $parts = str_split($sealed, RenderBootstrap::CHUNK_SIZE);
                $variables[] = ['key' => 'ALPHA_BOOTSTRAP_PARTS', 'value' => (string) count($parts)];
                foreach ($parts as $index => $part) {
                    $variables[] = ['key' => 'ALPHA_BOOTSTRAP_'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT), 'value' => $part];
                }
            }
            $transfer = ['version' => 1, 'exported_at' => now()->toIso8601String(), 'database_sha256' => hash('sha256', $database),
                'uploaded_files' => count($files), 'package_bytes' => strlen($sealed), 'package_file' => $disk->path('render-bootstrap.enc'),
                'envVars' => $variables];
            if (! $disk->put('render-transfer.json', json_encode($transfer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), ['visibility' => 'private'])) {
                throw new RuntimeException('No se pudo guardar la configuración privada de transferencia.');
            }
            $this->info('Paquete inicial cifrado preparado: '.strlen($sealed).' bytes; '.count($files).' archivos adicionales.');
            $this->line('Configuración privada: '.$disk->path('render-transfer.json'));
            $this->line('Archivo cifrado: '.$disk->path('render-bootstrap.enc'));
            $this->line('No publiques estos archivos ni pegues su contenido en registros o repositorios.');

            return self::SUCCESS;
        } catch (\Throwable $error) {
            // Do not print process output, SQL, keys, credentials or encrypted chunks.
            $message = $error instanceof RuntimeException && ! $error instanceof ProcessFailedException
                ? $error->getMessage() : 'Comprueba la base SQLite, Git, APP_KEY y el almacenamiento privado.';
            $this->error('No se completó la exportación. '.$message);

            return self::FAILURE;
        } finally {
            if ($snapshot && is_file($snapshot)) {
                unlink($snapshot);
            }
        }
    }

    private function uploadedFiles(): array
    {
        $process = new Process(['git', 'ls-files', '-z', '--', 'public/images/avatars', 'public/images/productos', 'public/images/ejercicios'], base_path());
        $process->setTimeout(30);
        $process->mustRun();
        $tracked = array_fill_keys(array_filter(explode("\0", $process->getOutput())), true);
        $files = [];
        foreach (['avatars', 'productos', 'ejercicios'] as $folder) {
            $directory = public_path('images/'.$folder);
            if (! is_dir($directory)) {
                continue;
            }
            foreach (new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS) as $file) {
                $path = 'images/'.$folder.'/'.$file->getFilename();
                if (! $file->isFile() || $file->isLink() || isset($tracked['public/'.$path]) || ! RenderBootstrap::validFilePath($path)) {
                    continue;
                }
                if ($file->getSize() > 6291456) {
                    throw new RuntimeException('Una imagen supera el límite de la transferencia inicial.');
                }
                $bytes = file_get_contents($file->getPathname());
                $image = $bytes === false ? false : @getimagesizefromstring($bytes);
                if ($image === false || ! in_array($image['mime'], ['image/png', 'image/jpeg', 'image/webp'], true)) {
                    throw new RuntimeException('Una imagen subida no supera la comprobación de formato.');
                }
                $files[] = $this->fileRecord($path, $bytes);
            }
        }

        return $files;
    }

    private function fileRecord(string $path, string $bytes): array
    {
        return ['path' => $path, 'sha256' => hash('sha256', $bytes), 'data' => base64_encode($bytes)];
    }
}
