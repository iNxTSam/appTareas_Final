<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
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

        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'category' => fake()->optional()->word(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'priority' => TaskPriority::Normal,
            'team' => fake()->optional()->word(),
            'responsible_id' => null,
            'responsible_name' => fake()->optional()->name(),
            'subtasks' => [],
        ];
    }
}
