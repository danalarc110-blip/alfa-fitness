<?php

namespace Tests\Feature;

use App\Mail\ComprobanteVentaMail;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ComprobanteVentaTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_sale_receipt(): void
    {
        $secretary = User::create([
            'name' => 'Secretaria Alfa',
            'email' => 'secre@test.com',
            'password' => 'Password!123',
            'rol' => 'Secretaria',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Carlos Cliente',
            'correo' => 'carlos@test.com',
            'password' => 'Password!123',
            'activo' => true,
        ]);

        $producto = Producto::create([
            'nombre' => 'Proteina Whey 2lb',
            'precio' => 45.00,
            'stock' => 10,
            'activo' => true,
        ]);

        $venta = Venta::create([
            'user_id' => $secretary->id,
            'cliente_id' => $cliente->id,
            'total' => 45.00,
        ]);

        $venta->detalles()->create([
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 45.00,
            'subtotal' => 45.00,
        ]);

        $response = $this->actingAs($secretary, 'web')->get(route('ventas.comprobante', $venta));
        $response->assertOk();
        $response->assertSee('Comprobante de Venta');
        $response->assertSee('Proteina Whey 2lb');
        $response->assertSee('$45.00');
        $response->assertSee('Enviar por Correo');
    }

    public function test_staff_can_send_receipt_by_email(): void
    {
        Mail::fake();

        $secretary = User::create([
            'name' => 'Secretaria Alfa',
            'email' => 'secre@test.com',
            'password' => 'Password!123',
            'rol' => 'Secretaria',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Carlos Cliente',
            'correo' => 'carlos@test.com',
            'password' => 'Password!123',
            'activo' => true,
        ]);

        $venta = Venta::create([
            'user_id' => $secretary->id,
            'cliente_id' => $cliente->id,
            'total' => 30.00,
        ]);

        $response = $this->actingAs($secretary, 'web')->post(route('ventas.enviar-correo', $venta), [
            'correo' => 'cliente_recibo@test.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        Mail::assertSent(ComprobanteVentaMail::class, function ($mail) {
            return $mail->hasTo('cliente_recibo@test.com');
        });
    }

    public function test_unauthenticated_or_client_cannot_access_receipt_routes(): void
    {
        $admin = User::create([
            'name' => 'Admin Alfa',
            'email' => 'admin@test.com',
            'password' => 'Password!123',
            'rol' => 'Administrador',
            'activo' => true,
        ]);

        $venta = Venta::create([
            'user_id' => $admin->id,
            'total' => 20.00,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Socio Regular',
            'correo' => 'socio@test.com',
            'password' => 'Password!123',
            'activo' => true,
        ]);

        // Guest is redirected
        $this->get(route('ventas.comprobante', $venta))->assertRedirect(route('login'));

        // Client cannot access staff sales receipt
        $this->actingAs($cliente, 'cliente')->get(route('ventas.comprobante', $venta))->assertForbidden();
    }
}
