<?php

namespace Tests\Feature;

use Tests\TestCase;

class PasswordRecoveryAvailabilityTest extends TestCase
{
    public function test_recovery_explains_when_the_configured_transport_cannot_deliver_messages(): void
    {
        foreach (['log', 'array'] as $transport) {
            config(['mail.default' => 'preview', 'mail.mailers.preview.transport' => $transport]);
            $this->get(route('password.request'))->assertOk()
                ->assertSee('El envío de correo todavía no está habilitado.')
                ->assertSee('Envío no disponible')->assertSee('disabled', false)
                ->assertDontSee('te enviaremos un enlace');
        }
    }

    public function test_recovery_form_remains_available_for_a_delivery_transport(): void
    {
        config(['mail.default' => 'smtp']);
        $this->get(route('password.request'))->assertOk()
            ->assertSee('Enviar enlace de recuperación')
            ->assertDontSee('Envío no disponible')->assertDontSee('disabled', false);
    }
}
