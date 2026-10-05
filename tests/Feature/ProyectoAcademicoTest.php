<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\VersionLegal;
use App\Services\ConsentimientoLegal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProyectoAcademicoTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_discloses_demo_without_removing_original_forms_or_consent(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('Proyecto estudiantil')
            ->assertSee('Personal')->assertSee('Clientes')
            ->assertSee('name="aceptacion_legal"', false)
            ->assertSee('name="password"', false);
    }

    public function test_public_documents_are_educational_and_data_requests_stay_available(): void
    {
        foreach (['privacidad', 'terminos', 'lesiones', 'derechos'] as $documento) {
            $this->get(route('legal.documento', $documento))->assertOk()
                ->assertSee('Plantilla educativa')->assertSee('Proyecto estudiantil');
        }
        $this->get(route('legal.documento', 'derechos'))
            ->assertSee(route('legal.solicitud'), false)->assertSee('Enviar solicitud');
    }

    public function test_academic_registration_still_requires_affirmative_current_acceptance(): void
    {
        config(['legal.version' => '2026-10-05-academico']);
        $datos = ['nombre' => 'Demostración', 'correo' => 'academico@example.test', 'password' => 'ClaveSegura!2026', 'password_confirmation' => 'ClaveSegura!2026'];
        $this->post(route('cliente.registro'), $datos)->assertSessionHasErrors('aceptacion_legal');
        $this->post(route('cliente.registro'), $datos + ['aceptacion_legal' => 1, 'legal_version' => '2026-10-04'])->assertSessionHasErrors('legal_version');
        $this->assertDatabaseCount('clientes', 0);
        $this->post(route('cliente.registro'), $datos + ['aceptacion_legal' => 1, 'legal_version' => '2026-10-05-academico'])->assertSessionHasNoErrors()->assertRedirect(route('cliente.dashboard'));
        $cliente = Cliente::firstOrFail();
        $this->assertSame('2026-10-05-academico', $cliente->legal_version);
        $this->assertNotNull($cliente->legal_aceptado_en);
        $this->get(route('cliente.dashboard'))->assertOk()->assertSee('Proyecto estudiantil');
        $this->get(route('gestion.usuarios.index'))->assertForbidden();
    }

    public function test_new_academic_version_does_not_rewrite_archived_documents(): void
    {
        config(['legal.version' => '2026-10-04']);
        $servicio = app(ConsentimientoLegal::class);
        $servicio->aceptar(new Cliente(['nombre' => 'Anterior', 'correo' => 'anterior@example.test', 'activo' => true]));
        $archivo = VersionLegal::firstOrFail()->getAttributes();
        config(['legal.version' => '2026-10-05-academico']);
        $servicio->aceptar(new Cliente(['nombre' => 'Nuevo', 'correo' => 'nuevo@example.test', 'activo' => true]));
        $this->assertDatabaseCount('versiones_legales', 2);
        $this->assertSame($archivo, VersionLegal::where('version', '2026-10-04')->firstOrFail()->getAttributes());
        $this->assertSame('2026-10-04', Cliente::where('correo', 'anterior@example.test')->firstOrFail()->legal_version);
    }
}
