<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenciaAvanzadaTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_export_csv_and_close_orphan_visits(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $cliente = Cliente::create(['nombre' => 'Carlos Gimnasio', 'correo' => 'carlos@example.test', 'activo' => true]);

        // Old open visit (15 hours ago)
        $visitaAntigua = Asistencia::create([
            'cliente_id' => $cliente->id,
            'registrado_por' => $secretaria->id,
            'fecha_hora' => now()->subHours(15),
            'fecha_salida' => null,
            'tipo_acceso' => 'QR',
        ]);

        // Secretary exports CSV
        $response = $this->actingAs($secretaria)->get(route('asistencia.exportar'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

        // Secretary closes orphan visits
        $this->actingAs($secretaria)->post(route('asistencia.cerrar-huerfanas'))->assertRedirect()->assertSessionHas('status');

        $visitaActualizada = $visitaAntigua->fresh();
        $this->assertNotNull($visitaActualizada->fecha_salida);
        $this->assertSame($secretaria->id, $visitaActualizada->salida_registrada_por);
    }
}
