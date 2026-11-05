<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RevisionIntegralDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-05 18:30:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function perfiles(): array
    {
        return [['cliente'], ['Administrador'], ['Secretaria']];
    }

    #[DataProvider('perfiles')]
    public function test_last_calendar_day_membership_remains_visible_on_dashboard(string $perfil): void
    {
        $cliente = Cliente::create(['nombre' => 'Socio', 'correo' => 'ultimo-dia@example.test', 'activo' => true]);
        $membresia = Membresia::create([
            'cliente_id' => $cliente->id, 'plan' => 'Mensual', 'importe' => '25.00',
            'inicio' => '2026-10-07', 'fin' => '2026-11-05', 'cancelada' => false,
        ]);
        // DATE columns can contain canonical dates, including imported SQLite records.
        DB::table('membresias')->where('id', $membresia->id)->update(['inicio' => '2026-10-07', 'fin' => '2026-11-05']);

        if ($perfil === 'cliente') {
            $this->actingAs($cliente, 'cliente');
            $ruta = route('cliente.dashboard');
        } else {
            $this->actingAs(User::factory()->create(['rol' => $perfil]));
            $ruta = route('dashboard');
        }

        $respuesta = $this->get($ruta)->assertOk()
            ->assertViewHas('porVencer', fn ($membresias) => $membresias->modelKeys() === [$membresia->id]);
        if ($perfil === 'cliente') {
            $respuesta->assertViewHas('metricas', fn ($metricas) => collect($metricas)->firstWhere(0, 'Membresias vigentes')[1] === 1);
        }
    }
}
