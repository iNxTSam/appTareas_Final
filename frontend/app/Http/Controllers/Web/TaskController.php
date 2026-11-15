<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\BackendApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    private const STATUSES = ['pendiente', 'en_progreso', 'completada'];

    private const PRIORITIES = ['low', 'medium', 'high'];

    /**
     * @var array<string, string>
     */
    private const API_PRIORITIES = [
        'low' => 'normal',
        'medium' => 'normal',
        'high' => 'importante',
    ];

    /**
     * @var array<string, string>
     */
    private const UI_PRIORITIES = [
        'normal' => 'medium',
        'importante' => 'high',
    ];

    public function __construct(private BackendApiClient $api) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date', 'after_or_equal:from'],
            'status' => ['sometimes', 'nullable', Rule::in(self::STATUSES)],
            'priority' => ['sometimes', 'nullable', Rule::in(self::PRIORITIES)],
        ]);

        $filters = array_filter(
            $filters,
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );

        return $this->proxyTaskCollection(fn (): Response => $this->api->tasks(
            (string) $request->session()->get('api_token'),
            $this->toApiFilters($filters),
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->taskRules());

        return $this->proxyTask(fn (): Response => $this->api->createTask(
            (string) $request->session()->get('api_token'),
            $this->toApiPayload($data),
        ));
    }

    public function show(Request $request, string $task): JsonResponse
    {
        return $this->proxyTask(fn (): Response => $this->api->task(
            (string) $request->session()->get('api_token'),
            $task,
        ));
    }

    public function update(Request $request, string $task): JsonResponse
    {
        $data = $request->validate($this->taskRules());

        return $this->proxyTask(fn (): Response => $this->api->updateTask(
            (string) $request->session()->get('api_token'),
            $task,
            $this->toApiPayload($data),
        ));
    }

    public function destroy(Request $request, string $task): JsonResponse
    {
        return $this->proxy(fn (): Response => $this->api->deleteTask(
            (string) $request->session()->get('api_token'),
            $task,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function taskRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'priority' => ['required', Rule::in(self::PRIORITIES)],
            'team' => ['nullable', 'string', 'max:60'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'responsible_id' => ['nullable', 'string', 'max:120'],
            'responsible_name' => ['nullable', 'string', 'max:120'],
            'subtasks' => ['sometimes', 'array'],
            'subtasks.*.text' => ['required', 'string', 'max:160'],
            'subtasks.*.done' => ['required', 'boolean'],
            'subtasks.*.responsible_id' => ['nullable', 'string', 'max:120'],
            'subtasks.*.responsible_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function toApiFilters(array $filters): array
    {
        if (isset($filters['priority'])) {
            $filters['priority'] = self::API_PRIORITIES[$filters['priority']];
        }

        foreach (['from', 'to'] as $field) {
            if (isset($filters[$field])) {
                $filters[$field] = Carbon::parse((string) $filters[$field])->toDateString();
            }
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function toApiPayload(array $data): array
    {
        $startDate = $data['start_date'] ?? $data['due_date'] ?? now()->toDateString();
        $endDate = $data['due_date'] ?? $startDate;

        return [
            'name' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_date' => Carbon::parse((string) $startDate)->toDateString(),
            'end_date' => Carbon::parse((string) $endDate)->toDateString(),
            'status' => $data['status'],
            'priority' => self::API_PRIORITIES[$data['priority']],
            'team' => $data['team'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $task
     * @return array<string, mixed>
     */
    private function fromApiTask(array $task): array
    {
        $priority = (string) ($task['priority'] ?? 'normal');

        return [
            'id' => $task['id'] ?? null,
            'title' => $task['name'] ?? '',
            'description' => $task['description'] ?? '',
            'status' => $task['status'] ?? 'pendiente',
            'status_label' => $task['status_label'] ?? null,
            'priority' => self::UI_PRIORITIES[$priority] ?? 'medium',
            'priority_label' => $task['priority_label'] ?? null,
            'team' => $task['team'] ?? '',
            'start_date' => $task['start_date'] ?? null,
            'due_date' => $task['end_date'] ?? null,
            'created_at' => $task['created_at'] ?? null,
            'updated_at' => $task['updated_at'] ?? null,
        ];
    }

    private function proxyTaskCollection(callable $request): JsonResponse
    {
        try {
            $response = $request();
        } catch (ConnectionException) {
            return $this->connectionError();
        }

        $payload = $response->json();

        if ($response->successful() && is_array($payload) && is_array($payload['data'] ?? null)) {
            $payload['data'] = array_map(
                fn (array $task): array => $this->fromApiTask($task),
                $payload['data'],
            );
        }

        return response()->json($payload, $response->status());
    }

    private function proxyTask(callable $request): JsonResponse
    {
        try {
            $response = $request();
        } catch (ConnectionException) {
            return $this->connectionError();
        }

        $payload = $response->json();

        if ($response->successful() && is_array($payload) && is_array($payload['data'] ?? null)) {
            $payload['data'] = $this->fromApiTask($payload['data']);
        }

        return response()->json($payload, $response->status());
    }

    private function proxy(callable $request): JsonResponse
    {
        try {
            $response = $request();
        } catch (ConnectionException) {
            return $this->connectionError();
        }

        return response()->json($response->json(), $response->status());
    }

    private function connectionError(): JsonResponse
    {
        return response()->json([
            'message' => 'No se pudo conectar con el servidor de tareas.',
        ], 503);
    }
}
