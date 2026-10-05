<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\VersionLegal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsentimientoLegal
{
    private const DOCUMENTOS = [
        'privacidad' => 'PRIVACIDAD',
        'terminos' => 'TERMINOS',
        'lesiones' => 'LESIONES',
        'derechos' => 'DERECHOS_DATOS',
    ];

    public function versionActual(): string
    {
        $version = config('legal.version');
        if (! is_string($version) || ! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]{0,31}\z/D', $version)) {
            throw ValidationException::withMessages([
                'aceptacion_legal' => 'La versión legal no está configurada correctamente. El responsable debe revisar LEGAL_VERSION.',
            ]);
        }

        return $version;
    }

    /** Current documents can be viewed without querying a database or creating a snapshot. */
    public function contenidoActual(string $documento): string
    {
        return $this->leerDocumento($documento, $this->versionActual());
    }

    /** An explicit version returns the archived text, not today's configuration. */
    public function contenido(string $documento, ?string $version = null): string
    {
        abort_unless(isset(self::DOCUMENTOS[$documento]), 404);
        if ($version === null) {
            return $this->contenidoActual($documento);
        }
        abort_unless(preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]{0,31}\z/D', $version), 404);
        $archivo = VersionLegal::where('version', $version)->firstOrFail();
        $documentos = $archivo->documentos;
        abort_unless($this->archivoValido($archivo), 503, 'No se pudo verificar la integridad de la versión legal archivada.');

        return $documentos[$documento];
    }

    /** Call only after an affirmative registration acceptance, inside the account transaction. */
    public function aceptar(Cliente $cliente): Cliente
    {
        $version = $this->versionActual();
        $documentos = [];
        foreach (self::DOCUMENTOS as $slug => $archivo) {
            $documentos[$slug] = $this->leerDocumento($slug, $version);
        }
        $hash = $this->huella($documentos);

        return DB::transaction(function () use ($cliente, $version, $documentos, $hash) {
            $archivo = VersionLegal::where('version', $version)->first();
            if (! $archivo) {
                try {
                    $archivo = VersionLegal::create(['version' => $version, 'hash_contenido' => $hash, 'documentos' => $documentos]);
                } catch (UniqueConstraintViolationException $exception) {
                    // A concurrent acceptance may have archived the same version first.
                    // Use a locking read so MySQL REPEATABLE READ sees the committed row.
                    $archivo = VersionLegal::where('version', $version)->lockForUpdate()->firstOrFail();
                }
            }
            if (! $this->archivoValido($archivo) || ! hash_equals($archivo->hash_contenido, $hash)) {
                throw ValidationException::withMessages([
                    'aceptacion_legal' => 'El responsable cambió los documentos o sus datos legales sin publicar una versión nueva. Debe actualizar LEGAL_VERSION antes de nuevos registros.',
                ]);
            }
            $cliente->forceFill(['legal_aceptado_en' => now(), 'legal_version' => $version, 'legal_requerido' => false])->save();

            return $cliente;
        });
    }

    private function leerDocumento(string $documento, string $version): string
    {
        abort_unless(isset(self::DOCUMENTOS[$documento]), 404);
        $contenido = file_get_contents(base_path('docs/'.self::DOCUMENTOS[$documento].'.md'));
        if ($contenido === false) {
            throw new \RuntimeException('No se pudo leer un documento legal del proyecto.');
        }
        $contenido = str_replace(["\r\n", "\r"], "\n", $contenido);
        foreach (config('legal.marcadores', []) as $clave => $valor) {
            // Encode HTML, then escape CommonMark syntax so configured names stay text.
            $texto = htmlspecialchars(str_replace(["\r\n", "\r", "\n"], ' ', (string) $valor), ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $texto = preg_replace_callback('/[\\\\`*_{}\[\]()<>#+.!|~-]/u', fn ($coincidencia) => '\\'.$coincidencia[0], $texto);
            $contenido = str_replace('{{'.$clave.'}}', $texto, $contenido);
        }
        // Keep the displayed version aligned with the identifier accepted and archived.
        $contenido = preg_replace('/^Versión:\s*[^\s]+/mu', 'Versión: '.$version.'.', $contenido, 1);

        return $contenido;
    }

    private function huella(array $documentos): string
    {
        // JSON storage engines can reorder object keys; canonicalize before hashing.
        ksort($documentos);

        return hash('sha256', json_encode($documentos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function archivoValido(VersionLegal $archivo): bool
    {
        $documentos = $archivo->documentos;
        if (! is_array($documentos) || count($documentos) !== count(self::DOCUMENTOS)) {
            return false;
        }
        foreach (self::DOCUMENTOS as $slug => $nombre) {
            if (! isset($documentos[$slug]) || ! is_string($documentos[$slug])) {
                return false;
            }
        }

        return is_string($archivo->hash_contenido) && hash_equals($archivo->hash_contenido, $this->huella($documentos));
    }
}
