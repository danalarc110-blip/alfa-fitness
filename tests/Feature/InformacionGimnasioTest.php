<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ConfiguracionGimnasio;
use App\Models\User;
use App\Support\InformacionGimnasio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformacionGimnasioTest extends TestCase
{
    use RefreshDatabase;

    private function datos(array $cambios = []): array
    {
        return array_replace(InformacionGimnasio::predeterminada(), [
            'nombre' => 'Gimnasio de prueba',
            'descripcion' => 'Entrenamiento para todas las personas.',
            'direccion' => 'Avenida de prueba 123',
            'telefono' => '+503 2222-3333',
            'correo' => 'recepcion@example.test',
        ], $cambios);
    }

    public function test_public_page_uses_defaults_without_creating_a_record(): void
    {
        $this->get(route('informacion'))->assertOk()
            ->assertSee('Centro Integral de Acondicionamiento Físico y Rendimiento')
            ->assertSee('5:00 a.m. – 10:00 p.m.');

        $this->assertDatabaseCount('configuracion_gimnasio', 0);
    }

    public function test_admin_can_save_public_information_and_edit_the_same_record(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $datos = $this->datos();
        $this->actingAs($admin)->post(route('configuracion.gimnasio'), ['gimnasio' => $datos])
            ->assertRedirect(route('configuracion').'#gimnasio')->assertSessionHasNoErrors();

        $this->assertSame($datos, InformacionGimnasio::obtener());
        $this->get(route('informacion'))->assertOk()
            ->assertSee('Gimnasio de prueba')->assertSee('Avenida de prueba 123')
            ->assertSee('+503 2222-3333')->assertSee('recepcion@example.test');

        $this->post(route('configuracion.gimnasio'), ['gimnasio' => $this->datos([
            'horarios' => 'Lunes a viernes: 6:00 a.m. a 9:00 p.m.',
            'direccion' => null, 'telefono' => null, 'correo' => null,
        ])])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('configuracion_gimnasio', 1);
        $this->get(route('informacion'))->assertOk()
            ->assertSee('6:00 a.m. a 9:00 p.m.')
            ->assertDontSee('Avenida de prueba 123')
            ->assertDontSee('recepcion@example.test');
    }

    public function test_staff_and_clients_cannot_change_public_information(): void
    {
        foreach (['Secretaria', 'Entrenador'] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol]))
                ->post(route('configuracion.gimnasio'), ['gimnasio' => $this->datos()])
                ->assertForbidden();
        }

        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => 'cliente@example.test', 'activo' => true]);
        $this->actingAs($cliente, 'cliente')->post(route('configuracion.gimnasio'), ['gimnasio' => $this->datos()])
            ->assertForbidden();
        $this->assertDatabaseCount('configuracion_gimnasio', 0);
    }

    public function test_dual_session_cannot_borrow_admin_permission(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $cliente = Cliente::create(['nombre' => 'Cliente', 'correo' => 'cliente@example.test', 'activo' => true]);
        $this->actingAs($admin, 'web')->actingAs($cliente, 'cliente')
            ->post(route('configuracion.gimnasio'), ['gimnasio' => $this->datos()])->assertForbidden();
        $this->get(route('configuracion'))->assertOk()->assertDontSee('name="gimnasio[nombre]"', false);
        $this->assertDatabaseCount('configuracion_gimnasio', 0);
    }

    public function test_admin_settings_form_shows_saved_values_and_preserves_invalid_input(): void
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        InformacionGimnasio::guardar($this->datos());
        $this->actingAs($admin)->get(route('configuracion'))->assertOk()
            ->assertSee('name="gimnasio[nombre]"', false)->assertSee('Gimnasio de prueba');

        $this->from(route('configuracion'))->post(route('configuracion.gimnasio'), [
            'gimnasio' => $this->datos(['nombre' => 'Nombre sin guardar', 'correo' => 'correo inválido']),
        ])->assertSessionHasErrors('gimnasio.correo');
        $this->get(route('configuracion'))->assertOk()->assertSee('Nombre sin guardar')
            ->assertSee('Escribe un correo de contacto válido.');
        $this->assertSame('Gimnasio de prueba', InformacionGimnasio::obtener()['nombre']);
    }

    public function test_guests_cannot_change_public_information(): void
    {
        $this->post(route('configuracion.gimnasio'), ['gimnasio' => $this->datos()])->assertRedirect(route('login'));
        $this->assertDatabaseCount('configuracion_gimnasio', 0);
    }

    public function test_invalid_values_and_privacy_settings_are_rejected_without_overwriting_saved_data(): void
    {
        InformacionGimnasio::guardar($this->datos());
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin)->from(route('configuracion'))->post(route('configuracion.gimnasio'), [
            'gimnasio' => $this->datos([
                'nombre' => str_repeat('x', 101), 'correo' => 'correo invalido',
                'telefono' => 'javascript:alert(1)', 'horarios' => '',
            ]),
        ])->assertRedirect(route('configuracion'))
            ->assertSessionHasErrors(['gimnasio.nombre', 'gimnasio.correo', 'gimnasio.telefono', 'gimnasio.horarios']);
        $this->assertSame($this->datos(), InformacionGimnasio::obtener());

        $this->post(route('configuracion.gimnasio'), ['gimnasio' => $this->datos(['privacy_demo' => false])])
            ->assertSessionHasErrors('gimnasio');
        $this->assertSame($this->datos(), InformacionGimnasio::obtener());
        $this->assertDatabaseCount('configuracion_gimnasio', 1);
    }

    public function test_public_text_is_escaped_instead_of_executing_markup(): void
    {
        $texto = '<script>alert("prueba")</script>';
        InformacionGimnasio::guardar($this->datos(['nombre' => $texto, 'descripcion' => $texto, 'instalaciones' => $texto]));

        $this->get(route('informacion'))->assertOk()->assertSee($texto)->assertDontSee($texto, false);
        $this->assertSame($texto, ConfiguracionGimnasio::findOrFail(1)->datos['nombre']);
    }

    public function test_capacity_is_a_positive_bounded_integer_and_persists_as_a_number(): void
    {
        $this->assertSame(80, InformacionGimnasio::obtener()['capacidad']);
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $this->actingAs($admin);
        foreach ([0, -1, 10001, 'dos', '1.5'] as $capacidad) {
            $this->post(route('configuracion.gimnasio'), ['gimnasio' => $this->datos(['capacidad' => $capacidad])])
                ->assertSessionHasErrors('gimnasio.capacidad');
        }
        $this->assertDatabaseCount('configuracion_gimnasio', 0);

        $this->post(route('configuracion.gimnasio'), ['gimnasio' => $this->datos(['capacidad' => '120'])])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(120, InformacionGimnasio::obtener()['capacidad']);
    }
}
