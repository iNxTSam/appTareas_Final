<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ana Torres',
            'email' => 'ana@test.local',
            'password' => 'Abcd1234',
            'password_confirmation' => 'Abcd1234',
        ], $overrides);
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Ana Torres',
            'email' => 'ana@test.local',
            'password' => 'Abcd1234',
        ]);
    }

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/register', $this->registerPayload());

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Cuenta creada correctamente.')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'ana@test.local')
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email'],
                'token',
                'token_type',
            ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertArrayNotHasKey('password', $response->json('user'));
        $this->assertDatabaseHas('users', ['email' => 'ana@test.local']);
    }

    public function test_register_stores_hashed_password(): void
    {
        $this->postJson('/api/register', $this->registerPayload())->assertCreated();

        $user = User::where('email', 'ana@test.local')->firstOrFail();

        $this->assertNotSame('Abcd1234', $user->password);
        $this->assertTrue(Hash::check('Abcd1234', $user->password));
    }

    public function test_register_validates_required_fields(): void
    {
        $this->postJson('/api/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_rejects_invalid_email(): void
    {
        $this->postJson('/api/register', $this->registerPayload(['email' => 'ana@correo']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_register_rejects_duplicate_email_case_insensitive(): void
    {
        $this->postJson('/api/register', $this->registerPayload())->assertCreated();

        $this->postJson('/api/register', $this->registerPayload(['email' => 'ANA@TEST.LOCAL']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_register_rejects_weak_password(): void
    {
        $payload = $this->registerPayload([
            'password' => 'Abc1234',
            'password_confirmation' => 'Abc1234',
        ]);

        $this->postJson('/api/register', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_returns_token_with_valid_credentials(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/login', [
            'email' => 'ana@test.local',
            'password' => 'Abcd1234',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Inicio de sesión correcto.')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'ana@test.local')
            ->assertJsonStructure(['message', 'user', 'token', 'token_type']);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_is_case_insensitive(): void
    {
        $this->createUser();

        $this->postJson('/api/login', [
            'email' => 'ANA@TEST.LOCAL',
            'password' => 'Abcd1234',
        ])->assertOk();
    }

    public function test_login_rejects_wrong_password_with_generic_message(): void
    {
        $this->createUser();

        $this->postJson('/api/login', [
            'email' => 'ana@test.local',
            'password' => 'Otra1234',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'El correo o la contraseña no son correctos.');
    }

    public function test_login_rejects_unknown_email_with_generic_message(): void
    {
        $this->postJson('/api/login', [
            'email' => 'nadie@test.local',
            'password' => 'Abcd1234',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'El correo o la contraseña no son correctos.');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $token = $this->postJson('/api/register', $this->registerPayload())
            ->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'ana@test.local');
    }

    public function test_logout_revokes_current_token(): void
    {
        $token = $this->postJson('/api/register', $this->registerPayload())
            ->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Sesión cerrada correctamente.');

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
