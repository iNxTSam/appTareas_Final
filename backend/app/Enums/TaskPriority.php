<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Baja = 'baja';
    case Media = 'media';
    case Alta = 'alta';

    public function label(): string
    {
        return match ($this) {
            self::Baja => 'Baja',
            self::Media => 'Media',
            self::Alta => 'Alta',
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
