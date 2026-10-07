<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PausaMembresia;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuditoriaLogicaInvariantesTest extends TestCase
{
    use RefreshDatabase;

    private User $secretaria;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->secretaria = User::create([
            'name' => 'Secretaria Test',
            'email' => 'secretaria@alfa.test',
            'password' => 'Password!123',
            'rol' => 'Secretaria',
            'activo' => true,
        ]);

        $this->cliente = Cliente::create([
            'nombre' => 'Carlos Cliente',
            'correo' => 'carlos@alfa.test',
            'password' => 'Password!123',
            'activo' => true,
        ]);
    }

    public function test_pause_start_date_cannot_be_after_membership_expiration(): void
    {
        $membresia = Membresia::create([
            'cliente_id' => $this->cliente->id,
            'plan' => 'Plan Mensual',
            'importe' => 30.00,
            'inicio' => today(),
            'fin' => today()->addDays(15),
            'cancelada' => false,
        ]);

        // Attempt pause starting 5 days AFTER membership has expired
        $response = $this->actingAs($this->cliente, 'cliente')
            ->post(route('membresias.pausa.solicitar', $membresia), [
                'dias' => 10,
                'motivo' => 'Viaje posterior',
                'inicio_pausa' => today()->addDays(20)->toDateString(),
            ]);

        $response->assertSessionHasErrors('inicio_pausa');
        $this->assertDatabaseCount('pausas_membresia', 0);
    }

    public function test_future_scheduled_pause_can_be_cancelled_or_resumed_before_it_starts(): void
    {
        $inicio = today();
        $finOriginal = today()->addDays(30);

        $membresia = Membresia::create([
            'cliente_id' => $this->cliente->id,
            'plan' => 'Plan Mensual',
            'importe' => 30.00,
            'inicio' => $inicio,
            'fin' => $finOriginal,
            'cancelada' => false,
        ]);

        // Request a 10-day pause starting 7 days from now
        $this->actingAs($this->cliente, 'cliente')
            ->post(route('membresias.pausa.solicitar', $membresia), [
                'dias' => 10,
                'motivo' => 'Viaje programado en una semana',
                'inicio_pausa' => today()->addDays(7)->toDateString(),
            ]);

        $pausa = PausaMembresia::firstOrFail();

        // Switch to staff (logout client first)
        auth('cliente')->logout();

        // Staff approves it -> extends fin by 10 days
        $this->actingAs($this->secretaria, 'web')
            ->patch(route('membresias.pausa.aprobar', $pausa));

        $this->assertEquals($finOriginal->copy()->addDays(10)->toDateString(), $membresia->fresh()->fin->toDateString());

        // Client or staff decides to cancel/resume before it starts (today < inicio_pausa)
        $this->actingAs($this->secretaria, 'web')
            ->patch(route('membresias.reanudar', $membresia));

        $membresia->refresh();
        $pausa->refresh();

        // All 10 days returned to original end date
        $this->assertEquals($finOriginal->toDateString(), $membresia->fin->toDateString());
        $this->assertSame(0, $pausa->dias);
        $this->assertSame('reanudada_anticipada', $pausa->estado);
    }

    public function test_cannot_approve_pause_on_cancelled_or_expired_membership(): void
    {
        $membresia = Membresia::create([
            'cliente_id' => $this->cliente->id,
            'plan' => 'Plan Mensual',
            'importe' => 30.00,
            'inicio' => today()->subDays(40),
            'fin' => today()->subDays(10), // expired
            'cancelada' => false,
        ]);

        $pausa = PausaMembresia::create([
            'membresia_id' => $membresia->id,
            'cliente_id' => $this->cliente->id,
            'dias' => 7,
            'motivo' => 'Pausa antigua',
            'estado' => 'pendiente',
            'inicio_pausa' => today(),
            'fin_pausa_estimada' => today()->addDays(6),
        ]);

        // Attempting to approve on expired membership fails
        $response = $this->actingAs($this->secretaria, 'web')
            ->patch(route('membresias.pausa.aprobar', $pausa));

        $response->assertSessionHasErrors('pausa');
        $this->assertSame('pendiente', $pausa->fresh()->estado);
    }

    public function test_cannot_reject_already_approved_pause(): void
    {
        $membresia = Membresia::create([
            'cliente_id' => $this->cliente->id,
            'plan' => 'Plan Mensual',
            'importe' => 30.00,
            'inicio' => today(),
            'fin' => today()->addDays(30),
            'cancelada' => false,
        ]);

        $pausa = PausaMembresia::create([
            'membresia_id' => $membresia->id,
            'cliente_id' => $this->cliente->id,
            'dias' => 7,
            'motivo' => 'Vacaciones',
            'estado' => 'aprobada',
            'inicio_pausa' => today(),
            'fin_pausa_estimada' => today()->addDays(6),
        ]);

        $response = $this->actingAs($this->secretaria, 'web')
            ->patch(route('membresias.pausa.rechazar', $pausa));

        $response->assertSessionHasErrors('pausa');
        $this->assertSame('aprobada', $pausa->fresh()->estado);
    }

    public function test_pause_on_cancelled_membership_does_not_block_entry_with_new_active_membership(): void
    {
        // Cancelled membership with an old approved pause
        $oldMembresia = Membresia::create([
            'cliente_id' => $this->cliente->id,
            'plan' => 'Plan Antiguo',
            'importe' => 20.00,
            'inicio' => today()->subDays(10),
            'fin' => today()->addDays(20),
            'cancelada' => true,
        ]);

        PausaMembresia::create([
            'membresia_id' => $oldMembresia->id,
            'cliente_id' => $this->cliente->id,
            'dias' => 10,
            'motivo' => 'Pausa previa',
            'estado' => 'aprobada',
            'inicio_pausa' => today(),
            'fin_pausa_estimada' => today()->addDays(9),
        ]);

        // New valid active membership
        Membresia::create([
            'cliente_id' => $this->cliente->id,
            'plan' => 'Plan Anual',
            'importe' => 300.00,
            'inicio' => today(),
            'fin' => today()->addYear(),
            'cancelada' => false,
        ]);

        // Attempt entry
        $response = $this->actingAs($this->secretaria, 'web')
            ->post(route('asistencia.store'), [
                'cliente_id' => $this->cliente->id,
            ]);

        $response->assertRedirect()->assertSessionHas('status');
        $this->assertDatabaseHas('asistencias', [
            'cliente_id' => $this->cliente->id,
            'fecha_salida' => null,
        ]);
    }

    public function test_orphan_visit_older_than_12h_is_auto_closed_on_new_entry_and_aforo_is_not_inflated(): void
    {
        // Visit from 2 days ago without exit
        $orphan = Asistencia::create([
            'cliente_id' => $this->cliente->id,
            'fecha_hora' => now()->subDays(2),
            'fecha_salida' => null,
            'tipo_acceso' => 'entrada',
        ]);

        // Verify Dashboard aforo does not count the 2-day-old orphan visit
        $dashResponse = $this->actingAs($this->secretaria, 'web')->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertViewHas('aforo', function ($aforo) {
            return $aforo['actual'] === 0;
        });

        // Member returns today: entry is registered and orphan visit is automatically closed
        $response = $this->actingAs($this->secretaria, 'web')
            ->post(route('asistencia.store'), [
                'cliente_id' => $this->cliente->id,
            ]);

        $response->assertRedirect()->assertSessionHas('status');

        $orphan->refresh();
        $this->assertNotNull($orphan->fecha_salida);
        $this->assertEquals($orphan->fecha_hora->copy()->addHours(2)->toDateTimeString(), $orphan->fecha_salida->toDateTimeString());

        // New attendance exists
        $this->assertDatabaseHas('asistencias', [
            'cliente_id' => $this->cliente->id,
            'fecha_salida' => null,
        ]);
    }

    public function test_smtp_failure_during_receipt_mail_is_handled_gracefully(): void
    {
        $producto = Producto::create([
            'nombre' => 'Creatina 300g',
            'precio' => 20.00,
            'stock' => 5,
            'activo' => true,
        ]);

        $venta = Venta::create([
            'user_id' => $this->secretaria->id,
            'cliente_id' => $this->cliente->id,
            'total' => 20.00,
            'metodo_pago' => 'Efectivo',
        ]);

        $venta->detalles()->create([
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 20.00,
            'subtotal' => 20.00,
        ]);

        // Mock Mail to throw exception
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('Connection to SMTP server lost.'));

        $response = $this->actingAs($this->secretaria, 'web')
            ->post(route('ventas.enviar-correo', $venta), [
                'correo' => 'cliente@test.com',
            ]);

        // Must redirect with session error, NOT crash with 500
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_cannot_create_sale_for_inactive_client(): void
    {
        $inactiveClient = Cliente::create([
            'nombre' => 'Cliente Inactivo',
            'correo' => 'inactivo@alfa.test',
            'password' => 'Password!123',
            'activo' => false,
        ]);

        $producto = Producto::create([
            'nombre' => 'Barra Proteica',
            'precio' => 3.00,
            'stock' => 10,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->secretaria, 'web')
            ->post(route('ventas.store'), [
                'cliente_id' => $inactiveClient->id,
                'metodo_pago' => 'Efectivo',
                'items' => [
                    [
                        'producto_id' => $producto->id,
                        'cantidad' => 1,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseCount('ventas', 0);
    }
}
