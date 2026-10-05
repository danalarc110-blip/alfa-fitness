<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\SolicitudDatos;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentimientoLegalTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_explicit_consent_and_records_server_version(): void
    {
        $datos = ['nombre' => 'Nueva', 'correo' => 'nueva@example.test', 'password' => 'Clave!Fuerte2026', 'password_confirmation' => 'Clave!Fuerte2026'];
        $this->post(route('cliente.registro'), $datos)->assertSessionHasErrors('aceptacion_legal');
        $this->assertDatabaseCount('clientes', 0);
        $this->post(route('cliente.registro'), $datos + ['aceptacion_legal' => 1, 'legal_version' => 'falsa'])->assertSessionHasErrors('legal_version');
        $this->assertDatabaseCount('clientes', 0);
        $this->post(route('cliente.registro'), $datos + ['aceptacion_legal' => 1, 'legal_version' => config('legal.version'), 'legal_aceptado_en' => '1900-01-01'])->assertSessionHasNoErrors();
        $cliente = Cliente::firstOrFail();
        $this->assertSame(config('legal.version'), $cliente->legal_version);
        $this->assertTrue($cliente->legal_aceptado_en->isToday());
    }

    public function test_legacy_clients_are_not_falsely_backfilled_or_blocked(): void
    {
        $cliente = Cliente::create(['nombre' => 'Anterior', 'correo' => 'anterior@example.test', 'password' => 'Clave!Fuerte2026', 'activo' => true]);
        $this->assertNull($cliente->legal_aceptado_en);
        $this->assertNull($cliente->legal_version);
        $this->post(route('cliente.login.submit'), ['correo' => $cliente->correo, 'password' => 'Clave!Fuerte2026'])->assertRedirect(route('cliente.dashboard'));
    }

    public function test_documents_are_public_and_traversal_is_not_a_document(): void
    {
        foreach (['privacidad', 'terminos', 'lesiones', 'derechos'] as $documento) {
            $this->get(route('legal.documento', $documento))->assertOk()->assertSee('Alpha Fitness');
        }
        $this->get('/legal/INFORME_FINAL')->assertNotFound();
        $this->get('/login')->assertOk()->assertSee('aceptacion_legal')->assertSee(route('legal.documento', 'privacidad'), false);
    }

    public function test_data_request_is_private_and_only_admin_can_manage_it(): void
    {
        $this->post(route('legal.solicitud'), ['nombre' => 'Titular', 'correo' => 'titular@example.test', 'tipo' => 'acceso', 'detalle' => 'Solicito mis datos.', 'aceptacion' => 1])->assertRedirect();
        $solicitud = SolicitudDatos::firstOrFail();
        $this->get(route('legal.solicitudes'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']))->get(route('legal.solicitudes'))->assertForbidden();
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->get(route('legal.solicitudes'))->assertOk()->assertSee('Solicito mis datos.');
        $this->patch(route('legal.resolver', $solicitud), ['estado' => 'en_revision', 'notas_internas' => 'Identidad pendiente.'])->assertSessionHasNoErrors();
        $this->assertSame('en_revision', $solicitud->fresh()->estado);
        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => 'cliente@example.test', 'activo' => true]);
        $this->actingAs($cliente, 'cliente')->get(route('legal.solicitudes'))->assertForbidden();
    }

    public function test_expired_google_pending_identity_cannot_create_an_account(): void
    {
        $this->withSession(['google_registro_pendiente' => ['nombre' => 'Google', 'correo' => 'google@example.test', 'google_id' => '123', 'expires' => now()->subMinute()->timestamp]])
            ->post(route('cliente.google.aceptar'), ['aceptacion_legal' => 1, 'legal_version' => config('legal.version')])->assertRedirect(route('login'))->assertSessionHasErrors('correo');
        $this->assertDatabaseCount('clientes', 0);
    }
}
