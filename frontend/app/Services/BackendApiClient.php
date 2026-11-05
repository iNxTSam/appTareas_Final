<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BackendApiClient
{
    private function client(): PendingRequest
    {
        return Http::baseUrl(config('services.backend.url'))
            ->acceptJson()
            ->asJson()
            ->timeout(10);
    }

    public function register(array $data): Response
    {
        return $this->client()->post('/api/register', $data);
    }

    public function login(string $email, string $password, ?string $clientIp = null): Response
    {
        $client = $this->client();

        if ($clientIp !== null) {
            $client = $client->withHeaders(['X-Forwarded-For' => $clientIp]);
        }

        return $client->post('/api/login', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function me(string $token): Response
    {
        return $this->client()->withToken($token)->get('/api/me');
    }

    public function logout(string $token): Response
    {
        return $this->client()->withToken($token)->post('/api/logout');
    }

    public function forgotPassword(string $email): Response
    {
        return $this->client()->post('/api/forgot-password', [
            'email' => $email,
        ]);
    }

    public function resetPassword(array $data): Response
    {
        return $this->client()->post('/api/reset-password', $data);
    }
}
