<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Seed the demo data used by the v2 prototype.
     */
    public function run(): void
    {
        $accounts = collect([
            ['name' => 'Juan Pérez', 'email' => 'juan@apptareas.local'],
            ['name' => 'Ana Torres', 'email' => 'ana@apptareas.local'],
            ['name' => 'Luis Pérez', 'email' => 'luis@apptareas.local'],
            ['name' => 'Camila Rojas', 'email' => 'camila@apptareas.local'],
            ['name' => 'Mateo Díaz', 'email' => 'mateo@apptareas.local'],
        ])->mapWithKeys(fn (array $data): array => [
            $data['email'] => Account::create($data + ['password' => 'password']),
        ]);

        $juan = $accounts['juan@apptareas.local'];
        $ana = $accounts['ana@apptareas.local'];
        $luis = $accounts['luis@apptareas.local'];
        $camila = $accounts['camila@apptareas.local'];
        $mateo = $accounts['mateo@apptareas.local'];

        $producto = Team::create([
            'name' => 'Producto',
            'slug' => 'producto',
            'owner_id' => $juan->account_id,
        ]);
        $producto->members()->attach($juan->account_id, ['role' => 'owner']);
        $producto->members()->attach([$ana->account_id, $luis->account_id, $camila->account_id], ['role' => 'member']);

        $marketing = Team::create([
            'name' => 'Marketing',
            'slug' => 'marketing',
            'owner_id' => $ana->account_id,
        ]);
        $marketing->members()->attach($ana->account_id, ['role' => 'owner']);
        $marketing->members()->attach([$juan->account_id, $mateo->account_id], ['role' => 'member']);

        $diseño = Project::create(['team_id' => $producto->team_id, 'name' => 'Diseño', 'color' => '#702632']);
        $desarrollo = Project::create(['team_id' => $producto->team_id, 'name' => 'Desarrollo', 'color' => '#912F40']);
        $campaña = Project::create(['team_id' => $marketing->team_id, 'name' => 'Campaña', 'color' => '#40434E']);

        $this->task($producto, $diseño, $juan, 'Rediseñar el tablero', 'pendiente', 'alta', '2026-11-23', '2026-11-27', [$ana, $luis], [
            ['title' => 'Wireframes', 'is_done' => true],
            ['title' => 'Pruebas con usuarios', 'is_done' => false],
            ['title' => 'Entrega final', 'is_done' => false],
        ]);
        $this->task($producto, $desarrollo, $ana, 'Corregir filtros de tareas', 'en_progreso', 'alta', '2026-11-24', '2026-11-26', [$ana], [
            ['title' => 'Filtro por estado', 'is_done' => true],
            ['title' => 'Filtro por responsable', 'is_done' => false],
        ]);
        $this->task($producto, $desarrollo, $luis, 'Documentar la API v2', 'en_progreso', 'media', '2026-11-22', '2026-11-30', [$luis, $juan]);
        $this->task($producto, $diseño, $camila, 'Kit de componentes', 'completada', 'media', '2026-11-18', '2026-11-22', [$camila], [
            ['title' => 'Botones', 'is_done' => true],
            ['title' => 'Tarjetas', 'is_done' => true],
        ]);
        $this->task($producto, $diseño, $juan, 'Revisar contraste accesible', 'pendiente', 'baja', '2026-11-25', '2026-11-28', [$juan, $camila]);
        $this->task($marketing, $campaña, $ana, 'Calendario de contenidos', 'pendiente', 'media', '2026-11-24', '2026-11-29', [$ana, $mateo], [
            ['title' => 'Instagram', 'is_done' => false],
            ['title' => 'LinkedIn', 'is_done' => false],
        ]);
        $this->task($marketing, $campaña, $mateo, 'Landing de lanzamiento', 'en_progreso', 'alta', '2026-11-20', '2026-11-27', [$mateo]);

        Task::create([
            'team_id' => null,
            'project_id' => null,
            'created_by' => $juan->account_id,
            'title' => 'Preparar la presentación del sprint',
            'description' => 'Tarea personal de Juan.',
            'status' => 'pendiente',
            'priority' => 'media',
            'start_date' => '2026-11-25',
            'end_date' => '2026-11-26',
            'position' => 0,
        ]);
    }

    /**
     * @param  array<int, Account>  $assignees
     * @param  array<int, array{title: string, is_done?: bool}>  $subtasks
     */
    private function task(
        Team $team,
        Project $project,
        Account $creator,
        string $title,
        string $status,
        string $priority,
        string $startDate,
        string $endDate,
        array $assignees = [],
        array $subtasks = [],
    ): Task {
        $task = Task::create([
            'team_id' => $team->team_id,
            'project_id' => $project->project_id,
            'created_by' => $creator->account_id,
            'title' => $title,
            'status' => $status,
            'priority' => $priority,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'completed_at' => $status === 'completada' ? now() : null,
            'position' => 0,
        ]);

        $task->assignees()->sync(array_map(
            static fn (Account $account): int => $account->account_id,
            $assignees,
        ));

        foreach ($subtasks as $index => $subtask) {
            Subtask::create([
                'task_id' => $task->task_id,
                'title' => $subtask['title'],
                'is_done' => (bool) ($subtask['is_done'] ?? false),
                'position' => $index + 1,
            ]);
        }

        return $task;
    }
}
