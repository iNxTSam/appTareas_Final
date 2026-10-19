<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function registrarParams(): array
    {
        return [
            'name' => 'Ana',
            'email' => 'ana@test.local',
            'password' => 'Abcd1234',
            'password_confirmation' => 'Abcd1234',
        ];
    }

    private function crearUsuario(): User
    {
        return User::create([
            'name' => 'Ana',
            'email' => 'ana@test.local',
            'password' => Hash::make('Abcd1234'),
        ]);
    }

    public function test_cp01_registro_valido(): void
    {
        $this->post('/registro', $this->registrarParams())
            ->assertRedirect(route('register'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['email' => 'ana@test.local']);
    }

    public function test_cp02_campos_vacios(): void
    {
        $this->post('/registro', ['name' => '', 'email' => '', 'password' => ''])
            ->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_cp03_correo_formato_invalido(): void
    {
        $p = $this->registrarParams();
        $p['email'] = 'ana@correo';
        $this->post('/registro', $p)->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_cp04_correo_duplicado_case_insensitive(): void
    {
        $this->post('/registro', $this->registrarParams());
        $p = $this->registrarParams();
        $p['email'] = 'ANA@test.local';
        $this->post('/registro', $p)->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_cp05_password_7_caracteres(): void
    {
        $p = $this->registrarParams();
        $p['password'] = 'Abc1234';
        $p['password_confirmation'] = 'Abc1234';
        $this->post('/registro', $p)->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_cp06_password_8_caracteres(): void
    {
        $this->post('/registro', $this->registrarParams());
        $this->assertDatabaseCount('users', 1);
    }

    public function test_cp07_confirmacion_difiere(): void
    {
        $p = $this->registrarParams();
        $p['password_confirmation'] = 'Distinta1';
        $this->post('/registro', $p)->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_cp08_password_hasheada(): void
    {
        $this->post('/registro', $this->registrarParams());
        $hash = User::first()->password;
        $this->assertNotSame('Abcd1234', $hash);
        $this->assertTrue(Hash::check('Abcd1234', $hash));
    }

    public function test_cp09_login_credenciales_correctas(): void
    {
        $this->crearUsuario();
        $this->post('/login', ['email' => 'ana@test.local', 'password' => 'Abcd1234'])
            ->assertRedirect(route('panel'));
        $this->assertAuthenticated();
    }

    public function test_cp10_password_incorrecta_mensaje_generico(): void
    {
        $this->crearUsuario();
        $this->post('/login', ['email' => 'ana@test.local', 'password' => 'Otra1234'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(
            'El correo o la contraseña no son correctos.',
            session('errors')->first('email')
        );
    }

    public function test_cp11_correo_no_registrado_mismo_mensaje_generico(): void
    {
        $this->post('/login', ['email' => 'nadie@test.local', 'password' => 'Abcd1234'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(
            'El correo o la contraseña no son correctos.',
            session('errors')->first('email')
        );
    }

    public function test_cp12_campos_vacios_login(): void
    {
        $this->post('/login', ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_cp13_url_privada_sin_sesion(): void
    {
        $this->get('/panel')->assertRedirect(route('login'));
    }

    public function test_cp14_url_privada_con_sesion(): void
    {
        $user = $this->crearUsuario();
        $this->actingAs($user)
            ->get('/panel')
            ->assertOk()
            ->assertSee('Área privada');
    }

    public function test_login_case_insensitive(): void
    {
        $this->crearUsuario();
        $this->post('/login', ['email' => 'ANA@Test.LOCAL', 'password' => 'Abcd1234'])
            ->assertRedirect(route('panel'));
        $this->assertAuthenticated();
    }
}
