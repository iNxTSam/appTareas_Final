<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTaskRequest extends FormRequest
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
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', Rule::in(TaskStatus::values())],
            'priority' => ['nullable', Rule::in(TaskPriority::values())],
            'team_id' => ['nullable', 'integer', 'exists:team,team_id'],
            'project_id' => ['nullable', 'integer', 'exists:project,project_id'],
            'assignee_id' => ['nullable', 'integer', 'exists:account,account_id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from.date_format' => 'La fecha desde debe tener el formato AAAA-MM-DD.',
            'to.date_format' => 'La fecha hasta debe tener el formato AAAA-MM-DD.',
            'to.after_or_equal' => 'La fecha hasta no puede ser anterior a la fecha desde.',
            'status.in' => 'El estado debe ser Pendiente, En progreso o Completada.',
            'priority.in' => 'La prioridad debe ser Baja, Media o Alta.',
            'team_id.exists' => 'El equipo seleccionado no existe.',
            'project_id.exists' => 'El proyecto seleccionado no existe.',
            'assignee_id.exists' => 'El responsable seleccionado no existe.',
        ];
    }
}
