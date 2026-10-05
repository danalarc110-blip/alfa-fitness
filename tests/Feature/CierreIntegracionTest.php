<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PagoMembresia;
use App\Models\PlanMembresia;
use App\Models\Producto;
use App\Models\SolicitudMembresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CierreIntegracionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_staff_created_client_accepts_personally_while_legacy_client_is_not_blocked(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $this->actingAs($secretaria)->post(route('gestion.clientes.store'), ['nombre' => 'Alta recepción', 'correo' => 'alta@example.test', 'password' => 'Clave!Fuerte2026', 'password_confirmation' => 'Clave!Fuerte2026'])->assertSessionHasNoErrors();
        $cliente = Cliente::where('correo', 'alta@example.test')->firstOrFail();
        $this->assertTrue($cliente->legal_requerido);
        auth('web')->logout();
        $this->actingAs($cliente, 'cliente')->get(route('cliente.dashboard'))->assertRedirect(route('cliente.legal.mostrar'));
        $this->get(route('cliente.legal.mostrar'))->assertOk();
        $this->post(route('cliente.legal.aceptar'), ['aceptacion_legal' => 1, 'legal_version' => config('legal.version')])->assertRedirect(route('cliente.dashboard'));
        $this->assertFalse($cliente->fresh()->legal_requerido);
        $this->get(route('cliente.dashboard'))->assertOk();
    }

    public function test_cancelled_access_does_not_erase_payment_and_legacy_display_remains(): void
    {
        $cliente = Cliente::create(['nombre' => 'Pagado', 'correo' => 'pago@example.test', 'activo' => true]);
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $solicitud = SolicitudMembresia::create(['cliente_id' => $cliente->id, 'plan_id' => PlanMembresia::firstOrFail()->id, 'plan_nombre' => 'Mensual', 'precio_acordado' => 25, 'duracion_dias' => 30]);
        $m = Membresia::create(['cliente_id' => $cliente->id, 'solicitud_id' => $solicitud->id, 'plan' => 'Mensual', 'importe' => 25, 'inicio' => today(), 'fin' => today()->addDays(29), 'cancelada' => true]);
        PagoMembresia::create(['membresia_id' => $m->id, 'solicitud_id' => $solicitud->id, 'registrado_por' => $secretaria->id, 'importe' => 25, 'pagado_en' => now(), 'metodo_pago' => 'tarjeta']);
        Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Legado', 'importe' => 10, 'inicio' => today(), 'fin' => today()->addDays(29), 'cancelada' => false]);
        $this->actingAs(User::factory()->create(['rol' => 'Administrador']))->get(route('dashboard'))->assertOk()->assertSee('$35.00');
    }

    public function test_card_details_are_not_accepted_in_payment_notes_or_explicit_fields(): void
    {
        $producto = Producto::create(['nombre' => 'Agua', 'precio' => 1, 'stock' => 5, 'activo' => true]);
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']));
        $datos = ['metodo_pago' => 'Tarjeta', 'items' => [['producto_id' => $producto->id, 'cantidad' => 1]]];
        $this->post(route('ventas.store'), $datos + ['notas' => '4111 1111 1111 1111'])->assertSessionHasErrors('notas');
        $this->post(route('ventas.store'), $datos + ['cvv' => '123'])->assertSessionHasErrors('cvv');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(5, $producto->fresh()->stock);
    }

    public function test_registration_rejects_bcrypt_unicode_truncation(): void
    {
        $password = str_repeat('é', 35).'Aa1!';
        $this->post(route('cliente.registro'), ['nombre' => 'Nueva', 'correo' => 'larga@example.test', 'password' => $password, 'password_confirmation' => $password, 'aceptacion_legal' => 1, 'legal_version' => config('legal.version')])->assertSessionHasErrors('password');
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_legacy_dual_guard_session_cannot_open_employee_dashboard(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $cliente = Cliente::create(['nombre' => 'Privado', 'correo' => 'privado@example.test', 'activo' => true]);
        $this->actingAs($admin, 'web')->actingAs($cliente, 'cliente');
        $this->get(route('dashboard'))->assertRedirect(route('cliente.dashboard'));
        $this->assertGuest('web');
        $this->assertAuthenticatedAs($cliente, 'cliente');
        $this->get(route('cliente.dashboard'))->assertOk();
    }
}
