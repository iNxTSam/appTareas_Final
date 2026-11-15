<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pendiente';
    case InProgress = 'en_progreso';
    case Completed = 'completada';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::InProgress => 'En progreso',
            self::Completed => 'Completada',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
