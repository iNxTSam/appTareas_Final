<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\BackendApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordRecoveryController extends Controller
{
    public function __construct(private BackendApiClient $api) {}

    public function create()
    {
        return view('auth.password-recovery');
    }

    public function store(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'email' => ['required', 'email:filter', 'max:255'],
        ], [
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
        ]);

        try {
            $response = $this->api->forgotPassword($data['email']);
        } catch (ConnectionException) {
            return back()->withErrors([
                'email' => 'No se pudo conectar con el servidor. Inténtalo de nuevo.',
            ])->onlyInput('email');
        }

        if ($response->status() === 422) {
            return back()->withErrors(
                $response->json('errors', ['email' => ['No se pudo enviar el enlace. Inténtalo de nuevo.']])
            )->onlyInput('email');
        }

        if (! $response->successful()) {
            return back()->withErrors([
                'email' => 'No se pudo enviar el enlace. Inténtalo de nuevo.',
            ])->onlyInput('email');
        }

        return redirect()
            ->route('password.recovery')
            ->with('status', $response->json('message', 'Si el correo está registrado, enviamos un enlace de recuperación.'));
    }

    public function edit(Request $request)
    {
        $token = (string) $request->query('token', '');
        $email = (string) $request->query('email', '');

        if ($token === '' || $email === '') {
            return redirect()
                ->route('password.recovery')
                ->withErrors(['email' => 'El enlace de recuperación no es válido.']);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => Str::lower(trim($email)),
        ]);
    }

    public function update(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'email' => ['required', 'email:filter', 'max:255'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'password_confirmation' => ['required'],
        ], [
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'token.required' => 'El enlace de recuperación no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password_confirmation.required' => 'Confirma tu contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.mixed' => 'La contraseña debe incluir al menos una letra mayúscula y una minúscula.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
        ]);

        try {
            $response = $this->api->resetPassword($data);
        } catch (ConnectionException) {
            return back()->withErrors([
                'email' => 'No se pudo conectar con el servidor. Inténtalo de nuevo.',
            ])->withInput($request->only('email', 'token'));
        }

        if ($response->status() === 422) {
            return back()->withErrors(
                $response->json('errors', ['email' => ['El enlace de recuperación no es válido o expiró.']])
            )->withInput($request->only('email', 'token'));
        }

        if (! $response->successful()) {
            return back()->withErrors([
                'email' => 'No se pudo restablecer la contraseña. Inténtalo de nuevo.',
            ])->withInput($request->only('email', 'token'));
        }

        return redirect()
            ->route('login')
            ->with('success', $response->json('message', 'Contraseña restablecida correctamente.'));
    }
}
