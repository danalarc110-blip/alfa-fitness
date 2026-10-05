<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\VersionLegal;
use App\Services\ConsentimientoLegal;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class VersionLegalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['legal.version' => '2026-10-04', 'legal.marcadores.NOMBRE_RESPONSABLE' => 'Responsable de prueba']);
    }

    private function nuevoCliente(string $correo = 'consentimiento@example.test'): Cliente
    {
        return new Cliente(['nombre' => 'Cliente de prueba', 'correo' => $correo, 'password' => 'PruebaSegura!123', 'activo' => true]);
    }

    public function test_aceptacion_archiva_cuatro_documentos_resueltos_y_guarda_fecha_version_y_bandera(): void
    {
        $cliente = $this->nuevoCliente();
        $cliente->forceFill(['legal_requerido' => true]);
        $resultado = app(ConsentimientoLegal::class)->aceptar($cliente);

        $this->assertSame($cliente, $resultado);
        $this->assertTrue($cliente->exists);
        $this->assertSame('2026-10-04', $cliente->fresh()->legal_version);
        $this->assertNotNull($cliente->fresh()->legal_aceptado_en);
        $this->assertFalse($cliente->fresh()->legal_requerido);
        $this->assertDatabaseCount('versiones_legales', 1);
        $archivo = VersionLegal::firstOrFail();
        $this->assertEqualsCanonicalizing(['privacidad', 'terminos', 'lesiones', 'derechos'], array_keys($archivo->documentos));
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $archivo->hash_contenido);
        $this->assertStringContainsString('Responsable de prueba', $archivo->documentos['privacidad']);
        $this->assertStringNotContainsString('{{NOMBRE_RESPONSABLE}}', $archivo->documentos['privacidad']);
        foreach ($archivo->documentos as $texto) {
            $this->assertStringContainsString('Versión: 2026-10-04.', $texto);
            $this->assertStringNotContainsString("\r", $texto);
        }
    }

    public function test_varios_clientes_comparten_un_archivo_sin_reescribirlo(): void
    {
        $servicio = app(ConsentimientoLegal::class);
        $servicio->aceptar($this->nuevoCliente());
        $original = VersionLegal::firstOrFail()->getAttributes();
        $this->travel(1)->minutes();
        $servicio->aceptar($this->nuevoCliente('segunda@example.test'));

        $this->assertDatabaseCount('versiones_legales', 1);
        $this->assertDatabaseCount('clientes', 2);
        $this->assertSame($original, VersionLegal::firstOrFail()->getAttributes());
        $this->assertSame('2026-10-04', Cliente::where('correo', 'segunda@example.test')->firstOrFail()->legal_version);
    }

    public function test_modificar_datos_legales_sin_nueva_version_rechaza_y_revierte_alta(): void
    {
        $servicio = app(ConsentimientoLegal::class);
        $servicio->aceptar($this->nuevoCliente());
        $original = VersionLegal::firstOrFail()->getAttributes();
        config(['legal.marcadores.NOMBRE_RESPONSABLE' => 'Otro responsable']);
        try {
            DB::transaction(function () use ($servicio) {
                $cliente = $this->nuevoCliente('no-creado@example.test');
                $cliente->save();
                $servicio->aceptar($cliente);
            });
            $this->fail('Debe exigir una versión nueva y revertir el cliente.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('aceptacion_legal', $exception->errors());
            $this->assertStringContainsString('LEGAL_VERSION', $exception->getMessage());
        }
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseMissing('clientes', ['correo' => 'no-creado@example.test']);
        $this->assertSame($original, VersionLegal::firstOrFail()->getAttributes());
    }

    public function test_version_nueva_preserva_textos_anteriores_con_su_configuracion_exacta(): void
    {
        $servicio = app(ConsentimientoLegal::class);
        $servicio->aceptar($this->nuevoCliente());
        $anterior = $servicio->contenido('privacidad', '2026-10-04');
        config(['legal.version' => '2026-10-04.1', 'legal.marcadores.NOMBRE_RESPONSABLE' => 'Nuevo responsable']);
        $servicio->aceptar($this->nuevoCliente('nueva-version@example.test'));

        $this->assertDatabaseCount('versiones_legales', 2);
        $this->assertSame($anterior, $servicio->contenido('privacidad', '2026-10-04'));
        $nuevo = $servicio->contenido('privacidad', '2026-10-04.1');
        $this->assertStringContainsString('Nuevo responsable', $nuevo);
        $this->assertStringContainsString('Versión: 2026-10-04.1.', $nuevo);
        $this->assertStringNotContainsString('Nuevo responsable', $anterior);
        $this->assertSame('2026-10-04.1', Cliente::where('correo', 'nueva-version@example.test')->firstOrFail()->legal_version);
    }

    public function test_documento_actual_no_requiere_base_de_datos_y_marcadores_son_texto_sin_enlaces_ni_html(): void
    {
        $nombre = "Nombre [enlace](https://no-permitido.example.test) O'Brien <script>alert(1)</script>\n# Título";
        config(['legal.marcadores.NOMBRE_RESPONSABLE' => $nombre]);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $contenido = app(ConsentimientoLegal::class)->contenido('privacidad');
        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();
        $html = Str::markdown($contenido, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $this->assertStringNotContainsString('href="https://no-permitido.example.test"', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<h1>Título', $html);
        $visible = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->assertStringContainsString(str_replace("\n", ' ', $nombre), $visible);
    }

    public function test_version_desconocida_no_sustituye_con_texto_actual(): void
    {
        $this->expectException(ModelNotFoundException::class);
        app(ConsentimientoLegal::class)->contenido('privacidad', 'no-publicada');
    }

    public function test_slug_de_documento_no_permite_recorrido_de_directorios(): void
    {
        try {
            app(ConsentimientoLegal::class)->contenidoActual('../.env');
            $this->fail('Solo se pueden leer los cuatro documentos públicos.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }

    public function test_version_de_configuracion_larga_o_insegura_impide_guardar_aceptacion(): void
    {
        foreach ([str_repeat('v', 33), '../secreto', 'versión con espacio', '', null] as $version) {
            config(['legal.version' => $version]);
            try {
                app(ConsentimientoLegal::class)->aceptar($this->nuevoCliente());
                $this->fail('Debe rechazar una versión inválida.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('aceptacion_legal', $exception->errors());
            }
        }
        $this->assertDatabaseCount('versiones_legales', 0);
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_modelo_no_permite_modificar_ni_eliminar_versiones_archivadas(): void
    {
        app(ConsentimientoLegal::class)->aceptar($this->nuevoCliente());
        $archivo = VersionLegal::firstOrFail();
        foreach (['actualizar', 'eliminar'] as $accion) {
            try {
                if ($accion === 'actualizar') {
                    $archivo->update(['version' => 'modificada']);
                } else {
                    $archivo->delete();
                }
                $this->fail('No se deben alterar archivos legales existentes.');
            } catch (\LogicException $exception) {
                $this->assertStringContainsString('versión legal archivada', $exception->getMessage());
            }
        }
        $this->assertDatabaseCount('versiones_legales', 1);
        $this->assertDatabaseHas('versiones_legales', ['version' => '2026-10-04']);
    }

    public function test_manipulacion_directa_del_archivo_rechaza_lectura_y_no_atribuye_aceptacion(): void
    {
        $servicio = app(ConsentimientoLegal::class);
        $servicio->aceptar($this->nuevoCliente());
        DB::table('versiones_legales')->update(['documentos' => json_encode(['privacidad' => 'Texto alterado'])]);
        try {
            $servicio->contenido('privacidad', '2026-10-04');
            $this->fail('Un archivo incompleto no debe mostrarse como auténtico.');
        } catch (HttpException $exception) {
            $this->assertSame(503, $exception->getStatusCode());
        }
        try {
            $servicio->aceptar($this->nuevoCliente('rechazado@example.test'));
            $this->fail('No debe registrar aceptación contra un archivo alterado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('aceptacion_legal', $exception->errors());
        }
        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_error_de_guardado_del_cliente_revierte_archivo_nuevo(): void
    {
        $this->nuevoCliente()->save();
        try {
            app(ConsentimientoLegal::class)->aceptar($this->nuevoCliente());
            $this->fail('Debe fallar el correo duplicado y revertir el archivo nuevo.');
        } catch (QueryException $exception) {
            $this->assertNotNull($exception);
        }
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('versiones_legales', 0);
        $this->assertNull(Cliente::firstOrFail()->legal_aceptado_en);
    }

    public function test_migracion_de_archivo_es_reversible_sin_eliminar_clientes(): void
    {
        $this->nuevoCliente()->save();
        $migracion = require database_path('migrations/2026_10_04_110000_create_versiones_legales.php');
        $migracion->down();
        $this->assertFalse(Schema::hasTable('versiones_legales'));
        $this->assertDatabaseCount('clientes', 1);
        $migracion->up();
        $this->assertTrue(Schema::hasTable('versiones_legales'));
        $this->assertDatabaseCount('versiones_legales', 0);
    }
}
