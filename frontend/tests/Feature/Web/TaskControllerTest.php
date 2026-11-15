<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Client\ConnectionException;
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
            && str_contains($request->url(), 'priority=high')
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_task_creation_forwards_validated_fields_and_session_token(): void
    {
        Http::fake([
            'http://backend.test/api/tasks' => Http::response(['id' => 42], 201),
        ]);
        $task = [
            'title' => 'Preparar el tablero',
            'description' => 'Tareas de la semana',
            'category' => 'Diseño',
            'status' => 'en_progreso',
            'priority' => 'high',
            'team' => 'Producto',
            'start_date' => '2026-11-15',
            'due_date' => '2026-11-20',
        ];

        $this->withSession($this->sessionData())
            ->postJson(route('tasks.store'), $task)
            ->assertCreated()
            ->assertExactJson(['id' => 42]);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'http://backend.test/api/tasks'
            && $request->data() === $task
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_task_detail_is_fetched_with_the_session_token(): void
    {
        Http::fake([
            'http://backend.test/api/tasks/42' => Http::response([
                'data' => ['id' => 42, 'title' => 'Preparar el tablero'],
            ]),
        ]);

        $this->withSession($this->sessionData())
            ->getJson(route('tasks.show', ['task' => 42]))
            ->assertOk()
            ->assertJsonPath('data.id', 42);

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'http://backend.test/api/tasks/42'
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_task_update_and_delete_forward_the_task_id(): void
    {
        Http::fake([
            'http://backend.test/api/tasks/42' => Http::sequence()
                ->push(['id' => 42, 'status' => 'completada'])
                ->push([], 204),
        ]);

        $this->withSession($this->sessionData())
            ->putJson(route('tasks.update', ['task' => 42]), [
                'title' => 'Preparar el tablero',
                'description' => '',
                'category' => 'Diseño',
                'status' => 'completada',
                'priority' => 'medium',
                'team' => 'Producto',
                'start_date' => '2026-11-15',
                'due_date' => '2026-11-20',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'completada');

        $this->withSession($this->sessionData())
            ->deleteJson(route('tasks.destroy', ['task' => 42]))
            ->assertNoContent();

        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && $request->url() === 'http://backend.test/api/tasks/42'
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
        ]);

        $this->withSession($this->sessionData())
            ->get(route('panel'))
            ->assertOk()
            ->assertSee('id="task-app"', false)
            ->assertSee('data-tasks-url="'.route('tasks.index').'"', false)
            ->assertSee(route('tasks.show', ['task' => '__task__']), false)
            ->assertSee('Vista de tablero')
            ->assertSee('name="priority"', false)
            ->assertSee('name="team"', false)
            ->assertSee('en_progreso');
    }
}
