<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'team_id' => ['nullable', 'integer', 'exists:team,team_id'],
            'project_id' => ['nullable', 'integer', 'exists:project,project_id'],
            'status' => ['required', Rule::in(TaskStatus::values())],
            'priority' => ['sometimes', Rule::in(TaskPriority::values())],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'assignee_ids' => ['sometimes', 'array'],
            'assignee_ids.*' => ['integer', 'distinct', 'exists:account,account_id'],
            'subtasks' => ['sometimes', 'array'],
            'subtasks.*.title' => ['required', 'string', 'max:160'],
            'subtasks.*.is_done' => ['sometimes', 'boolean'],
            'subtasks.*.position' => ['sometimes', 'integer', 'min:0'],
            'subtasks.*.assignee_id' => ['nullable', 'integer', 'exists:account,account_id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título de la tarea es obligatorio.',
            'title.string' => 'El título debe ser texto.',
            'title.max' => 'El título no puede superar los 120 caracteres.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no puede superar los 1000 caracteres.',
            'team_id.exists' => 'El equipo seleccionado no existe.',
            'project_id.exists' => 'El proyecto seleccionado no existe.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser Pendiente, En progreso o Completada.',
            'priority.in' => 'La prioridad debe ser Baja, Media o Alta.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date_format' => 'La fecha de inicio debe tener el formato AAAA-MM-DD.',
            'end_date.required' => 'La fecha de fin es obligatoria.',
            'end_date.date_format' => 'La fecha de fin debe tener el formato AAAA-MM-DD.',
            'end_date.after_or_equal' => 'El fin no puede ser antes del inicio.',
            'assignee_ids.array' => 'Los responsables deben ser una lista.',
            'assignee_ids.*.exists' => 'Uno de los responsables no existe.',
            'subtasks.array' => 'Las subtareas deben ser una lista.',
            'subtasks.*.title.required' => 'Cada subtarea necesita un título.',
            'subtasks.*.title.max' => 'El título de la subtarea no puede superar los 160 caracteres.',
            'subtasks.*.is_done.boolean' => 'El estado de la subtarea debe ser verdadero o falso.',
            'subtasks.*.assignee_id.exists' => 'El responsable de la subtarea no existe.',
        ];
    }
}
