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
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'team' => $this->team,
            'responsible_id' => $this->responsible_id,
            'responsible_name' => $this->responsible_name,
            'subtasks' => $this->subtasks ?? [],
            'created_at' => $this->created_at?->ToIso8601String(),
            'updated_at' => $this->updated_at?->ToIso8601String(),
        ];
    }
}
