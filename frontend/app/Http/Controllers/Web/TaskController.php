<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\BackendApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    private const STATUSES = ['pendiente', 'en_progreso', 'completada'];

    private const PRIORITIES = ['low', 'medium', 'high'];

    public function __construct(private BackendApiClient $api) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date', 'after_or_equal:from'],
            'status' => ['sometimes', 'nullable', Rule::in(self::STATUSES)],
            'priority' => ['sometimes', 'nullable', Rule::in(self::PRIORITIES)],
        ]);

        return $this->proxy(fn (): Response => $this->api->tasks(
            (string) $request->session()->get('api_token'),
            $filters
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->taskRules());

        return $this->proxy(fn (): Response => $this->api->createTask(
            (string) $request->session()->get('api_token'),
            $data
        ));
    }

    public function show(Request $request, string $task): JsonResponse
    {
        return $this->proxy(fn (): Response => $this->api->task(
            (string) $request->session()->get('api_token'),
            $task
        ));
    }

    public function update(Request $request, string $task): JsonResponse
    {
        $data = $request->validate($this->taskRules());

        return $this->proxy(fn (): Response => $this->api->updateTask(
            (string) $request->session()->get('api_token'),
            $task,
            $data
        ));
    }

    public function destroy(Request $request, string $task): JsonResponse
    {
        return $this->proxy(fn (): Response => $this->api->deleteTask(
            (string) $request->session()->get('api_token'),
            $task
        ));
    }

    private function taskRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'priority' => ['required', Rule::in(self::PRIORITIES)],
            'team' => ['nullable', 'string', 'max:120'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'responsible_id' => ['nullable', 'string', 'max:120'],
            'responsible_name' => ['nullable', 'string', 'max:120'],
            'subtasks' => ['sometimes', 'array'],
            'subtasks.*.text' => ['required', 'string', 'max:160'],
            'subtasks.*.done' => ['required', 'boolean'],
            'subtasks.*.responsible_id' => ['nullable', 'string', 'max:120'],
            'subtasks.*.responsible_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    private function proxy(callable $request): JsonResponse
    {
        try {
            $response = $request();
        } catch (ConnectionException) {
            return response()->json([
                'message' => 'No se pudo conectar con el servidor de tareas.',
            ], 503);
        }

        return response()->json($response->json(), $response->status());
    }
}
