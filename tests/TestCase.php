<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function reconocerPrivacidad(): void
    {
        $this->post(route('privacidad.continuar'), [
            'privacy_read' => '1',
            'privacy_version' => config('privacy.version'),
        ])->assertRedirect(route('login'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
