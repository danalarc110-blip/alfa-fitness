<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_rankings_no_longer_exposes_other_clients_records(): void
    {
        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => 'cliente@example.com', 'password' => 'Password!123', 'activo' => true]);
        $this->actingAs($cliente, 'cliente')->get(route('rankings.index'))->assertRedirect(route('progreso.index'));
        $this->post(route('cliente.logout'));
        foreach (['Administrador', 'Secretaria', 'Entrenador'] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol]))->get(route('rankings.index'))->assertForbidden();
        }
    }
}
