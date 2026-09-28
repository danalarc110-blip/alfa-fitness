<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AforoTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_aforo_and_peak_hours_correctly(): void
    {
        $admin = User::create([
            'name' => 'Admin Alfa',
            'email' => 'admin@test.com',
            'password' => 'Password!123',
            'rol' => 'Administrador',
            'activo' => true,
        ]);

        $cliente1 = Cliente::create(['nombre' => 'Cliente A', 'correo' => 'a@test.com', 'password' => 'Password!123', 'activo' => true]);
        $cliente2 = Cliente::create(['nombre' => 'Cliente B', 'correo' => 'b@test.com', 'password' => 'Password!123', 'activo' => true]);
        $cliente3 = Cliente::create(['nombre' => 'Cliente C', 'correo' => 'c@test.com', 'password' => 'Password!123', 'activo' => true]);

        // 2 clients inside right now (fecha_salida is null)
        Asistencia::create([
            'cliente_id' => $cliente1->id,
            'fecha_hora' => now()->subMinutes(30),
            'fecha_salida' => null,
            'tipo_acceso' => 'entrada',
        ]);
        Asistencia::create([
            'cliente_id' => $cliente2->id,
            'fecha_hora' => now()->subMinutes(15),
            'fecha_salida' => null,
            'tipo_acceso' => 'entrada',
        ]);
        // 1 client that already left
        Asistencia::create([
            'cliente_id' => $cliente3->id,
            'fecha_hora' => now()->subHours(2),
            'fecha_salida' => now()->subHour(),
            'tipo_acceso' => 'entrada',
        ]);

        $response = $this->actingAs($admin, 'web')->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Aforo en Vivo');
        $response->assertSee('2'); // 2 personas presentes
        $response->assertSee('/ 80 personas en sala');
        $response->assertSee('Distribución de Horas Pico');
        $response->assertViewHas('aforo', function ($aforo) {
            return $aforo['actual'] === 2 && $aforo['capacidad'] === 80;
        });
        $response->assertViewHas('horasPico');
    }
}
