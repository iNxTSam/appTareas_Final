<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetApiTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::create([
            'name' => 'Ana Torres',
            'email' => 'ana@test.local',
            'password' => 'Abcd1234',
        ]);
    }

    public function test_forgot_password_mensaje_generico_correo_no_registrado(): void
    {
        Notification::fake();

        $this->postJson('/api/forgot-password', ['email' => 'nadie@test.local'])
            ->assertOk()
            ->assertJsonPath('message', 'Si el correo está registrado, enviamos un enlace de recuperación.');

        Notification::assertNothingSent();
    }

    public function test_forgot_password_mensaje_generico_correo_registrado(): void
    {
        Notification::fake();
        $user = $this->createUser();

        $this->postJson('/api/forgot-password', ['email' => 'ana@test.local'])
            ->assertOk()
            ->assertJsonPath('message', 'Si el correo está registrado, enviamos un enlace de recuperación.');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_envia_enlace_token_seguro_un_solo_uso(): void
    {
        Notification::fake();
        $user = $this->createUser();

        $this->postJson('/api/forgot-password', ['email' => 'ana@test.local'])->assertOk();

        $tokenFromMail = '';
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use (&$tokenFromMail) {
            $tokenFromMail = $notification->token;

            return $notification->token !== '';
        });

        $this->assertNotEmpty($tokenFromMail);

        $stored = \DB::table('password_reset_tokens')->where('email', 'ana@test.local')->first();
        $this->assertNotNull($stored);
        $this->assertNotSame($tokenFromMail, $stored->token);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($tokenFromMail, $stored->token));
    }

    public function test_reset_password_rechaza_token_expirado(): void
    {
        $user = $this->createUser();
        $token = Password::createToken($user);

        $this->travel(31)->minutes();

        $this->postJson('/api/reset-password', [
            'email' => 'ana@test.local',
            'token' => $token,
            'password' => 'Nueva1234',
            'password_confirmation' => 'Nueva1234',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'El enlace de recuperación no es válido o expiró.');
    }

    public function test_reset_password_rechaza_password_debil(): void
    {
        $user = $this->createUser();
        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => 'ana@test.local',
            'token' => $token,
            'password' => 'abc123',
            'password_confirmation' => 'abc123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_reset_password_invalida_token_tras_el_cambio(): void
    {
        $user = $this->createUser();
        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => 'ana@test.local',
            'token' => $token,
            'password' => 'Nueva1234',
            'password_confirmation' => 'Nueva1234',
        ])->assertOk()
            ->assertJsonPath('message', 'Contraseña restablecida correctamente.');

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'ana@test.local']);

        $this->travel(61)->seconds();

        $this->postJson('/api/reset-password', [
            'email' => 'ana@test.local',
            'token' => $token,
            'password' => 'Otra1234',
            'password_confirmation' => 'Otra1234',
        ])->assertUnprocessable();
    }

    public function test_password_anterior_deja_de_funcionar(): void
    {
        $user = $this->createUser();
        $token = Password::createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => 'ana@test.local',
            'token' => $token,
            'password' => 'Nueva1234',
            'password_confirmation' => 'Nueva1234',
        ])->assertOk();

        $this->postJson('/api/login', ['email' => 'ana@test.local', 'password' => 'Abcd1234'])
            ->assertUnprocessable();

        $this->postJson('/api/login', ['email' => 'ana@test.local', 'password' => 'Nueva1234'])
            ->assertOk();
    }
}
