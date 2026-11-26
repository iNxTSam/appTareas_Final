<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(PreventRequestForgery::class);
        config(['services.backend.url' => 'http://backend.test']);
        Http::preventStrayRequests();
    }

    private function sessionData(): array
    {
        return [
            'api_token' => 'test-token',
            'api_user' => [
                'id' => 1,
                'name' => 'Ana Torres',
                'email' => 'ana@test.local',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function apiTask(): array
    {
        return [
            'id' => 42,
            'title' => 'Preparar el tablero',
            'description' => 'Tareas de la semana',
            'team' => ['id' => 7, 'name' => 'Producto'],
            'project' => ['id' => 9, 'name' => 'Diseño', 'color' => '#702632'],
            'status' => 'en_progreso',
            'status_label' => 'En progreso',
            'priority' => 'alta',
            'priority_label' => 'Alta',
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-20',
            'completed_at' => null,
            'position' => 0,
            'created_by' => ['id' => 1, 'name' => 'Ana Torres'],
            'assignees' => [
                ['id' => 3, 'name' => 'Ana Torres'],
            ],
            'subtasks' => [
                [
                    'id' => 5,
                    'title' => 'Revisar entregables',
                    'is_done' => false,
                    'position' => 1,
                    'assignee' => null,
                ],
            ],
            'created_at' => '2026-11-15T10:00:00+00:00',
            'updated_at' => '2026-11-15T10:00:00+00:00',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function uiTask(): array
    {
        return [
            'id' => 42,
            'title' => 'Preparar el tablero',
            'description' => 'Tareas de la semana',
            'category' => 'Diseño',
            'project_id' => 9,
            'status' => 'en_progreso',
            'status_label' => 'En progreso',
            'priority' => 'high',
            'priority_label' => 'Alta',
            'team' => 'Producto',
            'team_id' => 7,
            'responsible_id' => 3,
            'responsible_name' => 'Ana Torres',
            'assignees' => [
                ['id' => 3, 'name' => 'Ana Torres'],
            ],
            'subtasks' => [
                [
                    'id' => 5,
                    'text' => 'Revisar entregables',
                    'done' => false,
                    'responsible_id' => '',
                    'responsible_name' => '',
                ],
            ],
            'start_date' => '2026-11-15',
            'due_date' => '2026-11-20',
            'created_at' => '2026-11-15T10:00:00+00:00',
            'updated_at' => '2026-11-15T10:00:00+00:00',
        ];
    }

    public function test_task_listing_forwards_filters_and_session_token(): void
    {
        Http::fake([
            'http://backend.test/api/tasks*' => Http::response(['data' => []]),
        ]);

        $this->withSession($this->sessionData())
            ->getJson(route('tasks.index', [
                'from' => '2026-11-15',
                'to' => '2026-11-20',
                'status' => 'pendiente',
                'priority' => 'high',
            ]))
            ->assertOk()
            ->assertExactJson(['data' => []]);

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_starts_with($request->url(), 'http://backend.test/api/tasks?')
            && str_contains($request->url(), 'status=pendiente')
            && str_contains($request->url(), 'priority=alta')
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_task_listing_maps_api_fields_to_the_ui_contract(): void
    {
        Http::fake([
            'http://backend.test/api/tasks*' => Http::response(['data' => [$this->apiTask()]]),
        ]);

        $this->withSession($this->sessionData())
            ->getJson(route('tasks.index'))
            ->assertOk()
            ->assertExactJson(['data' => [$this->uiTask()]]);
    }

    public function test_task_creation_forwards_the_api_contract_and_maps_the_response(): void
    {
        Http::fake([
            'http://backend.test/api/tasks' => Http::response(['data' => $this->apiTask()], 201),
        ]);

        $this->withSession($this->sessionData())
            ->postJson(route('tasks.store'), [
                'title' => 'Preparar el tablero',
                'description' => 'Tareas de la semana',
                'team_id' => 7,
                'project_id' => 9,
                'status' => 'en_progreso',
                'priority' => 'high',
                'start_date' => '2026-11-15',
                'due_date' => '2026-11-20',
                'responsible_id' => '3',
                'subtasks' => [
                    ['title' => 'Revisar entregables', 'is_done' => false],
                ],
            ])
            ->assertCreated()
            ->assertExactJson(['data' => $this->uiTask()]);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'http://backend.test/api/tasks'
            && $request->data() === [
                'title' => 'Preparar el tablero',
                'description' => 'Tareas de la semana',
                'team_id' => 7,
                'project_id' => 9,
                'start_date' => '2026-11-15',
                'end_date' => '2026-11-20',
                'status' => 'en_progreso',
                'priority' => 'alta',
                'assignee_ids' => [3],
                'subtasks' => [
                    ['title' => 'Revisar entregables', 'is_done' => false],
                ],
            ]
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_task_creation_defaults_missing_start_date_to_the_due_date(): void
    {
        Http::fake([
            'http://backend.test/api/tasks' => Http::response(['data' => $this->apiTask()], 201),
        ]);

        $this->withSession($this->sessionData())
            ->postJson(route('tasks.store'), [
                'title' => 'Preparar el tablero',
                'status' => 'pendiente',
                'priority' => 'medium',
                'due_date' => '2026-11-20',
            ])
            ->assertCreated();

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->data()['start_date'] === '2026-11-20'
            && $request->data()['end_date'] === '2026-11-20'
            && $request->data()['priority'] === 'media'
            && $request->data()['team_id'] === null
            && $request->data()['assignee_ids'] === []);
    }

    public function test_task_creation_defaults_missing_dates_to_today(): void
    {
        $this->travelTo(Carbon::parse('2026-11-15 09:00:00'));

        Http::fake([
            'http://backend.test/api/tasks' => Http::response(['data' => $this->apiTask()], 201),
        ]);

        $this->withSession($this->sessionData())
            ->postJson(route('tasks.store'), [
                'title' => 'Preparar el tablero',
                'status' => 'pendiente',
                'priority' => 'low',
            ])
            ->assertCreated();

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->data()['start_date'] === '2026-11-15'
            && $request->data()['end_date'] === '2026-11-15'
            && $request->data()['priority'] === 'baja');
    }

    public function test_task_detail_is_fetched_with_the_session_token(): void
    {
        Http::fake([
            'http://backend.test/api/tasks/42' => Http::response(['data' => $this->apiTask()]),
        ]);

        $this->withSession($this->sessionData())
            ->getJson(route('tasks.show', ['task' => 42]))
            ->assertOk()
            ->assertExactJson(['data' => $this->uiTask()]);

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'http://backend.test/api/tasks/42'
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_task_update_and_delete_forward_the_task_id(): void
    {
        Http::fake([
            'http://backend.test/api/tasks/42' => Http::sequence()
                ->push(['data' => $this->apiTask()])
                ->push([], 204),
        ]);

        $this->withSession($this->sessionData())
            ->putJson(route('tasks.update', ['task' => 42]), [
                'title' => 'Preparar el tablero',
                'description' => 'Tareas de la semana',
                'status' => 'en_progreso',
                'priority' => 'high',
                'team_id' => 7,
                'start_date' => '2026-11-15',
                'due_date' => '2026-11-20',
            ])
            ->assertOk()
            ->assertJsonPath('data.priority', 'high');

        $this->withSession($this->sessionData())
            ->deleteJson(route('tasks.destroy', ['task' => 42]))
            ->assertNoContent();

        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && $request->url() === 'http://backend.test/api/tasks/42'
            && $request->data()['title'] === 'Preparar el tablero'
            && $request->data()['end_date'] === '2026-11-20'
            && $request->hasHeader('Authorization', 'Bearer test-token'));
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && $request->url() === 'http://backend.test/api/tasks/42'
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_task_api_connection_failure_returns_a_visible_error(): void
    {
        Http::fake(fn () => throw new ConnectionException('Backend no disponible.'));

        $this->withSession($this->sessionData())
            ->getJson(route('tasks.index'))
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'No se pudo conectar con el servidor de tareas.');
    }

    public function test_task_detail_connection_failure_returns_a_visible_error(): void
    {
        Http::fake(fn () => throw new ConnectionException('Backend no disponible.'));

        $this->withSession($this->sessionData())
            ->getJson(route('tasks.show', ['task' => 42]))
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'No se pudo conectar con el servidor de tareas.');
    }

    public function test_task_delete_connection_failure_returns_a_visible_error(): void
    {
        Http::fake(fn () => throw new ConnectionException('Backend no disponible.'));

        $this->withSession($this->sessionData())
            ->deleteJson(route('tasks.destroy', ['task' => 42]))
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'No se pudo conectar con el servidor de tareas.');
    }

    public function test_panel_remains_usable_when_selector_endpoints_fail(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/api/me')) {
                return Http::response(['user' => $this->sessionData()['api_user']]);
            }

            throw new ConnectionException('Selectores no disponibles.');
        });

        $this->withSession($this->sessionData())
            ->get(route('panel'))
            ->assertOk()
            ->assertSee('Ana Torres')
            ->assertSee('name="team_id"', false);
    }

    public function test_invalid_task_values_are_rejected_before_calling_the_api(): void
    {
        Http::fake();

        $this->withSession($this->sessionData())
            ->postJson(route('tasks.store'), [
                'title' => 'Tarea inválida',
                'status' => 'INICIADO',
                'priority' => 'urgent',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status', 'priority']);

        Http::assertNothingSent();
    }

    public function test_homepage_renders_at_the_named_home_route(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('App Tareas')
            ->assertSee(route('home'), false);
    }

    public function test_panel_renders_the_task_partials_and_api_urls(): void
    {
        Http::fake([
            'http://backend.test/api/me' => Http::response([
                'user' => $this->sessionData()['api_user'],
            ]),
            'http://backend.test/api/teams' => Http::response([
                'data' => [['id' => 7, 'name' => 'Producto', 'role' => 'owner', 'members_count' => 3]],
            ]),
            'http://backend.test/api/projects' => Http::response([
                'data' => [['id' => 9, 'team_id' => 7, 'name' => 'Diseño', 'color' => '#702632']],
            ]),
            'http://backend.test/api/accounts' => Http::response([
                'data' => [['id' => 3, 'name' => 'Ana Torres', 'email' => 'ana@test.local']],
            ]),
        ]);

        $this->withSession($this->sessionData())
            ->get(route('panel'))
            ->assertOk()
            ->assertSee('id="task-app"', false)
            ->assertSee('data-tasks-url="'.route('tasks.index').'"', false)
            ->assertSee(route('tasks.show', ['task' => '__task__']), false)
            ->assertSee('Vista de tarjetas')
            ->assertSee('name="priority"', false)
            ->assertSee('name="team_id"', false)
            ->assertSee('name="project_id"', false)
            ->assertSee('en_progreso');
    }
}
