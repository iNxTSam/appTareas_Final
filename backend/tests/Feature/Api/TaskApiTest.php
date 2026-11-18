<?php

namespace Tests\Feature\Api;

use App\Models\Task;
use App\Models\User;
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

    private function createUser(string $email = 'ana@test.local'): User
    {
        return User::create([
            'name' => 'Ana Torres',
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

    private function taskPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Preparar el tablero',
            'description' => 'Tareas de la semana',
            'category' => 'Diseño',
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-20',
            'status' => 'pendiente',
            'priority' => 'normal',
            'team' => 'Producto',
            'responsible_id' => 'ana',
            'responsible_name' => 'Ana Torres',
            'subtasks' => [
                ['text' => 'Revisar entregables', 'done' => false],
            ],
        ], $overrides);
    }

    private function createTask(User $user, array $overrides = []): Task
    {
        return Task::factory()->for($user)->create($overrides);
    }

    public function test_tasks_index_requires_authentication(): void
    {
        $this->getJson('/api/tasks')->assertUnauthorized();
    }

    public function test_tasks_store_requires_authentication(): void
    {
        $this->postJson('/api/tasks', $this->taskPayload())->assertUnauthorized();
    }

    public function test_tasks_index_returns_empty_data_when_authenticated_user_has_no_tasks(): void
    {
        $user = $this->createUser();
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_tasks_index_returns_only_tasks_of_the_authenticated_user(): void
    {
        $owner = $this->createUser('owner@test.local');
        $task = $this->createTask($owner, ['name' => 'Tarea del owner']);

        $token = $this->loginFor($owner->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('data.0.id', $task->id);
    }

    public function test_tasks_index_orders_tasks_by_start_date_then_id(): void
    {
        $user = $this->createUser();
        $first = $this->createTask($user, ['name' => 'Tarea primero', 'start_date' => '2026-11-16', 'end_date' => '2026-11-20']);
        $second = $this->createTask($user, ['name' => 'Tarea segunda', 'start_date' => '2026-11-15', 'end_date' => '2026-11-18']);

        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('data.1.id', $first->id);
    }

    public function test_tasks_index_filters_tasks_by_end_date_from(): void
    {
        $user = $this->createUser();
        $task = $this->createTask($user, ['name' => 'Tarea visible', 'start_date' => '2026-11-01', 'end_date' => '2026-11-20']);
        $this->createTask($user, ['name' => 'Tarea oculta', 'start_date' => '2026-11-01', 'end_date' => '2026-11-10']);

        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->getJson('/api/tasks?from=2026-11-15')
            ->assertOk()
            ->assertJsonPath('data.0.id', $task->id);
    }

    public function test_tasks_index_filters_tasks_by_start_date_to(): void
    {
        $user = $this->createUser();
        $task = $this->createTask($user, ['name' => 'Tarea visible', 'start_date' => '2026-11-15', 'end_date' => '2026-11-18']);
        $this->createTask($user, ['name' => 'Tarea oculta', 'start_date' => '2026-11-20', 'end_date' => '2026-11-25']);

        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->getJson('/api/tasks?to=2026-11-16')
            ->assertOk()
            ->assertJsonPath('data.0.id', $task->id);
    }

    public function test_tasks_index_filters_tasks_by_status(): void
    {
        $user = $this->createUser();
        $task = $this->createTask($user, ['name' => 'Tarea completada', 'status' => 'completada']);
        $this->createTask($user, ['name' => 'Tarea pendiente', 'status' => 'pendiente']);

        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->getJson('/api/tasks?status=completada')
            ->assertOk()
            ->assertJsonPath('data.0.id', $task->id);
    }

    public function test_tasks_index_filters_tasks_by_priority(): void
    {
        $user = $this->createUser();
        $task = $this->createTask($user, ['name' => 'Tarea importante', 'priority' => 'importante']);
        $this->createTask($user, ['name' => 'Tarea normal', 'priority' => 'normal']);

        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->getJson('/api/tasks?priority=importante')
            ->assertOk()
            ->assertJsonPath('data.0.id', $task->id);
    }

    public function test_tasks_index_returns_validation_errors_for_invalid_filters(): void
    {
        $user = $this->createUser();
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

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

    public function test_tasks_store_creates_task_and_returns_created_response(): void
    {
        $user = $this->createUser();
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $response = $this->postJson('/api/tasks', $this->taskPayload());
        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'description',
                'category',
                'start_date',
                'end_date',
                'status',
                'status_label',
                'priority',
                'priority_label',
                'team',
                'responsible_id',
                'responsible_name',
                'subtasks',
                'created_at',
                'updated_at',
            ],
        ]);

        $task = Task::where('user_id', $user->id)->where('name', 'Preparar el tablero')->firstOrFail();

        $this->assertSame($user->id, $task->user_id);
        $this->assertSame('Tareas de la semana', $task->description);
        $this->assertSame('Diseño', $task->category);
        $this->assertSame('2026-11-15', $task->start_date->format('Y-m-d'));
        $this->assertSame('2026-11-20', $task->end_date->format('Y-m-d'));
        $this->assertSame('pendiente', $task->status->value);
        $this->assertSame('normal', $task->priority->value);
        $this->assertSame('Producto', $task->team);
        $this->assertSame('ana', $task->responsible_id);
        $this->assertSame('Ana Torres', $task->responsible_name);
        $this->assertEquals([['text' => 'Revisar entregables', 'done' => false]], $task->subtasks);
    }

    public function test_tasks_store_creates_task_with_defaults_for_optional_fields(): void
    {
        $user = $this->createUser();
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->postJson('/api/tasks', [
            'name' => 'Tarea minima',
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-20',
            'status' => 'pendiente',
        ])
            ->assertCreated()
            ->assertJsonPath('data.priority', 'normal');
    }

    public function test_tasks_store_rejects_missing_required_fields_and_invalid_dates(): void
    {
        $user = $this->createUser();
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->postJson('/api/tasks', [
            'name' => 'Tarea sin fechas',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'end_date', 'status']);

        $this->postJson('/api/tasks', $this->taskPayload(['start_date' => '2026-11-20', 'end_date' => '2026-11-10']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);

        $this->postJson('/api/tasks', $this->taskPayload(['start_date' => '2026-13-15', 'end_date' => '2026-11-20']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_tasks_store_rejects_subtasks_missing_text(): void
    {
        $user = $this->createUser();
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->postJson('/api/tasks', $this->taskPayload(['subtasks' => [
            ['done' => false],
        ]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subtasks.0.text']);
    }

    public function test_tasks_store_rejects_task_name_longer_than_120_characters(): void
    {
        $user = $this->createUser();
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->postJson('/api/tasks', $this->taskPayload(['name' => str_repeat('a', 121)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_tasks_show_returns_task_for_owner(): void
    {
        $user = $this->createUser();
        $task = $this->createTask($user, ['name' => 'Tarea a mostrar']);
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->getJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.name', 'Tarea a mostrar');
    }

    public function test_tasks_show_returns_404_when_task_does_not_exist(): void
    {
        $user = $this->createUser();
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->getJson('/api/tasks/999999')->assertNotFound();
    }

    public function test_tasks_show_returns_404_when_task_belongs_to_another_user(): void
    {
        $owner = $this->createUser('owner2@test.local');
        $task = $this->createTask($owner, ['name' => 'Tarea ajena']);
        $other = $this->createUser('other2@test.local');
        $token = $this->loginFor($other->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->getJson("/api/tasks/{$task->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_tasks_update_modifies_task_and_returns_ok(): void
    {
        $user = $this->createUser();
        $task = $this->createTask($user, ['name' => 'Tarea original', 'status' => 'pendiente', 'priority' => 'normal', 'start_date' => '2026-11-15', 'end_date' => '2026-11-20']);
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->putJson("/api/tasks/{$task->id}", $this->taskPayload([
            'name' => 'Tarea actualizada',
            'status' => 'completada',
            'priority' => 'importante',
            'description' => 'Descripcion actualizada',
        ]))
            ->assertOk()
            ->assertJsonPath('data.id', $task->id)
            ->assertJsonPath('data.status', 'completada')
            ->assertJsonPath('data.status_label', 'Completada')
            ->assertJsonPath('data.priority', 'importante')
            ->assertJsonPath('data.priority_label', 'Importante');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'name' => 'Tarea actualizada', 'status' => 'completada']);
    }

    public function test_tasks_update_validates_required_fields_and_dates(): void
    {
        $user = $this->createUser();
        $task = $this->createTask($user, ['name' => 'Tarea para actualizar', 'start_date' => '2026-11-15', 'end_date' => '2026-11-20', 'status' => 'pendiente']);
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->putJson("/api/tasks/{$task->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'start_date', 'end_date', 'status']);

        $this->putJson("/api/tasks/{$task->id}", $this->taskPayload(['start_date' => '2026-11-20', 'end_date' => '2026-11-10']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_tasks_update_returns_404_when_task_does_not_exist(): void
    {
        $user = $this->createUser();
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->putJson('/api/tasks/999999', $this->taskPayload())->assertNotFound();
    }

    public function test_tasks_update_returns_404_when_task_belongs_to_another_user(): void
    {
        $owner = $this->createUser('owner3@test.local');
        $task = $this->createTask($owner);
        $other = $this->createUser('other3@test.local');
        $token = $this->loginFor($other->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->putJson("/api/tasks/{$task->id}", $this->taskPayload())->assertNotFound();
    }

    public function test_tasks_update_updates_subtasks_when_task_is_updated(): void
    {
        $user = $this->createUser();
        $task = $this->createTask($user, [
            'name' => 'Tarea con subtareas',
            'subtasks' => [
                ['text' => 'Subtarea uno', 'done' => false],
            ],
        ]);
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->putJson("/api/tasks/{$task->id}", $this->taskPayload([
            'subtasks' => [
                ['text' => 'Subtarea uno', 'done' => true],
                ['text' => 'Subtarea dos', 'done' => false],
            ],
        ]))
            ->assertOk()
            ->assertJsonPath('data.subtasks.0.done', true);

        $task->refresh();

        $this->assertEquals([['text' => 'Subtarea uno', 'done' => true], ['text' => 'Subtarea dos', 'done' => false]], $task->subtasks);
    }

    public function test_tasks_destroy_deletes_task_for_owner(): void
    {
        $user = $this->createUser();
        $task = $this->createTask($user, ['name' => 'Tarea a eliminar']);
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->deleteJson("/api/tasks/{$task->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_tasks_destroy_returns_404_when_task_does_not_exist(): void
    {
        $user = $this->createUser();
        $token = $this->loginFor($user->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->deleteJson('/api/tasks/999999')->assertNotFound();
    }

    public function test_tasks_destroy_returns_404_when_task_belongs_to_another_user(): void
    {
        $owner = $this->createUser('owner4@test.local');
        $task = $this->createTask($owner, ['name' => 'Tarea ajena a borrar']);
        $other = $this->createUser('other4@test.local');
        $token = $this->loginFor($other->email);
        $this->withHeader('Authorization', "Bearer {$token}");

        $this->deleteJson("/api/tasks/{$task->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }
}
