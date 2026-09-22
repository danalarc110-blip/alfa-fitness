<?php

namespace Tests\Feature;

use App\Models\PlanMembresia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanesMembresiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_manage_membership_plans(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);

        // Secretaria cannot create
        $this->actingAs($secretaria)->post(route('planes.store'), [
            'nombre' => 'Plan VIP',
            'precio' => 45.00,
            'duracion_dias' => 30,
        ])->assertForbidden();

        // Admin can create
        $this->actingAs($admin)->post(route('planes.store'), [
            'nombre' => 'Plan VIP',
            'precio' => 45.00,
            'duracion_dias' => 30,
            'condiciones' => 'Acceso ilimitado a todas las áreas',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('planes_membresia', [
            'nombre' => 'Plan VIP',
            'precio' => 45.00,
            'duracion_dias' => 30,
            'activo' => true,
        ]);

        $plan = PlanMembresia::where('nombre', 'Plan VIP')->firstOrFail();

        // Admin can update
        $this->actingAs($admin)->put(route('planes.update', $plan), [
            'nombre' => 'Plan VIP Plus',
            'precio' => 50.00,
            'duracion_dias' => 35,
            'activo' => 1,
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('planes_membresia', [
            'id' => $plan->id,
            'nombre' => 'Plan VIP Plus',
            'precio' => 50.00,
        ]);

        // Admin can toggle status
        $this->actingAs($admin)->patch(route('planes.toggle', $plan))->assertRedirect();
        $this->assertFalse($plan->fresh()->activo);

        $this->actingAs($admin)->patch(route('planes.toggle', $plan))->assertRedirect();
        $this->assertTrue($plan->fresh()->activo);
    }
}
