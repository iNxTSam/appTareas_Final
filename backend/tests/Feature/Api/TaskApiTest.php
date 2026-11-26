<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function createAccount(string $email = 'ana@test.local', string $name = 'Ana Torres'): Account
    {
        return Account::create([
            'name' => $name,
            'email' => $email,
            'password' => 'Abcd1234',
        ]);
    }

    private function loginFor(string $email): string
    {
        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'Abcd1234',
        ]);

        $response->assertOk();

        return (string) $response->json('token');
    }

    private function authenticate(Account $account): void
    {
        $token = $this->loginFor($account->email);
        $this->withHeader('Authorization', "Bearer {$token}");
    }

    private function makeTeam(Account $owner, Account ...$members): Team
    {
        $team = Team::factory()->create(['owner_id' => $owner->account_id]);
        $team->members()->attach($owner->account_id, ['role' => 'owner']);

        foreach ($members as $member) {
            $team->members()->attach($member->account_id, ['role' => 'member']);
        }

        return $team;
    }

    private function makeTask(Account $creator, array $overrides = []): Task
    {
        return Task::factory()->create(array_merge([
            'created_by' => $creator->account_id,
        ], $overrides));
    }

    private function taskPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Preparar el tablero',
            'description' => 'Tareas de la semana',
            'status' => 'pendiente',
            'priority' => 'media',
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-20',
        ], $overrides);
    }

    public function test_tasks_index_requires_authentication(): void
    {
        $this->getJson('/api/tasks')->assertUnauthorized();
    }

    public function test_tasks_store_requires_authentication(): void
    {
        $this->postJson('/api/tasks', $this->taskPayload())->assertUnauthorized();
    }

    public function test_tasks_index_returns_empty_data_when_account_has_no_tasks(): void
    {
        $account = $this->createAccount();
        $this->authenticate($account);

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_tasks_index_returns_personal_tasks_of_the_authenticated_account(): void
    {
        $account = $this->createAccount();
        $task = $this->makeTask($account, ['title' => 'Tarea personal']);
        $this->authenticate($account);

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('data.0.id', $task->task_id)
            ->assertJsonPath('data.0.title', 'Tarea personal')
            ->assertJsonPath('data.0.team', null);
    }

    public function test_tasks_index_hides_personal_tasks_of_other_accounts(): void
    {
        $owner = $this->createAccount('owner@test.local');
        $this->makeTask($owner, ['title' => 'Tarea ajena']);
        $other = $this->createAccount('other@test.local');
        $this->authenticate($other);

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_tasks_index_returns_team_tasks_when_account_is_member(): void
    {
        $owner = $this->createAccount('owner@test.local');
        $member = $this->createAccount('member@test.local');
        $team = $this->makeTeam($owner, $member);
        $task = $this->makeTask($owner, ['team_id' => $team->team_id, 'title' => 'Tarea del equipo']);

        $this->authenticate($member);

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('data.0.id', $task->task_id)
            ->assertJsonPath('data.0.team.id', $team->team_id);
    }

    public function test_tasks_index_hides_tasks_of_teams_where_account_is_not_member(): void
    {
        $owner = $this->createAccount('owner@test.local');
        $outsider = $this->createAccount('outsider@test.local');
        $team = $this->makeTeam($owner);
        $this->makeTask($owner, ['team_id' => $team->team_id, 'title' => 'Tarea privada']);

        $this->authenticate($outsider);

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_tasks_index_orders_tasks_by_start_date_then_id(): void
    {
        $account = $this->createAccount();
        $first = $this->makeTask($account, ['title' => 'Tarea primero', 'start_date' => '2026-11-16', 'end_date' => '2026-11-20']);
        $second = $this->makeTask($account, ['title' => 'Tarea segunda', 'start_date' => '2026-11-15', 'end_date' => '2026-11-18']);

        $this->authenticate($account);

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('data.0.id', $second->task_id)
            ->assertJsonPath('data.1.id', $first->task_id);
    }

    public function test_tasks_index_filters_tasks_by_date_range(): void
    {
        $account = $this->createAccount();
        $task = $this->makeTask($account, ['title' => 'Tarea visible', 'start_date' => '2026-11-01', 'end_date' => '2026-11-20']);
        $this->makeTask($account, ['title' => 'Tarea oculta', 'start_date' => '2026-11-21', 'end_date' => '2026-11-25']);

        $this->authenticate($account);

        $this->getJson('/api/tasks?from=2026-11-15&to=2026-11-20')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $task->task_id);
    }

    public function test_tasks_index_filters_tasks_by_status_and_priority(): void
    {
        $account = $this->createAccount();
        $task = $this->makeTask($account, ['title' => 'Tarea completada', 'status' => 'completada', 'priority' => 'alta']);
        $this->makeTask($account, ['title' => 'Tarea pendiente', 'status' => 'pendiente', 'priority' => 'baja']);

        $this->authenticate($account);

        $this->getJson('/api/tasks?status=completada&priority=alta')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $task->task_id);
    }

    public function test_tasks_index_filters_tasks_by_team_project_and_assignee(): void
    {
        $owner = $this->createAccount('owner@test.local');
        $member = $this->createAccount('member@test.local');
        $team = $this->makeTeam($owner, $member);
        $project = Project::factory()->create(['team_id' => $team->team_id, 'name' => 'Diseño']);
        $task = $this->makeTask($owner, ['team_id' => $team->team_id, 'project_id' => $project->project_id]);
        $task->assignees()->attach($member->account_id);

        $this->authenticate($member);

        $this->getJson("/api/tasks?team_id={$team->team_id}&project_id={$project->project_id}&assignee_id={$member->account_id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $task->task_id);
    }

    public function test_tasks_index_returns_validation_errors_for_invalid_filters(): void
    {
        $account = $this->createAccount();
        $this->authenticate($account);

        $this->getJson('/api/tasks?from=2026-13-45')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['from']);

        $this->getJson('/api/tasks?status=iniciada')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->getJson('/api/tasks?priority=urgente')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_tasks_store_creates_personal_task_with_defaults(): void
    {
        $account = $this->createAccount();
        $this->authenticate($account);

        $this->postJson('/api/tasks', [
            'title' => 'Tarea minima',
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-20',
            'status' => 'pendiente',
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Tarea minima')
            ->assertJsonPath('data.priority', 'media')
            ->assertJsonPath('data.team', null)
            ->assertJsonPath('data.project', null);

        $this->assertDatabaseHas('task', [
            'title' => 'Tarea minima',
            'created_by' => $account->account_id,
            'team_id' => null,
            'priority' => 'media',
        ]);
    }

    public function test_tasks_store_creates_task_with_team_project_assignees_and_subtasks(): void
    {
        $owner = $this->createAccount('owner@test.local');
        $member = $this->createAccount('member@test.local', 'Luis Perez');
        $team = $this->makeTeam($owner, $member);
        $project = Project::factory()->create(['team_id' => $team->team_id]);

        $this->authenticate($owner);

        $this->postJson('/api/tasks', $this->taskPayload([
            'team_id' => $team->team_id,
            'project_id' => $project->project_id,
            'assignee_ids' => [$owner->account_id, $member->account_id],
            'subtasks' => [
                ['title' => 'Revisar entregables'],
                ['title' => 'Enviar avance', 'is_done' => true],
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.team.id', $team->team_id)
            ->assertJsonPath('data.project.id', $project->project_id)
            ->assertJsonCount(2, 'data.assignees')
            ->assertJsonPath('data.subtasks.0.title', 'Revisar entregables')
            ->assertJsonPath('data.subtasks.0.is_done', false)
            ->assertJsonPath('data.subtasks.1.is_done', true);

        $task = Task::where('title', 'Preparar el tablero')->firstOrFail();
        $this->assertSame($project->project_id, $task->project_id);
        $this->assertCount(2, $task->assignees);
        $this->assertCount(2, $task->subtasks);
    }

    public function test_tasks_store_sets_completed_at_when_status_is_completada(): void
    {
        $account = $this->createAccount();
        $this->authenticate($account);

        $this->postJson('/api/tasks', $this->taskPayload(['status' => 'completada']))
            ->assertCreated()
            ->assertJsonPath('data.status', 'completada');

        $this->assertNotNull(Task::firstOrFail()->completed_at);
    }

    public function test_tasks_store_rejects_missing_required_fields_and_invalid_dates(): void
    {
        $account = $this->createAccount();
        $this->authenticate($account);

        $this->postJson('/api/tasks', ['title' => 'Tarea sin fechas'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'end_date', 'status']);

        $this->postJson('/api/tasks', $this->taskPayload(['start_date' => '2026-11-20', 'end_date' => '2026-11-10']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);

        $this->postJson('/api/tasks', $this->taskPayload(['start_date' => '2026-13-15']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_tasks_store_rejects_subtasks_missing_title(): void
    {
        $account = $this->createAccount();
        $this->authenticate($account);

        $this->postJson('/api/tasks', $this->taskPayload(['subtasks' => [
            ['is_done' => false],
        ]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subtasks.0.title']);
    }

    public function test_tasks_store_rejects_title_longer_than_120_characters(): void
    {
        $account = $this->createAccount();
        $this->authenticate($account);

        $this->postJson('/api/tasks', $this->taskPayload(['title' => str_repeat('a', 121)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_tasks_store_rejects_team_where_account_is_not_member(): void
    {
        $owner = $this->createAccount('owner@test.local');
        $outsider = $this->createAccount('outsider@test.local');
        $team = $this->makeTeam($owner);

        $this->authenticate($outsider);

        $this->postJson('/api/tasks', $this->taskPayload(['team_id' => $team->team_id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['team_id']);
    }

    public function test_tasks_store_rejects_project_from_another_team(): void
    {
        $owner = $this->createAccount('owner@test.local');
        $team = $this->makeTeam($owner);
        $otherTeam = $this->makeTeam($owner);
        $project = Project::factory()->create(['team_id' => $otherTeam->team_id]);

        $this->authenticate($owner);

        $this->postJson('/api/tasks', $this->taskPayload([
            'team_id' => $team->team_id,
            'project_id' => $project->project_id,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['project_id']);
    }

    public function test_tasks_store_rejects_project_without_team(): void
    {
        $owner = $this->createAccount('owner@test.local');
        $team = $this->makeTeam($owner);
        $project = Project::factory()->create(['team_id' => $team->team_id]);

        $this->authenticate($owner);

        $this->postJson('/api/tasks', $this->taskPayload([
            'team_id' => null,
            'project_id' => $project->project_id,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['project_id']);
    }

    public function test_tasks_store_validates_subtask_assignee_membership(): void
    {
        $owner = $this->createAccount('owner@test.local');
        $member = $this->createAccount('member@test.local');
        $outsider = $this->createAccount('outsider@test.local');
        $team = $this->makeTeam($owner, $member);

        $this->authenticate($owner);

        $this->postJson('/api/tasks', $this->taskPayload([
            'team_id' => $team->team_id,
            'subtasks' => [
                ['title' => 'Asignada al equipo', 'assignee_id' => $member->account_id],
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.subtasks.0.assignee.id', $member->account_id);

        $this->postJson('/api/tasks', $this->taskPayload([
            'team_id' => $team->team_id,
            'subtasks' => [
                ['title' => 'Asignada a un ajeno', 'assignee_id' => $outsider->account_id],
            ],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subtasks.0.assignee_id']);
    }

    public function test_tasks_store_rejects_assignee_outside_the_team(): void
    {
        $owner = $this->createAccount('owner@test.local');
        $outsider = $this->createAccount('outsider@test.local');
        $team = $this->makeTeam($owner);

        $this->authenticate($owner);

        $this->postJson('/api/tasks', $this->taskPayload([
            'team_id' => $team->team_id,
            'assignee_ids' => [$outsider->account_id],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['assignee_ids']);
    }

    public function test_tasks_show_returns_task_for_owner(): void
    {
        $account = $this->createAccount();
        $task = $this->makeTask($account, ['title' => 'Tarea a mostrar']);
        $this->authenticate($account);

        $this->getJson("/api/tasks/{$task->task_id}")
            ->assertOk()
            ->assertJsonPath('data.id', $task->task_id)
            ->assertJsonPath('data.title', 'Tarea a mostrar');
    }

    public function test_tasks_show_returns_404_when_task_does_not_exist(): void
    {
        $account = $this->createAccount();
        $this->authenticate($account);

        $this->getJson('/api/tasks/999999')->assertNotFound();
    }

    public function test_tasks_show_returns_404_when_task_belongs_to_another_account(): void
    {
        $owner = $this->createAccount('owner2@test.local');
        $task = $this->makeTask($owner, ['title' => 'Tarea ajena']);
        $other = $this->createAccount('other2@test.local');
        $this->authenticate($other);

        $this->getJson("/api/tasks/{$task->task_id}")->assertNotFound();

        $this->assertDatabaseHas('task', ['task_id' => $task->task_id]);
    }

    public function test_tasks_update_modifies_task_and_returns_ok(): void
    {
        $account = $this->createAccount();
        $task = $this->makeTask($account, ['title' => 'Tarea original', 'status' => 'pendiente', 'priority' => 'media']);
        $this->authenticate($account);

        $this->putJson("/api/tasks/{$task->task_id}", $this->taskPayload([
            'title' => 'Tarea actualizada',
            'status' => 'completada',
            'priority' => 'alta',
            'description' => 'Descripcion actualizada',
        ]))
            ->assertOk()
            ->assertJsonPath('data.id', $task->task_id)
            ->assertJsonPath('data.status', 'completada')
            ->assertJsonPath('data.status_label', 'Completada')
            ->assertJsonPath('data.priority', 'alta')
            ->assertJsonPath('data.priority_label', 'Alta');

        $this->assertDatabaseHas('task', [
            'task_id' => $task->task_id,
            'title' => 'Tarea actualizada',
            'status' => 'completada',
        ]);
        $this->assertNotNull($task->refresh()->completed_at);
    }

    public function test_tasks_update_clears_completed_at_when_task_is_reopened(): void
    {
        $account = $this->createAccount();
        $task = $this->makeTask($account, ['status' => 'completada']);
        $this->authenticate($account);

        $this->putJson("/api/tasks/{$task->task_id}", $this->taskPayload(['status' => 'en_progreso']))
            ->assertOk();

        $this->assertNull($task->refresh()->completed_at);
    }

    public function test_tasks_update_validates_required_fields_and_dates(): void
    {
        $account = $this->createAccount();
        $task = $this->makeTask($account, ['title' => 'Tarea para actualizar']);
        $this->authenticate($account);

        $this->putJson("/api/tasks/{$task->task_id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'start_date', 'end_date', 'status']);

        $this->putJson("/api/tasks/{$task->task_id}", $this->taskPayload(['start_date' => '2026-11-20', 'end_date' => '2026-11-10']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_tasks_update_returns_404_when_task_does_not_exist(): void
    {
        $account = $this->createAccount();
        $this->authenticate($account);

        $this->putJson('/api/tasks/999999', $this->taskPayload())->assertNotFound();
    }

    public function test_tasks_update_returns_404_when_task_belongs_to_another_account(): void
    {
        $owner = $this->createAccount('owner3@test.local');
        $task = $this->makeTask($owner);
        $other = $this->createAccount('other3@test.local');
        $this->authenticate($other);

        $this->putJson("/api/tasks/{$task->task_id}", $this->taskPayload())->assertNotFound();
    }

    public function test_tasks_update_replaces_subtasks(): void
    {
        $account = $this->createAccount();
        $task = $this->makeTask($account, ['title' => 'Tarea con subtareas']);
        Subtask::factory()->create(['task_id' => $task->task_id, 'title' => 'Subtarea vieja']);
        $this->authenticate($account);

        $this->putJson("/api/tasks/{$task->task_id}", $this->taskPayload([
            'subtasks' => [
                ['title' => 'Subtarea uno', 'is_done' => true],
                ['title' => 'Subtarea dos'],
            ],
        ]))
            ->assertOk()
            ->assertJsonCount(2, 'data.subtasks')
            ->assertJsonPath('data.subtasks.0.is_done', true);

        $this->assertDatabaseMissing('subtask', ['title' => 'Subtarea vieja']);
        $this->assertDatabaseHas('subtask', ['task_id' => $task->task_id, 'title' => 'Subtarea dos', 'is_done' => false]);
    }

    public function test_tasks_destroy_deletes_task_for_owner(): void
    {
        $account = $this->createAccount();
        $task = $this->makeTask($account, ['title' => 'Tarea a eliminar']);
        $this->authenticate($account);

        $this->deleteJson("/api/tasks/{$task->task_id}")->assertNoContent();

        $this->assertSoftDeleted('task', ['task_id' => $task->task_id]);
    }

    public function test_tasks_destroy_returns_404_when_task_does_not_exist(): void
    {
        $account = $this->createAccount();
        $this->authenticate($account);

        $this->deleteJson('/api/tasks/999999')->assertNotFound();
    }

    public function test_tasks_destroy_returns_404_when_task_belongs_to_another_account(): void
    {
        $owner = $this->createAccount('owner4@test.local');
        $task = $this->makeTask($owner, ['title' => 'Tarea ajena a borrar']);
        $other = $this->createAccount('other4@test.local');
        $this->authenticate($other);

        $this->deleteJson("/api/tasks/{$task->task_id}")->assertNotFound();

        $this->assertDatabaseHas('task', ['task_id' => $task->task_id, 'deleted_at' => null]);
    }

    public function test_teams_index_returns_only_teams_of_the_authenticated_account(): void
    {
        $owner = $this->createAccount('owner@test.local');
        $member = $this->createAccount('member@test.local');
        $team = $this->makeTeam($owner, $member);
        $this->makeTeam($owner);

        $this->authenticate($member);

        $this->getJson('/api/teams')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $team->team_id)
            ->assertJsonPath('data.0.role', 'member');
    }

    public function test_projects_index_returns_projects_of_my_teams_and_filters_by_team(): void
    {
        $owner = $this->createAccount('owner@test.local');
        $member = $this->createAccount('member@test.local');
        $team = $this->makeTeam($owner, $member);
        $otherTeam = $this->makeTeam($owner, $member);
        $project = Project::factory()->create(['team_id' => $team->team_id]);
        Project::factory()->create(['team_id' => $otherTeam->team_id]);

        $this->authenticate($member);

        $this->getJson('/api/projects')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/projects?team_id={$team->team_id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $project->project_id);
    }

    public function test_accounts_index_returns_members_of_my_teams_and_myself(): void
    {
        $owner = $this->createAccount('owner@test.local');
        $member = $this->createAccount('member@test.local', 'Luis Perez');
        $outsider = $this->createAccount('outsider@test.local', 'Pedro Ruiz');
        $team = $this->makeTeam($owner, $member);

        $this->authenticate($owner);

        $this->getJson('/api/accounts')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $member->account_id, 'name' => 'Luis Perez'])
            ->assertJsonMissing(['id' => $outsider->account_id]);
    }
}
