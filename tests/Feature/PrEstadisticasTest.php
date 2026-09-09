<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrEstadisticasTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_pr_statistics_is_integrated_into_private_progress(): void
    {
        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => 'cliente@example.com', 'password' => 'Password!123', 'activo' => true]);
        $this->actingAs($cliente, 'cliente')->get(route('estadisticas.index'))->assertRedirect(route('progreso.index'));
        foreach (['Administrador', 'Secretaria', 'Entrenador'] as $rol) $this->actingAs(User::factory()->create(['rol' => $rol]))->get(route('estadisticas.index'))->assertForbidden();
    }
}
