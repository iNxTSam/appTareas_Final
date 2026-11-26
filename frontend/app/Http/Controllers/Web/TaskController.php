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
        'low' => 'baja',
        'medium' => 'media',
        'high' => 'alta',
    ];

    /**
     * @var array<string, string>
     */
    private const UI_PRIORITIES = [
        'baja' => 'low',
        'media' => 'medium',
        'alta' => 'high',
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
            'team_id' => ['nullable', 'integer', 'min:1'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'priority' => ['required', Rule::in(self::PRIORITIES)],
            'start_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'responsible_id' => ['nullable', 'string', 'max:120'],
            'subtasks' => ['sometimes', 'array'],
            'subtasks.*.title' => ['required', 'string', 'max:160'],
            'subtasks.*.is_done' => ['required', 'boolean'],
            'subtasks.*.responsible_id' => ['nullable', 'string', 'max:120'],
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
        $startDate = $data['start_date'];
        $endDate = $data['due_date'] ?? $startDate;
        $responsibleId = (string) ($data['responsible_id'] ?? '');
        $subtasks = array_map(static fn (array $subtask): array => [
            'title' => $subtask['title'],
            'is_done' => (bool) $subtask['is_done'],
        ], $data['subtasks'] ?? []);

        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'team_id' => isset($data['team_id']) ? (int) $data['team_id'] : null,
            'project_id' => isset($data['project_id']) ? (int) $data['project_id'] : null,
            'start_date' => Carbon::parse((string) $startDate)->toDateString(),
            'end_date' => Carbon::parse((string) $endDate)->toDateString(),
            'status' => $data['status'],
            'priority' => self::API_PRIORITIES[$data['priority']],
            'assignee_ids' => ctype_digit($responsibleId) ? [(int) $responsibleId] : [],
            'subtasks' => $subtasks,
        ];
    }

    /**
     * @param  array<string, mixed>  $task
     * @return array<string, mixed>
     */
    private function fromApiTask(array $task): array
    {
        $priority = (string) ($task['priority'] ?? 'media');
        $assignees = is_array($task['assignees'] ?? null) ? $task['assignees'] : [];
        $firstAssignee = $assignees[0] ?? null;

        return [
            'id' => $task['id'] ?? null,
            'title' => $task['title'] ?? '',
            'description' => $task['description'] ?? '',
            'category' => $task['project']['name'] ?? 'GENERAL',
            'project_id' => $task['project']['id'] ?? null,
            'status' => $task['status'] ?? 'pendiente',
            'status_label' => $task['status_label'] ?? null,
            'priority' => self::UI_PRIORITIES[$priority] ?? 'medium',
            'priority_label' => $task['priority_label'] ?? null,
            'team' => $task['team']['name'] ?? '',
            'team_id' => $task['team']['id'] ?? null,
            'responsible_id' => $firstAssignee['id'] ?? '',
            'responsible_name' => $firstAssignee['name'] ?? '',
            'assignees' => $assignees,
            'subtasks' => array_map(static fn (array $subtask): array => [
                'id' => $subtask['id'] ?? null,
                'text' => $subtask['title'] ?? '',
                'done' => (bool) ($subtask['is_done'] ?? false),
                'responsible_id' => $subtask['assignee']['id'] ?? '',
                'responsible_name' => $subtask['assignee']['name'] ?? '',
            ], is_array($task['subtasks'] ?? null) ? $task['subtasks'] : []),
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
