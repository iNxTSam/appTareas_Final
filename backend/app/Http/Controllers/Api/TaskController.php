<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Account;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function index(IndexTaskRequest $request): AnonymousResourceCollection
    {
        $filters = array_filter(
            $request->validated(),
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );

        $query = Task::query()
            ->visibleTo($request->user())
            ->with($this->relations());

        if (isset($filters['from'])) {
            $query->where('end_date', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('start_date', '<=', $filters['to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['team_id'])) {
            $query->where('team_id', $filters['team_id']);
        }

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['assignee_id'])) {
            $query->whereHas('assignees', static fn (Builder $query): Builder => $query
                ->where('account.account_id', $filters['assignee_id']));
        }

        return TaskResource::collection(
            $query->orderBy('start_date')->orderBy('task_id')->get(),
        );
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $account = $request->user();
        $data = $request->validated();
        $teamId = $data['team_id'] ?? null;
        $projectId = $data['project_id'] ?? null;

        $this->assertTeamAccess($account, $teamId);
        $this->assertProjectBelongsToTeam($teamId, $projectId);
        $assigneeIds = $data['assignee_ids'] ?? [];
        $this->assertAssignees($account, $teamId, $assigneeIds);

        $task = Task::create([
            'team_id' => $teamId,
            'project_id' => $projectId,
            'created_by' => $account->account_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'priority' => $data['priority'] ?? 'media',
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'completed_at' => $data['status'] === 'completada' ? now() : null,
        ]);

        $task->assignees()->sync($assigneeIds);
        $this->syncSubtasks($task, $data['subtasks'] ?? [], $account, $teamId);

        return (new TaskResource($task->load($this->relations())))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $task): TaskResource
    {
        return new TaskResource(
            $this->findVisible($request, $task)->load($this->relations()),
        );
    }

    public function update(UpdateTaskRequest $request, int $task): TaskResource
    {
        $account = $request->user();
        $model = $this->findVisible($request, $task);
        $data = $request->validated();
        $teamId = $data['team_id'] ?? null;
        $projectId = $data['project_id'] ?? null;

        $this->assertTeamAccess($account, $teamId);
        $this->assertProjectBelongsToTeam($teamId, $projectId);
        $assigneeIds = $data['assignee_ids'] ?? [];
        $this->assertAssignees($account, $teamId, $assigneeIds);

        $model->update([
            'team_id' => $teamId,
            'project_id' => $projectId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'priority' => $data['priority'] ?? $model->priority->value,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'completed_at' => $data['status'] === 'completada'
                ? ($model->completed_at ?? now())
                : null,
        ]);

        $model->assignees()->sync($assigneeIds);
        $this->syncSubtasks($model, $data['subtasks'] ?? [], $account, $teamId);

        return new TaskResource($model->load($this->relations()));
    }

    public function destroy(Request $request, int $task): Response
    {
        $this->findVisible($request, $task)->delete();

        return response()->noContent();
    }

    private function findVisible(Request $request, int $taskId): Task
    {
        return Task::query()->visibleTo($request->user())->findOrFail($taskId);
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return ['team', 'project', 'creator', 'assignees', 'subtasks.assignee'];
    }

    private function assertTeamAccess(Account $account, ?int $teamId): void
    {
        if ($teamId === null) {
            return;
        }

        if (! $account->teams()->where('team.team_id', $teamId)->exists()) {
            throw ValidationException::withMessages([
                'team_id' => ['No perteneces a ese equipo.'],
            ]);
        }
    }

    private function assertProjectBelongsToTeam(?int $teamId, ?int $projectId): void
    {
        if ($projectId === null) {
            return;
        }

        if ($teamId === null) {
            throw ValidationException::withMessages([
                'project_id' => ['Un proyecto requiere un equipo.'],
            ]);
        }

        $project = Project::find($projectId);

        if ($project === null || (int) $project->team_id !== (int) $teamId) {
            throw ValidationException::withMessages([
                'project_id' => ['El proyecto no pertenece al equipo.'],
            ]);
        }
    }

    /**
     * @param  array<int, int>  $assigneeIds
     */
    private function assertAssignees(Account $account, ?int $teamId, array $assigneeIds): void
    {
        if ($assigneeIds === []) {
            return;
        }

        $allowed = [$account->account_id];

        if ($teamId !== null) {
            $team = $account->teams()->where('team.team_id', $teamId)->first();

            $allowed = $team?->members()->pluck('account.account_id')->all() ?? [];
        }

        if (array_diff($assigneeIds, $allowed) !== []) {
            throw ValidationException::withMessages([
                'assignee_ids' => ['Los responsables deben pertenecer al equipo.'],
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $subtasks
     */
    private function syncSubtasks(Task $task, array $subtasks, Account $account, ?int $teamId): void
    {
        $task->subtasks()->delete();

        foreach (array_values($subtasks) as $index => $subtask) {
            $assigneeId = $subtask['assignee_id'] ?? null;

            if ($assigneeId !== null) {
                $this->assertAssignees($account, $teamId, [$assigneeId]);
            }

            Subtask::create([
                'task_id' => $task->task_id,
                'title' => $subtask['title'],
                'is_done' => (bool) ($subtask['is_done'] ?? false),
                'position' => $subtask['position'] ?? $index + 1,
                'assignee_id' => $assigneeId,
            ]);
        }
    }
}
