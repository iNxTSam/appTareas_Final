<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\BackendApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;

class PanelController extends Controller
{
    public function __construct(private BackendApiClient $api) {}

    public function index(Request $request)
    {
        $token = $request->session()->get('api_token');
        $user = $request->session()->get('api_user');

        try {
            $response = $this->api->me($token);
        } catch (ConnectionException) {
            return view('panel', [
                'apiUser' => $user,
                'apiError' => 'No se pudo validar la sesión con el servidor.',
            ]);
        }

        if ($response->status() === 401) {
            $request->session()->forget(['api_token', 'api_user']);

            return redirect()->route('login')->withErrors([
                'email' => 'Tu sesión expiró. Inicia sesión de nuevo.',
            ]);
        }

        if ($response->successful()) {
            $user = $response->json('user');
            $request->session()->put('api_user', $user);
        }

        return view('panel', [
            'apiUser' => $user,
            'apiError' => $response->successful()
                ? null
                : 'No se pudo cargar la información de tu cuenta.',
        ]);
    }
}
