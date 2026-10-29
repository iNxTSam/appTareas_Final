<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\BackendApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function __construct(private BackendApiClient $api) {}

    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:filter', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'password_confirmation' => ['required'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password_confirmation.required' => 'Confirma tu contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.mixed' => 'La contraseña debe incluir al menos una letra mayúscula y una minúscula.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
        ]);

        try {
            $response = $this->api->register($data);
        } catch (ConnectionException) {
            return back()->withErrors([
                'email' => 'No se pudo conectar con el servidor. Inténtalo de nuevo.',
            ])->withInput();
        }

        if ($response->status() === 422) {
            return back()->withErrors(
                $response->json('errors', ['email' => ['No se pudo crear la cuenta.']])
            )->withInput();
        }

        if (! $response->successful()) {
            return back()->withErrors([
                'email' => 'No se pudo crear la cuenta. Inténtalo de nuevo.',
            ])->withInput();
        }

        return redirect()->route('login')->with('success', 'Cuenta creada correctamente. Inicia sesión.');
    }
}
