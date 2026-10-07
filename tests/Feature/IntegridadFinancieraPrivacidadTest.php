<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\PausaMembresia;
use App\Models\PlanMembresia;
use App\Models\Producto;
use App\Models\Rutina;
use App\Models\SesionEntrenamiento;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IntegridadFinancieraPrivacidadTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(): Cliente
    {
        return Cliente::create(['nombre' => 'Socio', 'correo' => Str::uuid().'@example.test', 'password' => 'Password!123', 'activo' => true]);
    }

    private function rutina(User|Cliente $propietario): Rutina
    {
        return Rutina::create([
            'user_id' => $propietario->id,
            'user_type' => $propietario instanceof Cliente ? 'cliente' : 'web',
            'nombre' => 'Fuerza', 'objetivo' => 'Salud', 'nivel' => 'Intermedio', 'dias_por_semana' => 1,
        ]);
    }

    public function test_coach_cannot_clone_another_users_routine(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        $owner = $this->cliente();
        $destino = $this->cliente();
        $rutina = $this->rutina($owner);

        $this->actingAs($coach)->post(route('entrenamientos.asignar', $rutina), ['cliente_id' => $destino->id])->assertForbidden();
        $this->assertDatabaseCount('rutinas', 1);
    }

    public function test_reception_cannot_assign_own_routine(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = $this->cliente();
        $rutina = $this->rutina($secretaria);

        $this->actingAs($secretaria)->post(route('entrenamientos.asignar', $rutina), ['cliente_id' => $cliente->id])->assertForbidden();
        $this->assertDatabaseCount('rutinas', 1);
    }

    public function test_assignment_uses_stable_coach_id_after_rename_and_rejects_name_collision(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador', 'name' => 'Entrenador Original']);
        $intruso = User::factory()->create(['rol' => 'Entrenador', 'name' => 'Entrenador Original']);
        $cliente = $this->cliente();
        $rutina = $this->rutina($coach);

        $this->actingAs($coach)->post(route('entrenamientos.asignar', $rutina), ['cliente_id' => $cliente->id])->assertRedirect();
        $this->assertDatabaseHas('rutinas', ['user_id' => $cliente->id, 'user_type' => 'cliente', 'asignado_por_id' => $coach->id]);
        $coach->update(['name' => 'Nombre Actualizado']);
        $this->actingAs($coach)->get(route('entrenador.cliente.progreso', $cliente))->assertOk();
        $this->actingAs($intruso)->get(route('entrenador.cliente.progreso', $cliente))->assertForbidden();
    }

    public function test_unverified_legacy_assignment_name_does_not_grant_access(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador', 'name' => 'Coach']);
        $cliente = $this->cliente();
        $this->rutina($cliente)->update(['asignado_por' => $coach->name]);

        $this->actingAs($coach)->get(route('entrenador.cliente.progreso', $cliente))->assertForbidden();
    }

    public function test_retried_workout_uuid_persists_exactly_one_session(): void
    {
        $cliente = $this->cliente();
        $rutina = $this->rutina($cliente);
        $payload = ['sesion_uuid' => (string) Str::uuid(), 'duracion_segundos' => 300, 'series_completadas' => 2, 'total_series' => 3];

        $primera = $this->actingAs($cliente, 'cliente')->postJson(route('entrenamientos.finalizar', $rutina), $payload)->assertOk();
        $this->postJson(route('entrenamientos.finalizar', $rutina), $payload)->assertOk()->assertJsonPath('sesion.id', $primera->json('sesion.id'));
        $this->assertDatabaseCount('sesiones_entrenamiento', 1);
    }

    public function test_workout_rejects_foreign_day_and_impossible_series_count(): void
    {
        $cliente = $this->cliente();
        $rutina = $this->rutina($cliente);
        $otra = $this->rutina($this->cliente());
        $dia = $otra->dias()->create(['orden' => 1, 'titulo' => 'Privado']);

        $this->actingAs($cliente, 'cliente')->postJson(route('entrenamientos.finalizar', $rutina), ['dia_id' => $dia->id])->assertUnprocessable()->assertJsonValidationErrors('dia_id');
        $this->postJson(route('entrenamientos.finalizar', $rutina), ['series_completadas' => 4, 'total_series' => 3])->assertUnprocessable()->assertJsonValidationErrors('series_completadas');
        $this->assertDatabaseCount('sesiones_entrenamiento', 0);
    }

    public function test_workout_uuid_cannot_reveal_another_users_session(): void
    {
        $cliente = $this->cliente();
        $otro = $this->cliente();
        $rutina = $this->rutina($cliente);
        $otraRutina = $this->rutina($otro);
        $uuid = (string) Str::uuid();
        SesionEntrenamiento::create(['sesion_uuid' => $uuid, 'user_id' => $otro->id, 'user_type' => 'cliente', 'rutina_id' => $otraRutina->id, 'rutina_nombre' => 'Privada']);

        $this->actingAs($cliente, 'cliente')->postJson(route('entrenamientos.finalizar', $rutina), ['sesion_uuid' => $uuid])->assertForbidden();
        $this->assertDatabaseCount('sesiones_entrenamiento', 1);
    }

    public function test_sale_calculates_repeated_fractional_prices_exactly(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $producto = Producto::create(['nombre' => 'Agua', 'precio' => '0.29', 'stock' => 1000, 'activo' => true]);

        $this->actingAs($secretaria)->post(route('ventas.store'), ['metodo_pago' => 'Efectivo', 'items' => [['producto_id' => $producto->id, 'cantidad' => 999]]])->assertRedirect()->assertSessionHasNoErrors();
        $venta = Venta::firstOrFail();
        $this->assertSame('289.71', $venta->total);
        $this->assertSame('289.71', $venta->detalles()->firstOrFail()->subtotal);
        $this->assertSame(1, $producto->fresh()->stock);
    }

    public function test_sale_over_database_precision_rolls_back_stock_and_records(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $producto = Producto::create(['nombre' => 'Equipo', 'precio' => '999999.00', 'stock' => 999, 'activo' => true]);

        $this->actingAs($secretaria)->post(route('ventas.store'), ['metodo_pago' => 'Efectivo', 'items' => [['producto_id' => $producto->id, 'cantidad' => 999]]])->assertSessionHasErrors('items');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(999, $producto->fresh()->stock);
    }

    public function test_product_with_sale_cannot_destroy_historical_details(): void
    {
        $user = User::factory()->create(['rol' => 'Secretaria']);
        $producto = Producto::create(['nombre' => 'Histórico', 'precio' => '10.00', 'stock' => 1, 'activo' => true]);
        $venta = Venta::create(['user_id' => $user->id, 'total' => '10.00', 'metodo_pago' => 'Efectivo']);
        $venta->detalles()->create(['producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => '10.00', 'subtotal' => '10.00']);

        try {
            $producto->delete();
            $this->fail('La FK debe impedir eliminar un producto vendido.');
        } catch (QueryException) {
            $this->assertDatabaseCount('detalle_ventas', 1);
            $this->assertDatabaseHas('productos', ['id' => $producto->id]);
        }
    }

    public function test_seller_with_sales_cannot_delete_historical_sales(): void
    {
        $user = User::factory()->create(['rol' => 'Secretaria']);
        Venta::create(['user_id' => $user->id, 'total' => '10.00', 'metodo_pago' => 'Efectivo']);

        try {
            $user->delete();
            $this->fail('La FK debe impedir destruir ventas al borrar al vendedor.');
        } catch (QueryException) {
            $this->assertDatabaseCount('ventas', 1);
            $this->assertDatabaseHas('users', ['id' => $user->id]);
        }
    }

    public function test_plan_rejects_duplicate_name_and_fractional_cent(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $plan = PlanMembresia::firstOrFail();
        $cantidad = PlanMembresia::count();

        $this->actingAs($admin)->post(route('planes.store'), ['nombre' => $plan->nombre, 'precio' => '12.345', 'duracion_dias' => 30])->assertSessionHasErrors(['nombre', 'precio']);
        $this->assertDatabaseCount('planes_membresia', $cantidad);
        $this->put(route('planes.update', $plan), ['nombre' => $plan->nombre, 'precio' => '12.34', 'duracion_dias' => 30, 'activo' => true])->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_inactive_client_pause_cannot_be_approved(): void
    {
        $cliente = $this->cliente();
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $membresia = Membresia::create(['cliente_id' => $cliente->id, 'plan' => 'Mensual', 'importe' => '25.00', 'inicio' => today(), 'fin' => today()->addDays(30)]);
        $pausa = PausaMembresia::create(['cliente_id' => $cliente->id, 'membresia_id' => $membresia->id, 'dias' => 3, 'motivo' => 'Viaje', 'estado' => 'pendiente', 'inicio_pausa' => today(), 'fin_pausa_estimada' => today()->addDays(2)]);
        $cliente->update(['activo' => false]);

        $this->actingAs($secretaria)->patch(route('membresias.pausa.aprobar', $pausa))->assertSessionHasErrors('pausa');
        $this->assertSame('pendiente', $pausa->fresh()->estado);
    }
}
