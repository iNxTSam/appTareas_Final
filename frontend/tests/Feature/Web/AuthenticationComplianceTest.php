<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthenticationComplianceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(PreventRequestForgery::class);
        config(['services.backend.url' => 'http://backend.test']);
        Http::preventStrayRequests();
    }

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ana Torres',
            'email' => 'ana@test.local',
            'password' => 'Abcd1234',
            'password_confirmation' => 'Abcd1234',
        ], $overrides);
    }

    private function loginPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'ana@test.local',
            'password' => 'Abcd1234',
        ], $overrides);
    }

    private function sessionUser(): array
    {
        return [
            'id' => 1,
            'name' => 'Ana Torres',
            'email' => 'ana@test.local',
        ];
    }

    public function test_cp01_registro_valido_redirige_al_login(): void
    {
        Http::fake([
            'http://backend.test/api/register' => Http::response([
                'message' => 'Cuenta creada correctamente.',
            ], 201),
        ]);

        $this->post('/registro', $this->registerPayload())
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Cuenta creada correctamente. Inicia sesión.');

        Http::assertSent(fn ($request) => $request->url() === 'http://backend.test/api/register'
            && ($request->data()['password_confirmation'] ?? null) === 'Abcd1234');
    }

    public function test_cp02_registro_con_campos_vacios(): void
    {
        Http::fake();

        $this->post('/registro', ['name' => '', 'email' => '', 'password' => ''])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        Http::assertNothingSent();
    }

    public function test_cp03_registro_con_correo_invalido(): void
    {
        Http::fake();

        $this->post('/registro', $this->registerPayload(['email' => 'ana@correo']))
            ->assertSessionHasErrors('email');

        Http::assertNothingSent();
    }

    public function test_cp04_registro_con_correo_duplicado(): void
    {
        Http::fake([
            'http://backend.test/api/register' => Http::response([
                'errors' => ['email' => ['Este correo ya está registrado.']],
            ], 422),
        ]);

        $this->post('/registro', $this->registerPayload(['email' => 'ANA@test.local']))
            ->assertSessionHasErrors('email');

        $this->assertSame('Este correo ya está registrado.', session('errors')->first('email'));
    }

    public function test_cp05_registro_con_password_corta(): void
    {
        Http::fake();

        $this->post('/registro', $this->registerPayload([
            'password' => 'Abc1234',
            'password_confirmation' => 'Abc1234',
        ]))->assertSessionHasErrors('password');

        Http::assertNothingSent();
    }

    public function test_cp06_registro_con_password_de_ocho_caracteres(): void
    {
        Http::fake([
            'http://backend.test/api/register' => Http::response([], 201),
        ]);

        $this->post('/registro', $this->registerPayload())
            ->assertRedirect(route('login'));
    }

    public function test_cp07_registro_con_confirmacion_distinta(): void
    {
        Http::fake();

        $this->post('/registro', $this->registerPayload([
            'password_confirmation' => 'Otra1234',
        ]))->assertSessionHasErrors('password');

        Http::assertNothingSent();
    }

    public function test_cp09_login_valido_guarda_token_y_redirige_al_panel(): void
    {
        Http::fake([
            'http://backend.test/api/login' => Http::response([
                'message' => 'Inicio de sesión correcto.',
                'user' => $this->sessionUser(),
                'token' => 'test-token',
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $this->post('/login', $this->loginPayload())
            ->assertRedirect(route('panel'))
            ->assertSessionHas('api_token', 'test-token')
            ->assertSessionHas('success', 'Inicio de sesión correcto.');

        $this->assertSame('ana@test.local', session('api_user')['email']);
    }

    public function test_cp10_login_con_password_incorrecta(): void
    {
        Http::fake([
            'http://backend.test/api/login' => Http::response([
                'errors' => ['email' => ['El correo o la contraseña no son correctos.']],
            ], 422),
        ]);

        $this->post('/login', $this->loginPayload(['password' => 'Otra1234']))
            ->assertSessionHasErrors('email');

        $this->assertSame(
            'El correo o la contraseña no son correctos.',
            session('errors')->first('email')
        );
    }

    public function test_cp11_login_con_correo_no_registrado(): void
    {
        Http::fake([
            'http://backend.test/api/login' => Http::response([
                'errors' => ['email' => ['El correo o la contraseña no son correctos.']],
            ], 422),
        ]);

        $this->post('/login', $this->loginPayload(['email' => 'nadie@test.local']))
            ->assertSessionHasErrors('email');

        $this->assertSame(
            'El correo o la contraseña no son correctos.',
            session('errors')->first('email')
        );
    }

    public function test_cp12_login_con_campos_vacios(): void
    {
        Http::fake();

        $this->post('/login', ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email', 'password']);

        Http::assertNothingSent();
    }

    public function test_cp13_panel_sin_sesion_redirige_al_login(): void
    {
        Http::fake();

        $this->get('/panel')->assertRedirect(route('login'));
        Http::assertNothingSent();
    }

    public function test_cp14_panel_con_sesion_muestra_el_usuario(): void
    {
        Http::fake([
            'http://backend.test/api/me' => Http::response([
                'user' => $this->sessionUser(),
            ], 200),
        ]);

        $response = $this->withSession([
            'api_token' => 'test-token',
            'api_user' => $this->sessionUser(),
        ])->get('/panel');

        $response->assertOk()
            ->assertSee('Ana Torres')
            ->assertHeader('Pragma', 'no-cache');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_panel_con_token_revocado_limpia_la_sesion(): void
    {
        Http::fake([
            'http://backend.test/api/me' => Http::response(['message' => 'Unauthenticated.'], 401),
        ]);

        $this->withSession([
            'api_token' => 'revoked-token',
            'api_user' => $this->sessionUser(),
        ])->get('/panel')
            ->assertRedirect(route('login'))
            ->assertSessionMissing('api_token')
            ->assertSessionHasErrors('email');
    }

    public function test_logout_limpia_la_sesion_y_redirige_al_login(): void
    {
        Http::fake([
            'http://backend.test/api/logout' => Http::response([
                'message' => 'Sesión cerrada correctamente.',
            ], 200),
        ]);

        $this->withSession([
            'api_token' => 'test-token',
            'api_user' => $this->sessionUser(),
        ])->post('/logout')
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Sesión cerrada correctamente.')
            ->assertSessionMissing('api_token');
    }

    public function test_login_con_backend_caido_muestra_un_error_amigable(): void
    {
        Http::fake(fn () => throw new ConnectionException('Backend no disponible.'));

        $this->post('/login', $this->loginPayload())
            ->assertSessionHasErrors('email');

        $this->assertSame(
            'No se pudo conectar con el servidor. Inténtalo de nuevo.',
            session('errors')->first('email')
        );
    }
}
