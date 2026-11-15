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
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', 'string', 'max:80'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(TaskStatus::values())],
            'priority' => ['sometimes', Rule::in(TaskPriority::values())],
            'team' => ['nullable', 'string', 'max:60'],
            'responsible_id' => ['nullable', 'string', 'max:120'],
            'responsible_name' => ['nullable', 'string', 'max:120'],
            'subtasks' => ['sometimes', 'array'],
            'subtasks.*.text' => ['required', 'string', 'max:160'],
            'subtasks.*.done' => ['required', 'boolean'],
            'subtasks.*.responsible_id' => ['nullable', 'string', 'max:120'],
            'subtasks.*.responsible_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la tarea es obligatorio.',
            'name.string' => 'El nombre de la tarea debe ser texto.',
            'name.max' => 'El nombre no puede superar los 120 caracteres.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no puede superar los 1000 caracteres.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.date_format' => 'La fecha de inicio debe tener el formato AAAA-MM-DD.',
            'end_date.required' => 'La fecha de fin es obligatoria.',
            'end_date.date_format' => 'La fecha de fin debe tener el formato AAAA-MM-DD.',
            'end_date.after_or_equal' => 'El fin no puede ser antes del inicio.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser Pendiente, En progreso o Completada.',
            'priority.in' => 'La prioridad debe ser Normal o Importante.',
            'team.string' => 'El equipo debe ser texto.',
            'team.max' => 'El equipo no puede superar los 60 caracteres.',
            'category.string' => 'La categoría debe ser texto.',
            'category.max' => 'La categoría no puede superar los 80 caracteres.',
            'responsible_id.string' => 'El identificador del responsable debe ser texto.',
            'responsible_id.max' => 'El identificador del responsable no puede superar los 120 caracteres.',
            'responsible_name.string' => 'El responsable debe ser texto.',
            'responsible_name.max' => 'El responsable no puede superar los 120 caracteres.',
            'subtasks.array' => 'Las subtareas deben ser una lista.',
            'subtasks.*.text.required' => 'Cada subtarea necesita un texto.',
            'subtasks.*.text.string' => 'El texto de la subtarea debe ser texto.',
            'subtasks.*.text.max' => 'El texto de la subtarea no puede superar los 160 caracteres.',
            'subtasks.*.done.required' => 'Cada subtarea debe indicar si está completada.',
            'subtasks.*.done.boolean' => 'El estado de la subtarea debe ser verdadero o falso.',
        ];
    }
}
