<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PasswordRecoveryInterfaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(PreventRequestForgery::class);
        config(['services.backend.url' => 'http://backend.test']);
        Http::preventStrayRequests();
    }

    public function test_la_pantalla_de_recuperacion_muestra_el_formulario(): void
    {
        $this->get(route('password.recovery'))
            ->assertOk()
            ->assertSee('Recuperar contraseña')
            ->assertSee('name="email"', false)
            ->assertSee('Enviar instrucciones');
    }

    public function test_el_login_enlaza_a_la_pantalla_de_recuperacion(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.recovery'));
    }

    public function test_la_pantalla_de_restablecimiento_lee_token_y_correo(): void
    {
        $this->get(route('password.reset', ['token' => 'token-prueba', 'email' => 'ana@test.local']))
            ->assertOk()
            ->assertSee('Restablecer contraseña')
            ->assertSee('token-prueba')
            ->assertSee('ana@test.local');
    }

    public function test_solicitar_recuperacion_llama_a_la_api(): void
    {
        Http::fake([
            'http://backend.test/api/forgot-password' => Http::response([
                'message' => 'Si el correo está registrado, enviamos un enlace de recuperación.',
            ], 200),
        ]);

        $this->post(route('password.recovery.store'), ['email' => 'ANA@test.local'])
            ->assertRedirect(route('password.recovery'))
            ->assertSessionHas('status', 'Si el correo está registrado, enviamos un enlace de recuperación.');

        Http::assertSent(fn ($request) => $request->url() === 'http://backend.test/api/forgot-password'
            && $request->data()['email'] === 'ana@test.local');
    }

    public function test_restablecer_contrasena_llama_a_la_api(): void
    {
        Http::fake([
            'http://backend.test/api/reset-password' => Http::response([
                'message' => 'Contraseña restablecida correctamente.',
            ], 200),
        ]);

        $this->post(route('password.reset.update'), [
            'email' => 'ana@test.local',
            'token' => 'token-prueba',
            'password' => 'Nueva1234',
            'password_confirmation' => 'Nueva1234',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Contraseña restablecida correctamente.');

        Http::assertSent(fn ($request) => $request->url() === 'http://backend.test/api/reset-password'
            && $request->data()['token'] === 'token-prueba');
    }

    public function test_restablecer_contrasena_muestra_el_error_del_backend(): void
    {
        Http::fake([
            'http://backend.test/api/reset-password' => Http::response([
                'errors' => ['email' => ['El enlace de recuperación no es válido o expiró.']],
            ], 422),
        ]);

        $this->post(route('password.reset.update'), [
            'email' => 'ana@test.local',
            'token' => 'token-invalido',
            'password' => 'Nueva1234',
            'password_confirmation' => 'Nueva1234',
        ])->assertSessionHasErrors('email');

        $this->assertSame(
            'El enlace de recuperación no es válido o expiró.',
            session('errors')->first('email')
        );
    }

    public function test_restablecer_contrasena_rechaza_password_debil(): void
    {
        Http::fake();

        $this->post(route('password.reset.update'), [
            'email' => 'ana@test.local',
            'token' => 'token-prueba',
            'password' => 'abc123',
            'password_confirmation' => 'abc123',
        ])->assertSessionHasErrors('password');

        Http::assertNothingSent();
    }

    public function test_solicitar_recuperacion_con_backend_caido_muestra_error(): void
    {
        Http::fake(fn () => throw new ConnectionException('Backend no disponible.'));

        $this->from(route('password.recovery'))
            ->post(route('password.recovery.store'), ['email' => 'ana@test.local'])
            ->assertRedirect(route('password.recovery'))
            ->assertSessionHasErrors('email');

        $this->assertSame(
            'No se pudo conectar con el servidor. Inténtalo de nuevo.',
            session('errors')->first('email')
        );
    }

    public function test_solicitar_recuperacion_con_error_422_muestra_el_error_del_backend(): void
    {
        Http::fake([
            'http://backend.test/api/forgot-password' => Http::response([
                'errors' => ['email' => ['El correo no es válido.']],
            ], 422),
        ]);

        $this->post(route('password.recovery.store'), ['email' => 'ana@test.local'])
            ->assertSessionHasErrors('email');

        $this->assertSame('El correo no es válido.', session('errors')->first('email'));
    }

    public function test_solicitar_recuperacion_con_error_del_servidor_muestra_mensaje_generico(): void
    {
        Http::fake([
            'http://backend.test/api/forgot-password' => Http::response([], 500),
        ]);

        $this->post(route('password.recovery.store'), ['email' => 'ana@test.local'])
            ->assertSessionHasErrors('email');

        $this->assertSame(
            'No se pudo enviar el enlace. Inténtalo de nuevo.',
            session('errors')->first('email')
        );
    }

    public function test_pantalla_de_restablecimiento_sin_token_redirige_a_recuperacion(): void
    {
        $this->get(route('password.reset'))
            ->assertRedirect(route('password.recovery'))
            ->assertSessionHasErrors('email');
    }

    public function test_restablecer_contrasena_con_backend_caido_muestra_error(): void
    {
        Http::fake(fn () => throw new ConnectionException('Backend no disponible.'));

        $this->from(route('password.reset', ['token' => 'token-prueba', 'email' => 'ana@test.local']))
            ->post(route('password.reset.update'), [
                'email' => 'ana@test.local',
                'token' => 'token-prueba',
                'password' => 'Nueva1234',
                'password_confirmation' => 'Nueva1234',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame(
            'No se pudo conectar con el servidor. Inténtalo de nuevo.',
            session('errors')->first('email')
        );
    }

    public function test_restablecer_contrasena_con_error_del_servidor_muestra_mensaje_generico(): void
    {
        Http::fake([
            'http://backend.test/api/reset-password' => Http::response([], 500),
        ]);

        $this->from(route('password.reset', ['token' => 'token-prueba', 'email' => 'ana@test.local']))
            ->post(route('password.reset.update'), [
                'email' => 'ana@test.local',
                'token' => 'token-prueba',
                'password' => 'Nueva1234',
                'password_confirmation' => 'Nueva1234',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame(
            'No se pudo restablecer la contraseña. Inténtalo de nuevo.',
            session('errors')->first('email')
        );
    }
}
