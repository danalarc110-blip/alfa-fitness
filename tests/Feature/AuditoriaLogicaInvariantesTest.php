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
                'venta_uuid' => (string) \Illuminate\Support\Str::uuid(),
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

    public function test_trainer_progress_selector_in_routines_only_lists_assigned_clients_while_admin_sees_all(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador', 'name' => 'Entrenador Alfa']);
        $admin = User::factory()->create(['rol' => 'Administrador', 'name' => 'Admin Alfa']);

        $vinculado = Cliente::create([
            'nombre' => 'Atleta Vinculado',
            'correo' => 'vinculado@alfa.test',
            'password' => 'Password!123',
            'activo' => true,
        ]);

        \App\Models\Rutina::create([
            'user_id' => $vinculado->id,
            'user_type' => 'cliente',
            'nombre' => 'Rutina Asignada',
            'objetivo' => 'Fuerza',
            'nivel' => 'Intermedio',
            'asignado_por' => $coach->name,
            'asignado_por_id' => $coach->id,
            'dias_por_semana' => 3,
        ]);

        // Coach sees all active clients in $clientes (for assigning new routines),
        // but only assigned clients in $clientesProgreso (for progress monitoring).
        $coachResponse = $this->actingAs($coach, 'web')->get(route('entrenamientos.index'));
        $coachResponse->assertOk()
            ->assertViewHas('clientes', fn ($c) => $c->contains('id', $vinculado->id) && $c->contains('id', $this->cliente->id))
            ->assertViewHas('clientesProgreso', fn ($cp) => $cp->contains('id', $vinculado->id) && ! $cp->contains('id', $this->cliente->id))
            ->assertSee(route('entrenador.cliente.progreso', $vinculado), false)
            ->assertDontSee(route('entrenador.cliente.progreso', $this->cliente), false);

        // Administrator sees all active clients in $clientesProgreso.
        $adminResponse = $this->actingAs($admin, 'web')->get(route('entrenamientos.index'));
        $adminResponse->assertOk()
            ->assertViewHas('clientesProgreso', fn ($cp) => $cp->contains('id', $vinculado->id) && $cp->contains('id', $this->cliente->id));
    }

    public function test_scheduled_future_pause_and_pending_pause_render_correct_actions_in_membresias_view(): void
    {
        $membresiaProgramada = Membresia::create([
            'cliente_id' => $this->cliente->id,
            'plan' => 'Plan Trimestral',
            'importe' => 75.00,
            'inicio' => today(),
            'fin' => today()->addDays(90),
            'cancelada' => false,
        ]);

        PausaMembresia::create([
            'membresia_id' => $membresiaProgramada->id,
            'cliente_id' => $this->cliente->id,
            'dias' => 7,
            'motivo' => 'Viaje futuro aprobado',
            'estado' => 'aprobada',
            'inicio_pausa' => today()->addDays(5),
            'fin_pausa_estimada' => today()->addDays(11),
            'aprobada_por' => $this->secretaria->id,
        ]);

        $response = $this->actingAs($this->cliente, 'cliente')->get(route('membresias.index'));
        $response->assertOk()
            ->assertSee('Programada:')
            ->assertSee(route('membresias.reanudar', $membresiaProgramada), false)
            ->assertDontSee("abrirModalPausa({$membresiaProgramada->id},", false);
    }

    public function test_late_pause_approval_shifts_start_to_today_and_does_not_charge_past_unfrozen_days(): void
    {
        $finOriginal = today()->addDays(30);

        $membresia = Membresia::create([
            'cliente_id' => $this->cliente->id,
            'plan' => 'Plan Mensual',
            'importe' => 30.00,
            'inicio' => today()->subDays(5),
            'fin' => $finOriginal,
            'cancelada' => false,
        ]);

        // Request was submitted 3 days ago for 10 days, but remained pending until today
        $pausa = PausaMembresia::create([
            'membresia_id' => $membresia->id,
            'cliente_id' => $this->cliente->id,
            'dias' => 10,
            'motivo' => 'Viaje familiar',
            'estado' => 'pendiente',
            'inicio_pausa' => today()->subDays(3),
            'fin_pausa_estimada' => today()->addDays(6),
        ]);

        $this->actingAs($this->secretaria, 'web')
            ->patch(route('membresias.pausa.aprobar', $pausa))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $pausa->refresh();
        $membresia->refresh();

        $this->assertEquals(today()->toDateString(), $pausa->inicio_pausa->toDateString());
        $this->assertEquals(today()->addDays(9)->toDateString(), $pausa->fin_pausa_estimada->toDateString());
        $this->assertEquals($finOriginal->copy()->addDays(10)->toDateString(), $membresia->fin->toDateString());

        // Resuming on the same day of approval only consumes 1 day (today), returning 9 unused days
        $this->actingAs($this->secretaria, 'web')
            ->patch(route('membresias.reanudar', $membresia))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $pausa->refresh();
        $membresia->refresh();

        $this->assertSame(1, $pausa->dias);
        $this->assertEquals($finOriginal->copy()->addDay()->toDateString(), $membresia->fin->toDateString());
    }

    public function test_workout_completion_modal_hides_pr_link_for_staff_and_shows_it_for_client(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);

        $rutinaCoach = \App\Models\Rutina::create([
            'user_id' => $coach->id,
            'user_type' => 'web',
            'nombre' => 'Rutina Coach',
            'objetivo' => 'Fuerza',
            'nivel' => 'Avanzado',
            'dias_por_semana' => 3,
        ]);

        $rutinaCliente = \App\Models\Rutina::create([
            'user_id' => $this->cliente->id,
            'user_type' => 'cliente',
            'nombre' => 'Rutina Cliente',
            'objetivo' => 'Salud',
            'nivel' => 'Intermedio',
            'dias_por_semana' => 3,
        ]);

        $this->actingAs($coach, 'web')
            ->get(route('entrenamientos.entrenar', $rutinaCoach))
            ->assertOk()
            ->assertDontSee('Registrar PRs');

        auth('web')->logout();

        $this->actingAs($this->cliente, 'cliente')
            ->get(route('entrenamientos.entrenar', $rutinaCliente))
            ->assertOk()
            ->assertSee('Registrar PRs');
    }

    public function test_orphan_visit_older_than_12h_does_not_inflate_reception_kpis_or_client_active_status(): void
    {
        Asistencia::create([
            'cliente_id' => $this->cliente->id,
            'fecha_hora' => now()->subHours(16),
            'fecha_salida' => null,
            'tipo_acceso' => 'entrada',
        ]);

        // Reception dashboard KPI 'Dentro ahora' matches live aforo (0)
        $this->actingAs($this->secretaria, 'web')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('metricas', fn ($metricas) => collect($metricas)->firstWhere(0, 'Dentro ahora')[1] === 0);

        // Reception attendance page shows dentro = 0 and huerfanasCount = 1
        $this->actingAs($this->secretaria, 'web')
            ->get(route('asistencia.index'))
            ->assertOk()
            ->assertViewHas('dentro', 0)
            ->assertViewHas('huerfanasCount', 1);

        auth('web')->logout();

        // Client attendance page does not show client as currently inside the gym
        $this->actingAs($this->cliente, 'cliente')
            ->get(route('cliente.asistencia'))
            ->assertOk()
            ->assertViewHas('visitaActual', null)
            ->assertSee('Sin sesión activa')
            ->assertSee('Salida pendiente');
    }

    public function test_membresias_search_query_filters_solicitudes_membresias_and_pending_pauses(): void
    {
        $otroCliente = Cliente::create([
            'nombre' => 'Pedro Omitido',
            'correo' => 'pedro@alfa.test',
            'password' => 'Password!123',
            'activo' => true,
        ]);

        $m1 = Membresia::create([
            'cliente_id' => $this->cliente->id,
            'plan' => 'Plan Mensual',
            'importe' => 30.00,
            'inicio' => today(),
            'fin' => today()->addDays(30),
            'cancelada' => false,
        ]);

        $m2 = Membresia::create([
            'cliente_id' => $otroCliente->id,
            'plan' => 'Plan Anual',
            'importe' => 250.00,
            'inicio' => today(),
            'fin' => today()->addDays(365),
            'cancelada' => false,
        ]);

        PausaMembresia::create([
            'membresia_id' => $m1->id,
            'cliente_id' => $this->cliente->id,
            'dias' => 5,
            'motivo' => 'Pausa de Carlos',
            'estado' => 'pendiente',
            'inicio_pausa' => today()->addDays(2),
            'fin_pausa_estimada' => today()->addDays(6),
        ]);

        PausaMembresia::create([
            'membresia_id' => $m2->id,
            'cliente_id' => $otroCliente->id,
            'dias' => 5,
            'motivo' => 'Pausa de Pedro',
            'estado' => 'pendiente',
            'inicio_pausa' => today()->addDays(2),
            'fin_pausa_estimada' => today()->addDays(6),
        ]);

        $response = $this->actingAs($this->secretaria, 'web')
            ->get(route('membresias.index', ['q' => 'Carlos Cliente']));

        $response->assertOk()
            ->assertViewHas('membresias', fn ($m) => $m->total() === 1 && $m->first()->cliente_id === $this->cliente->id)
            ->assertViewHas('pausasPendientes', fn ($p) => $p->count() === 1 && $p->first()->cliente_id === $this->cliente->id)
            ->assertSee('Pausa de Carlos')
            ->assertDontSee('Pausa de Pedro');
    }

    public function test_banning_client_closes_open_attendance_and_frees_live_aforo(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);

        $visita = Asistencia::create([
            'cliente_id' => $this->cliente->id,
            'fecha_hora' => now()->subMinutes(25),
            'fecha_salida' => null,
            'tipo_acceso' => 'entrada',
        ]);

        $this->actingAs($admin, 'web')
            ->patch(route('cuentas.banear', $this->cliente))
            ->assertRedirect();

        $visita->refresh();
        $this->assertNotNull($visita->fecha_salida);
        $this->assertSame($admin->id, $visita->salida_registrada_por);
    }

    public function test_client_dashboard_omits_expiry_warning_when_future_renewal_is_already_scheduled(): void
    {
        Membresia::create([
            'cliente_id' => $this->cliente->id,
            'plan' => 'Plan Actual',
            'importe' => 25.00,
            'inicio' => today()->subDays(25),
            'fin' => today()->addDays(3),
            'cancelada' => false,
        ]);

        Membresia::create([
            'cliente_id' => $this->cliente->id,
            'plan' => 'Plan Renovado',
            'importe' => 25.00,
            'inicio' => today()->addDays(4),
            'fin' => today()->addDays(34),
            'cancelada' => false,
        ]);

        $this->actingAs($this->cliente, 'cliente')
            ->get(route('cliente.dashboard'))
            ->assertOk()
            ->assertViewHas('porVencer', fn ($porVencer) => $porVencer->isEmpty());
    }
}
