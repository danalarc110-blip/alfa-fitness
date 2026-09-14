<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\PlanMembresia;
use App\Models\Rutina;
use App\Models\SolicitudMembresia;
use App\Models\User;
use App\Services\ImagenSegura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_identity_cannot_borrow_staff_permissions_when_both_guards_exist(): void
    {
        $staff = User::factory()->create(['rol' => 'Administrador']);
        $client = Cliente::create(['nombre' => 'Cliente', 'correo' => 'guards@example.test', 'activo' => true]);
        $this->actingAs($staff)->actingAs($client, 'cliente')->postJson(route('productos.store'), ['nombre' => 'No permitido', 'precio' => 1, 'stock' => 1])->assertForbidden();
        $this->assertDatabaseCount('productos', 0);
    }

    public function test_attendance_end_date_can_be_used_without_a_start_date(): void
    {
        $this->actingAs(User::factory()->create())->get(route('asistencia.index', ['hasta' => today()->toDateString()]))->assertOk()->assertSessionHasNoErrors();
    }

    public function test_inactive_exercises_and_malformed_search_filters_are_rejected(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff)->post(route('entrenamientos.crear'));
        $day = Rutina::firstOrFail()->dias()->firstOrFail();
        $exercise = Ejercicio::create(['nombre' => 'Archivado', 'grupo_muscular' => 'Piernas', 'activo' => false]);
        $this->postJson(route('entrenamientos.ejercicios.crear', $day), ['ejercicio_id' => $exercise->id])->assertUnprocessable();
        foreach (['/ejercicios', '/productos', '/entrenamientos/catalogo/buscar'] as $path) {
            $this->getJson($path.'?q[]=invalid')->assertUnprocessable();
        }
    }

    public function test_mail_failure_keeps_one_pending_account_with_a_retry_message(): void
    {
        Password::shouldReceive('broker')->with('users')->andReturnSelf();
        Password::shouldReceive('sendResetLink')->once()->andThrow(new \RuntimeException('SMTP failed'));
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->post(route('entrenadores.store'), ['name' => 'Invitado', 'email' => 'pending@example.test'])->assertRedirect()->assertSessionHas('status');
        $this->assertDatabaseHas('users', ['email' => 'pending@example.test', 'password_establecida' => false]);
        $this->assertSame(1, User::where('email', 'pending@example.test')->count());
    }

    public function test_password_change_rotates_remember_token_and_old_sessions_are_rejected(): void
    {
        foreach (['web', 'cliente'] as $guard) {
            $user = $guard === 'web' ? User::factory()->create(['password' => 'Anterior!2026']) : Cliente::create(['nombre' => 'Cliente', 'correo' => 'sesion@example.test', 'password' => 'Anterior!2026', 'activo' => true]);
            $user->forceFill(['remember_token' => 'old-token'])->save();
            $oldHash = auth($guard)->hashPasswordForCookie($user->password);
            $this->actingAs($user, $guard)->withSession(['password_hash_'.$guard => $oldHash])
                ->post('/configuracion/password', ['password_actual' => 'Anterior!2026', 'password' => 'NuevaClave!2026', 'password_confirmation' => 'NuevaClave!2026'])->assertSessionHasNoErrors();
            $this->assertNotSame('old-token', $user->fresh()->remember_token);
            $this->get('/configuracion')->assertOk();
            $this->withSession(['password_hash_'.$guard => $oldHash])->getJson('/configuracion')->assertUnauthorized();
            $this->assertGuest($guard);
        }
    }

    public function test_dashboard_counts_all_routines_not_just_recent_four(): void
    {
        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => 'count@example.test', 'activo' => true]);
        for ($i = 0; $i < 6; $i++) {
            Rutina::create(['user_id' => $cliente->id, 'user_type' => 'cliente', 'nombre' => 'Rutina '.$i, 'objetivo' => 'Fuerza', 'nivel' => 'Intermedio', 'dias_por_semana' => 1]);
        }
        $this->actingAs($cliente, 'cliente')->get('/cliente/dashboard')->assertOk()->assertViewHas('metricas', fn ($m) => $m[0] === ['Mis rutinas', 6]);
    }

    public function test_failed_image_processing_does_not_create_a_partial_product(): void
    {
        $this->mock(ImagenSegura::class)->shouldReceive('guardar')->once()->andThrow(new \RuntimeException('Disk failed'));
        $this->actingAs(User::factory()->create())->post(route('productos.store'), ['nombre' => 'Producto', 'precio' => 2, 'stock' => 1, 'imagen' => UploadedFile::fake()->image('foto.jpg')])->assertServerError();
        $this->assertDatabaseCount('productos', 0);
    }

    public function test_inactive_client_cannot_receive_membership_or_payment(): void
    {
        $cliente = Cliente::create(['nombre' => 'Inactivo', 'correo' => 'inactivo@example.test', 'activo' => false]);
        $plan = PlanMembresia::firstOrFail();
        $solicitud = SolicitudMembresia::create(['cliente_id' => $cliente->id, 'plan_id' => $plan->id, 'plan_nombre' => $plan->nombre, 'precio_acordado' => $plan->precio, 'duracion_dias' => $plan->duracion_dias]);
        $this->actingAs(User::factory()->create())->patchJson(route('membresias.activar', $solicitud), ['importe' => 25])->assertUnprocessable();
        $this->assertDatabaseCount('pagos_membresia', 0);
        $this->assertSame('pendiente', $solicitud->fresh()->estado);
    }

    public function test_private_responses_are_not_cacheable_and_have_security_headers(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/configuracion');
        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
