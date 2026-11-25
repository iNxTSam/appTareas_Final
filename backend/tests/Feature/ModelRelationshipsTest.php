<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_team_project_task_and_subtask_relations_are_wired(): void
    {
        $owner = Account::factory()->create();
        $member = Account::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->account_id]);
        $team->members()->attach($owner->account_id, ['role' => 'owner']);
        $team->members()->attach($member->account_id, ['role' => 'member']);
        $project = Project::factory()->create(['team_id' => $team->team_id]);
        $task = Task::factory()->create([
            'team_id' => $team->team_id,
            'project_id' => $project->project_id,
            'created_by' => $owner->account_id,
        ]);
        $task->assignees()->attach($member->account_id);
        $subtask = Subtask::factory()->create([
            'task_id' => $task->task_id,
            'assignee_id' => $member->account_id,
        ]);

        $this->assertTrue($owner->createdTasks->contains($task));
        $this->assertTrue($member->assignments->contains($task));
        $this->assertTrue($member->assignedSubtasks->contains($subtask));
        $this->assertTrue($owner->teams->contains($team));
        $this->assertTrue($team->owner->is($owner));
        $this->assertTrue($team->members->contains($member));
        $this->assertTrue($team->projects->contains($project));
        $this->assertTrue($team->tasks->contains($task));
        $this->assertTrue($project->team->is($team));
        $this->assertTrue($project->tasks->contains($task));
        $this->assertTrue($task->team->is($team));
        $this->assertTrue($task->project->is($project));
        $this->assertTrue($task->creator->is($owner));
        $this->assertTrue($task->assignees->contains($member));
        $this->assertTrue($task->subtasks->contains($subtask));
        $this->assertTrue($subtask->task->is($task));
        $this->assertTrue($subtask->assignee->is($member));
    }
}
