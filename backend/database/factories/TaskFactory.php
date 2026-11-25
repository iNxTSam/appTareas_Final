<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Account;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');
        $end = fake()->dateTimeBetween($start, '+2 months');
        $status = fake()->randomElement(TaskStatus::cases());

        return [
            'team_id' => null,
            'project_id' => null,
            'created_by' => Account::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => $status,
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'completed_at' => $status->value === 'completada' ? now() : null,
            'position' => 0,
        ];
    }

    public function forTeam(Team $team, ?Project $project = null): static
    {
        return $this->state(fn (): array => [
            'team_id' => $team->team_id,
            'project_id' => $project?->project_id,
        ]);
    }
}
