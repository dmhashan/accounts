<?php

namespace App\Enums;

enum DateFormat: string
{
    case DayMonYear = 'D MMM YYYY';
    case DDMMYYYY = 'DD/MM/YYYY';
    case MMDDYYYY = 'MM/DD/YYYY';
    case ISO = 'YYYY-MM-DD';
    case MonDayYear = 'MMM D, YYYY';

    public function label(): string
    {
        return match ($this) {
            self::DayMonYear => '28 May 2026',
            self::DDMMYYYY => '28/05/2026',
            self::MMDDYYYY => '05/28/2026',
            self::ISO => '2026-05-28',
            self::MonDayYear => 'May 28, 2026',
        };
    }

    public function example(): string
    {
        return match ($this) {
            self::DayMonYear => 'Day Mon Year',
            self::DDMMYYYY => 'DD/MM/YYYY',
            self::MMDDYYYY => 'MM/DD/YYYY',
            self::ISO => 'ISO 8601',
            self::MonDayYear => 'Mon Day, Year',
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
