<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\EntrenadorCalificacion;
use App\Models\Rutina;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class EntrenadoresTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_applies_zero_search_and_trims_spaces(): void
    {
        User::factory()->create(['name' => 'Coach 10', 'rol' => 'Entrenador']);
        User::factory()->create(['name' => 'Coach excluido por búsqueda', 'rol' => 'Entrenador']);
        $cliente = Cliente::create(['nombre' => 'Cliente de búsqueda', 'correo' => 'busqueda-zero@example.test', 'activo' => true]);
        $this->actingAs($cliente, 'cliente')->get(route('entrenadores.index', ['q' => ' 0 ']))
            ->assertOk()->assertSee('Coach 10')->assertDontSee('Coach excluido por búsqueda')
            ->assertViewHas('busqueda', '0');
    }

    public function test_admin_creates_linked_trainer_account_without_known_password(): void
    {
        Notification::fake();
        $this->actingAs(User::factory()->create(['rol' => 'Secretaria']))->post(route('entrenadores.store'), ['name' => 'X', 'email' => 'x@example.com'])->assertForbidden();
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->post(route('entrenadores.store'), ['name' => 'Entrenador Nuevo', 'email' => 'nuevo@example.com'])->assertSessionHasNoErrors();
        $trainer = User::where('email', 'nuevo@example.com')->firstOrFail();
        $this->assertSame('Entrenador', $trainer->rol);
        $this->assertFalse($trainer->password_establecida);
        Notification::assertSentTo($trainer, ResetPassword::class);
        $this->reconocerPrivacidad();
        $this->post(route('login.submit'), ['email' => $trainer->email, 'password' => 'cualquier-cosa'])->assertSessionHasErrors('email');
        $token = Password::broker('users')->createToken($trainer);
        $this->post(route('password.update'), ['token' => $token, 'email' => $trainer->email, 'password' => 'Entrenador!2026', 'password_confirmation' => 'Entrenador!2026'])->assertRedirect(route('login'));
        $this->assertTrue($trainer->fresh()->password_establecida);
        $this->assertTrue(Hash::check('Entrenador!2026', $trainer->fresh()->password));
        $this->actingAs($admin)->put(route('entrenadores.update', $trainer), ['name' => 'Entrenador Editado', 'email' => $trainer->email, 'activo' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $trainer->id, 'name' => 'Entrenador Editado', 'activo' => false]);
    }

    public function test_directory_searches_trainers_and_hides_inactive_accounts_from_members(): void
    {
        $visible = User::factory()->create(['name' => 'Ana Fuerza', 'email' => 'ana@example.test', 'rol' => 'Entrenador', 'activo' => true]);
        User::factory()->create(['name' => 'Carlos Cardio', 'email' => 'carlos@example.test', 'rol' => 'Entrenador', 'activo' => true]);
        User::factory()->create(['name' => 'Ana Inactiva', 'email' => 'inactiva@example.test', 'rol' => 'Entrenador', 'activo' => false]);
        $cliente = Cliente::create(['nombre' => 'Miembro', 'correo' => 'miembro@example.test', 'password' => 'Fuerte!2026Clave', 'activo' => true]);

        $this->actingAs($cliente, 'cliente')
            ->get(route('entrenadores.index', ['q' => 'Ana']))
            ->assertOk()
            ->assertSee($visible->name)
            ->assertDontSee('Carlos Cardio')
            ->assertDontSee('Ana Inactiva')
            ->assertDontSee('ana@example.test');

        $this->post(route('cliente.logout'))->assertRedirect(route('login'));
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin, 'web')
            ->get(route('entrenadores.index', ['q' => 'inactiva@example.test']))
            ->assertOk()
            ->assertSee('Ana Inactiva');
    }

    private function clienteValorador(string $correo = 'valorador@example.test'): Cliente
    {
        return Cliente::create(['nombre' => 'Cliente Valorador', 'correo' => $correo, 'password' => 'Clave!2026Segura', 'activo' => true]);
    }

    private function vincular(Cliente $cliente, User $entrenador, array $attrs = []): Rutina
    {
        return Rutina::create(array_merge([
            'user_type' => 'cliente', 'user_id' => $cliente->id, 'asignado_por_id' => $entrenador->id,
            'nombre' => 'Rutina asignada', 'objetivo' => 'Fuerza', 'nivel' => 'Intermedio', 'dias_por_semana' => 3,
        ], $attrs));
    }

    public function test_linked_client_updates_a_single_vote_and_directory_shows_average_and_count(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador', 'name' => 'Ana Entrenadora']);
        $cliente = $this->clienteValorador();
        $otro = $this->clienteValorador('otro@example.test');
        $this->vincular($cliente, $coach);
        $this->vincular($cliente, $coach, ['nombre' => 'Segunda rutina']);
        $this->vincular($otro, $coach);

        $this->actingAs($cliente, 'cliente')->get(route('entrenadores.index'))
            ->assertOk()->assertSee('Sin calificaciones')->assertSee('Califica a tu entrenador');
        $this->postJson(route('entrenadores.calificar', $coach), ['estrellas' => 2])
            ->assertOk()->assertJson(['promedio' => 2, 'total_votos' => 1]);
        $firstId = EntrenadorCalificacion::firstOrFail()->id;
        $this->postJson(route('entrenadores.calificar', $coach), ['estrellas' => 5])
            ->assertOk()->assertJson(['promedio' => 5, 'total_votos' => 1]);
        $this->postJson(route('entrenadores.calificar', $coach), ['estrellas' => 5])->assertOk();
        $this->assertDatabaseCount('entrenador_calificaciones', 1);
        $this->assertSame($firstId, EntrenadorCalificacion::firstOrFail()->id);

        $this->post(route('cliente.logout'))->assertRedirect(route('login'));
        $this->actingAs($otro, 'cliente')->postJson(route('entrenadores.calificar', $coach), ['estrellas' => 3])
            ->assertOk()->assertJson(['promedio' => 4, 'total_votos' => 2]);
        $this->post(route('cliente.logout'))->assertRedirect(route('login'));
        $this->actingAs($cliente, 'cliente')->get(route('entrenadores.index'))
            ->assertOk()->assertSee('4.0 / 5')->assertSee('2 valoraciones')->assertSee('Tu valoración: 5 de 5 estrellas.');
        $this->assertDatabaseCount('ejercicio_calificaciones', 0);
    }

    public function test_rating_uses_authenticated_client_and_supports_native_form_submission(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->clienteValorador();
        $otro = $this->clienteValorador('suplantado@example.test');
        $this->vincular($cliente, $coach);
        $this->actingAs($cliente, 'cliente')->from(route('entrenadores.index'))
            ->post(route('entrenadores.calificar', $coach), ['estrellas' => 4, 'cliente_id' => $otro->id])
            ->assertRedirect(route('entrenadores.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('entrenador_calificaciones', ['cliente_id' => $cliente->id, 'entrenador_id' => $coach->id, 'estrellas' => 4]);
        $this->assertDatabaseMissing('entrenador_calificaciones', ['cliente_id' => $otro->id]);
    }

    public function test_rating_rejects_invalid_values_without_changing_previous_vote(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->clienteValorador();
        $this->vincular($cliente, $coach);
        $this->actingAs($cliente, 'cliente')->postJson(route('entrenadores.calificar', $coach), ['estrellas' => 4])->assertOk();
        foreach ([0, 6, -1, 2.5, 'cinco', null, [5]] as $valor) {
            $this->postJson(route('entrenadores.calificar', $coach), ['estrellas' => $valor])
                ->assertUnprocessable()->assertJsonValidationErrors('estrellas');
        }
        $this->assertDatabaseCount('entrenador_calificaciones', 1);
        $this->assertDatabaseHas('entrenador_calificaciones', ['estrellas' => 4]);
    }

    public function test_only_routines_owned_by_client_establish_rating_authorization(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        $otroCoach = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->clienteValorador();
        $this->vincular($cliente, $coach, ['user_type' => 'web']);
        $this->vincular($cliente, $otroCoach);
        $this->actingAs($cliente, 'cliente')->postJson(route('entrenadores.calificar', $coach), ['estrellas' => 5])->assertForbidden();
        $this->assertDatabaseCount('entrenador_calificaciones', 0);
        $this->get(route('entrenadores.index'))->assertOk()
            ->assertSee('Podrás valorar a este entrenador cuando te asigne una rutina.');
    }

    public function test_inactive_trainers_and_other_staff_cannot_receive_client_ratings(): void
    {
        $cliente = $this->clienteValorador();
        foreach ([['rol' => 'Entrenador', 'activo' => false], ['rol' => 'Secretaria', 'activo' => true], ['rol' => 'Administrador', 'activo' => true]] as $attrs) {
            $personal = User::factory()->create($attrs);
            $this->vincular($cliente, $personal);
            $this->actingAs($cliente, 'cliente')->postJson(route('entrenadores.calificar', $personal), ['estrellas' => 5])->assertNotFound();
        }
        $this->assertDatabaseCount('entrenador_calificaciones', 0);
    }

    public function test_staff_cannot_vote_or_modify_existing_client_ratings(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->clienteValorador();
        $this->vincular($cliente, $coach);
        EntrenadorCalificacion::create(['entrenador_id' => $coach->id, 'cliente_id' => $cliente->id, 'estrellas' => 2]);
        foreach (['Entrenador', 'Secretaria', 'Administrador'] as $rol) {
            $personal = $rol === 'Entrenador' ? $coach : User::factory()->create(['rol' => $rol]);
            $this->actingAs($personal, 'web')->postJson(route('entrenadores.calificar', $coach), ['estrellas' => 5, 'cliente_id' => $cliente->id])->assertForbidden();
            $this->get(route('entrenadores.index'))->assertOk()->assertDontSee('Califica a tu entrenador');
        }
        $this->assertDatabaseCount('entrenador_calificaciones', 1);
        $this->assertDatabaseHas('entrenador_calificaciones', ['estrellas' => 2]);
    }

    public function test_simultaneous_guards_keep_client_directory_permissions(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $coach = User::factory()->create(['rol' => 'Entrenador', 'email' => 'privado@example.test']);
        User::factory()->create(['rol' => 'Entrenador', 'name' => 'Entrenador Inactivo', 'activo' => false]);
        User::factory()->create(['rol' => 'Secretaria', 'name' => 'Secretaria Privada']);
        $cliente = $this->clienteValorador();
        $this->actingAs($admin, 'web')->actingAs($cliente, 'cliente')->get(route('entrenadores.index'))
            ->assertOk()->assertDontSee('privado@example.test')->assertDontSee('Entrenador Inactivo')
            ->assertDontSee('Secretaria Privada')->assertDontSee('Editar personal');
        $this->post(route('entrenadores.store'), ['name' => 'Nuevo', 'email' => 'nuevo@example.test'])->assertForbidden();
        $this->put(route('entrenadores.update', $coach), ['name' => 'Modificado', 'email' => $coach->email, 'activo' => 1])->assertForbidden();
        $this->get(route('entrenador.clientes'))->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'nuevo@example.test']);
    }

    public function test_removed_assignment_revokes_rating_permission(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->clienteValorador();
        $rutina = $this->vincular($cliente, $coach);
        $this->actingAs($cliente, 'cliente')->postJson(route('entrenadores.calificar', $coach), ['estrellas' => 3])->assertOk();
        $rutina->delete();
        $this->postJson(route('entrenadores.calificar', $coach), ['estrellas' => 5])->assertForbidden();
        $this->assertDatabaseHas('entrenador_calificaciones', ['estrellas' => 3]);
    }

    public function test_database_enforces_one_rating_per_client_and_trainer(): void
    {
        $coach = User::factory()->create(['rol' => 'Entrenador']);
        $cliente = $this->clienteValorador();
        EntrenadorCalificacion::create(['entrenador_id' => $coach->id, 'cliente_id' => $cliente->id, 'estrellas' => 4]);

        $this->expectException(QueryException::class);
        EntrenadorCalificacion::create(['entrenador_id' => $coach->id, 'cliente_id' => $cliente->id, 'estrellas' => 2]);
    }
}
