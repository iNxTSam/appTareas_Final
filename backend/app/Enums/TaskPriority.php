<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Normal = 'normal';
    case Important = 'importante';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Important => 'Importante',
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
