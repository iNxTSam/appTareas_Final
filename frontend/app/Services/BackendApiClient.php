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

    public function login(string $email, string $password): Response
    {
        return $this->client()->post('/api/login', [
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
}
