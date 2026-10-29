<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RenderProxyTest extends TestCase
{
    private ?string $originalRender;

    protected function setUp(): void
    {
        $this->originalRender = Env::getRepository()->get('RENDER');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $repository = Env::getRepository();
        $this->originalRender === null ? $repository->clear('RENDER') : $repository->set('RENDER', $this->originalRender);
        TrustProxies::flushState();
        Request::setTrustedProxies([], 0);
        parent::tearDown();
    }

    private function renderRequest(bool $render): TestResponse
    {
        Env::getRepository()->set('RENDER', $render ? 'true' : 'false');
        TrustProxies::flushState();
        $this->refreshApplication();
        $this->withoutVite();
        config(['session.secure' => true]);

        return $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-Host' => 'untrusted.example',
            ])->get('http://alpha-fitness.onrender.com/login');
    }

    public function test_render_https_proxy_produces_secure_forms_without_trusting_forwarded_host(): void
    {
        $response = $this->renderRequest(true);
        $response->assertOk()->assertSee('action="https://alpha-fitness.onrender.com/privacidad/continuar"', false)
            ->assertDontSee('untrusted.example');
        $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isSecure());
    }

    public function test_local_requests_do_not_trust_an_unsolicited_forwarded_protocol(): void
    {
        $this->renderRequest(false)->assertOk()
            ->assertSee('action="http://alpha-fitness.onrender.com/privacidad/continuar"', false)
            ->assertDontSee('untrusted.example');
    }
}
