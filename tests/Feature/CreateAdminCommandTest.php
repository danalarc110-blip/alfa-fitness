<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_create_command_creates_administrator_successfully(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Admin Principal',
            '--email' => 'admin@alfafitness.test',
            '--password' => 'Admin!2026Fuerte',
        ])
            ->assertSuccessful();

        $admin = User::where('email', 'admin@alfafitness.test')->first();
        $this->assertNotNull($admin);
        $this->assertSame('Administrador', $admin->rol);
        $this->assertSame('Admin Principal', $admin->name);
        $this->assertTrue($admin->activo);
        $this->assertTrue($admin->password_establecida);
        $this->assertTrue(Hash::check('Admin!2026Fuerte', $admin->password));
    }

    public function test_admin_create_command_fails_if_admin_already_exists(): void
    {
        User::factory()->create(['rol' => 'Administrador']);

        $this->artisan('admin:create', [
            '--name' => 'Segundo Admin',
            '--email' => 'segundo@alfafitness.test',
            '--password' => 'Admin!2026Fuerte',
        ])
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'segundo@alfafitness.test']);
    }

    public function test_admin_create_command_validates_password_strength(): void
    {
        $this->artisan('admin:create', [
            '--name' => 'Admin Debil',
            '--email' => 'debil@alfafitness.test',
            '--password' => '123456',
        ])
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'debil@alfafitness.test']);
    }
}
