<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\BackendApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function __construct(private BackendApiClient $api) {}

    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $email = Str::lower(trim($credentials['email']));

        try {
            $response = $this->api->login($email, $credentials['password']);
        } catch (ConnectionException) {
            return back()->withErrors([
                'email' => 'No se pudo conectar con el servidor. Inténtalo de nuevo.',
            ])->onlyInput('email');
        }

        if ($response->status() === 422) {
            return back()->withErrors(
                $response->json('errors', ['email' => ['El correo o la contraseña no son correctos.']])
            )->onlyInput('email');
        }

        if (! $response->successful()) {
            return back()->withErrors([
                'email' => 'No se pudo iniciar sesión. Inténtalo de nuevo.',
            ])->onlyInput('email');
        }

        $data = $response->json();

        $request->session()->put([
            'api_token' => $data['token'],
            'api_user' => $data['user'],
        ]);

        $request->session()->regenerate();

        return redirect()->route('panel')->with('success', 'Inicio de sesión correcto.');
    }

    public function destroy(Request $request)
    {
        $token = $request->session()->get('api_token');

        if ($token) {
            try {
                $this->api->logout($token);
            } catch (ConnectionException) {
                // La sesión local se cierra aunque el backend no responda.
            }
        }

        $request->session()->forget(['api_token', 'api_user']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sesión cerrada correctamente.');
    }
}
