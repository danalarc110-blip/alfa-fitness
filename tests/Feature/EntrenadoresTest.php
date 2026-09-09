<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EntrenadoresTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_linked_trainer_account_without_known_password(): void
    {
        Notification::fake();
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']))->post(route('entrenadores.store'), ['name' => 'X', 'email' => 'x@example.com'])->assertForbidden();
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->post(route('entrenadores.store'), ['name' => 'Entrenador Nuevo', 'email' => 'nuevo@example.com'])->assertSessionHasNoErrors();
        $trainer = User::where('email', 'nuevo@example.com')->firstOrFail();
        $this->assertSame('Entrenador', $trainer->rol); $this->assertFalse($trainer->password_establecida);
        Notification::assertSentTo($trainer, \Illuminate\Auth\Notifications\ResetPassword::class);
        $this->post(route('login.submit'), ['email' => $trainer->email, 'password' => 'cualquier-cosa'])->assertSessionHasErrors('email');
        $token = Password::broker('users')->createToken($trainer);
        $this->post(route('password.update'), ['token' => $token, 'email' => $trainer->email, 'password' => 'Entrenador!2026', 'password_confirmation' => 'Entrenador!2026'])->assertRedirect(route('login'));
        $this->assertTrue($trainer->fresh()->password_establecida);
        $this->assertTrue(Hash::check('Entrenador!2026', $trainer->fresh()->password));
        $this->actingAs($admin)->put(route('entrenadores.update', $trainer), ['name' => 'Entrenador Editado', 'email' => $trainer->email, 'activo' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $trainer->id, 'name' => 'Entrenador Editado', 'activo' => false]);
    }
}
