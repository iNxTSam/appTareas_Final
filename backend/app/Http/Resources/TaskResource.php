<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->task_id,
            'title' => $this->title,
            'description' => $this->description,
            'team' => $this->team ? [
                'id' => $this->team->team_id,
                'name' => $this->team->name,
            ] : null,
            'project' => $this->project ? [
                'id' => $this->project->project_id,
                'name' => $this->project->name,
                'color' => $this->project->color,
            ] : null,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'position' => $this->position,
            'created_by' => $this->creator ? [
                'id' => $this->creator->account_id,
                'name' => $this->creator->name,
            ] : null,
            'assignees' => $this->assignees->map(static fn ($account): array => [
                'id' => $account->account_id,
                'name' => $account->name,
            ])->values(),
            'subtasks' => $this->subtasks->map(static fn ($subtask): array => [
                'id' => $subtask->subtask_id,
                'title' => $subtask->title,
                'is_done' => $subtask->is_done,
                'position' => $subtask->position,
                'assignee' => $subtask->assignee ? [
                    'id' => $subtask->assignee->account_id,
                    'name' => $subtask->assignee->name,
                ] : null,
            ])->values(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
