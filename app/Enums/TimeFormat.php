<?php

namespace App\Enums;

enum TimeFormat: string
{
    case H24 = 'HH:mm';
    case H12 = 'h:mm A';

    public function label(): string
    {
        return match ($this) {
            self::H24 => '14:30',
            self::H12 => '2:30 PM',
        };
    }

    public function example(): string
    {
        return match ($this) {
            self::H24 => '24-hour',
            self::H12 => '12-hour',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'example' => $case->example(),
            ],
            self::cases(),
        );
    }
}
