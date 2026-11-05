<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\SolicitudMembresia;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormularioEntradaTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_product_arrays_return_the_editable_form_without_creating_a_product(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->from(route('productos.index'))->followingRedirects()
            ->post(route('productos.store'), [
                'nombre' => ['unexpected'], 'precio' => ['unexpected'],
                'stock' => ['unexpected'], 'categoria' => ['unexpected'],
            ])->assertOk()->assertSee('Revisa los siguientes datos:');
        $this->assertDatabaseCount('productos', 0);
    }

    public function test_invalid_staff_arrays_return_the_form_without_creating_an_account(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->from(route('entrenadores.index'))->followingRedirects()
            ->post(route('entrenadores.store'), [
                'name' => ['unexpected'], 'email' => ['unexpected'], 'rol' => 'Entrenador',
            ])->assertOk()->assertSee('Revisa los siguientes datos:');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_invalid_progress_arrays_return_the_form_without_creating_a_record(): void
    {
        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => 'progreso.seguro@example.test', 'activo' => true]);
        $ejercicio = Ejercicio::create(['nombre' => 'Sentadilla', 'grupo_muscular' => 'Piernas', 'activo' => true]);
        $this->actingAs($cliente, 'cliente')->from(route('progreso.index'))->followingRedirects()
            ->post(route('progreso.store'), [
                'ejercicio_id' => $ejercicio->id, 'peso_kg' => ['unexpected'],
                'repeticiones' => ['unexpected'], 'notas' => ['unexpected'],
            ])->assertOk()->assertSee('Revisa los siguientes datos:');
        $this->assertDatabaseCount('personal_records', 0);
    }

    public function test_invalid_receipt_recipient_array_returns_the_receipt_with_a_safe_default(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => 'recibo.seguro@example.test', 'activo' => true]);
        $venta = Venta::create(['user_id' => $staff->id, 'cliente_id' => $cliente->id, 'total' => '25.00', 'metodo_pago' => 'Efectivo']);
        $this->actingAs($staff)->from(route('ventas.comprobante', $venta))->followingRedirects()
            ->post(route('ventas.enviar-correo', $venta), ['correo' => ['unexpected']])
            ->assertOk()->assertSee('Revisa los siguientes datos:')
            ->assertSee('value="'.$cliente->correo.'"', false);
        $this->assertDatabaseCount('ventas', 1);
    }

    public function test_invalid_payment_arrays_return_the_membership_form_without_registering_a_payment(): void
    {
        $staff = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => 'membresia.segura@example.test', 'activo' => true]);
        $solicitud = SolicitudMembresia::create([
            'cliente_id' => $cliente->id, 'plan_nombre' => 'Mensual',
            'precio_acordado' => '25.00', 'duracion_dias' => 30, 'estado' => 'pendiente',
        ]);
        $this->actingAs($staff)->from(route('membresias.index'))->followingRedirects()
            ->patch(route('membresias.activar', $solicitud), ['importe' => ['unexpected'], 'referencia' => ['unexpected']])
            ->assertOk()->assertSee('Revisa los siguientes datos:');
        $this->assertSame('pendiente', $solicitud->fresh()->estado);
        $this->assertDatabaseCount('membresias', 0);
        $this->assertDatabaseCount('pagos_membresia', 0);
    }

    public function test_valid_previous_text_and_zero_values_are_preserved_and_escaped(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $nombre = 'Mancuerna "<ligera>"';
        $this->actingAs($admin)->withSession(['_old_input' => [
            'nombre' => $nombre, 'precio' => '0.00', 'stock' => 0, 'categoria' => 'Pesos & accesorios',
        ]])->get(route('productos.index'))->assertOk()
            ->assertSee($nombre)->assertSee('Pesos & accesorios')
            ->assertSee('value="0.00"', false)->assertSee('value="0"', false)
            ->assertDontSee('value="'.$nombre.'"', false);
    }
}
