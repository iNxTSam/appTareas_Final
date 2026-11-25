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

    public function tasks(string $token, array $filters = []): Response
    {
        return $this->client()->withToken($token)->get('/api/tasks', $filters);
    }

    public function teams(string $token): Response
    {
        return $this->client()->withToken($token)->get('/api/teams');
    }

    public function projects(string $token): Response
    {
        return $this->client()->withToken($token)->get('/api/projects');
    }

    public function accounts(string $token): Response
    {
        return $this->client()->withToken($token)->get('/api/accounts');
    }

    public function createTask(string $token, array $data): Response
    {
        return $this->client()->withToken($token)->post('/api/tasks', $data);
    }

    public function task(string $token, string $id): Response
    {
        return $this->client()->withToken($token)->get('/api/tasks/'.rawurlencode($id));
    }

    public function updateTask(string $token, string $id, array $data): Response
    {
        return $this->client()->withToken($token)->put('/api/tasks/'.rawurlencode($id), $data);
    }

    public function deleteTask(string $token, string $id): Response
    {
        return $this->client()->withToken($token)->delete('/api/tasks/'.rawurlencode($id));
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
