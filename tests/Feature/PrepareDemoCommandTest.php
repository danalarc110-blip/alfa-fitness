<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PrepareDemoCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        config(['privacy.demo' => true]);
    }

    public function test_prepares_working_roles_and_data_without_changing_existing_accounts(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $oldAdmin = $admin->fresh()->getAttributes();
        $existing = Cliente::create(['nombre' => 'Cliente anterior', 'correo' => 'anterior@example.test', 'password' => 'Anterior!2026Clave', 'activo' => true]);
        $oldClient = $existing->fresh()->getAttributes();

        $this->artisan('demo:prepare')->assertSuccessful();
        $this->assertSame($oldAdmin, $admin->fresh()->getAttributes());
        $this->assertSame($oldClient, $existing->fresh()->getAttributes());
        $access = json_decode(Storage::disk('local')->get('demo-access.json'), true);
        $this->assertCount(3, $access['cuentas']);
        foreach ($access['cuentas'] as $account) {
            $model = $account['tipo'] === 'cliente' ? Cliente::findOrFail($account['id']) : User::findOrFail($account['id']);
            $this->assertTrue($model->activo);
            $this->assertTrue(Hash::check($account['password'], $model->password));
            $this->assertGreaterThanOrEqual(20, strlen($account['password']));
        }
        $client = Cliente::where('correo', 'cliente.demo@alpha-fitness.example')->firstOrFail();
        $trainer = User::where('rol', 'Entrenador')->firstOrFail();
        $routine = Rutina::where('user_id', $client->id)->where('user_type', 'cliente')->firstOrFail();
        $this->assertSame($trainer->id, $routine->asignado_por_id);
        $this->assertGreaterThan(0, $routine->dias()->first()->ejercicios()->count());
        $this->assertSame('Vigente', Membresia::where('cliente_id', $client->id)->firstOrFail()->estado);
        $this->actingAs($trainer)->get(route('entrenador.clientes'))->assertOk()->assertSee($client->nombre);
        Notification::assertNothingSent();
        $this->assertDatabaseCount('pagos_membresia', 1);
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseCount('asistencias', 1);
    }

    public function test_second_run_does_not_duplicate_activity_or_reset_passwords(): void
    {
        User::factory()->create(['rol' => 'Administrador']);
        $this->artisan('demo:prepare')->assertSuccessful();
        $access = Storage::disk('local')->get('demo-access.json');
        $trainer = User::where('rol', 'Entrenador')->firstOrFail();
        $trainer->password = 'Nueva!2026ClaveManual';
        $trainer->save();
        $hash = $trainer->fresh()->password;
        $this->artisan('demo:prepare')->assertSuccessful();
        $this->assertSame($hash, $trainer->fresh()->password);
        $this->assertSame($access, Storage::disk('local')->get('demo-access.json'));
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('rutinas', 1);
        $this->assertDatabaseCount('membresias', 1);
        $this->assertDatabaseCount('pagos_membresia', 1);
        $this->assertDatabaseCount('ventas', 1);
    }

    public function test_reserved_address_collision_never_reuses_or_overwrites_an_account(): void
    {
        User::factory()->create(['rol' => 'Administrador']);
        $existing = User::factory()->create(['email' => 'entrenador.demo@alpha-fitness.example', 'rol' => 'Secretaria']);
        $before = $existing->fresh()->getAttributes();
        $this->artisan('demo:prepare')->assertFailed();
        $this->assertSame($before, $existing->fresh()->getAttributes());
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('clientes', 0);
        Storage::disk('local')->assertMissing('demo-access.json');
    }

    public function test_disabled_demo_and_missing_admin_fail_without_creating_users(): void
    {
        $this->artisan('demo:prepare')->assertFailed();
        config(['privacy.demo' => false]);
        User::factory()->create(['rol' => 'Administrador']);
        $this->artisan('demo:prepare', ['--force' => true])->assertFailed();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_failure_to_save_private_access_rolls_back_the_created_accounts_and_activity(): void
    {
        User::factory()->create(['rol' => 'Administrador']);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('exists')->once()->with('demo-access.json')->andReturnFalse();
        $disk->shouldReceive('put')->once()->andReturnFalse();
        $disk->shouldReceive('delete')->once()->with('demo-access.json')->andReturnTrue();
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->artisan('demo:prepare')->assertFailed();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('rutinas', 0);
        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('membresias', 0);
    }
}
