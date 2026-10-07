<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GestionPersonalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_secretaria_and_send_invitation(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['rol' => 'Administrador']);

        $response = $this->actingAs($admin)->post(route('entrenadores.store'), [
            'name' => 'Lucía Recepción',
            'email' => 'lucia@alfafitness.test',
            'rol' => 'Secretaria',
        ]);

        $response->assertSessionHasNoErrors();
        $secretaria = User::where('email', 'lucia@alfafitness.test')->first();
        $this->assertNotNull($secretaria);
        $this->assertSame('Secretaria', $secretaria->rol);
        $this->assertFalse($secretaria->password_establecida);
        Notification::assertSentTo($secretaria, ResetPassword::class);
    }

    public function test_admin_can_filter_staff_by_role(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        User::factory()->create(['name' => 'Entrenador Juan', 'rol' => 'Entrenador', 'activo' => true]);
        User::factory()->create(['name' => 'Secretaria Maria', 'rol' => 'Secretaria', 'activo' => true]);

        // Filter secretarias only
        $response = $this->actingAs($admin)->get(route('entrenadores.index', ['rol' => 'Secretaria']));
        $response->assertOk()
            ->assertSee('Secretaria Maria')
            ->assertDontSee('Entrenador Juan');

        // Filter trainers only
        $response = $this->actingAs($admin)->get(route('entrenadores.index', ['rol' => 'Entrenador']));
        $response->assertOk()
            ->assertSee('Entrenador Juan')
            ->assertDontSee('Secretaria Maria');
    }

    public function test_secretaria_or_trainer_cannot_create_staff(): void
    {
        $secretaria = User::factory()->create(['rol' => 'Secretaria']);
        $entrenador = User::factory()->create(['rol' => 'Entrenador']);

        $this->actingAs($secretaria)->post(route('entrenadores.store'), [
            'name' => 'Ilegal',
            'email' => 'ilegal@test.com',
            'rol' => 'Secretaria',
        ])->assertForbidden();

        $this->actingAs($entrenador)->post(route('entrenadores.store'), [
            'name' => 'Ilegal 2',
            'email' => 'ilegal2@test.com',
            'rol' => 'Entrenador',
        ])->assertForbidden();
    }
}
