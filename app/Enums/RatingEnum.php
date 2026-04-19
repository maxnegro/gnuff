<?php

namespace App\Enums;

enum RatingEnum: string
{
    case GNUF = 'gnuf';
    case OK = 'ok';
    case MEH = 'meh';
    case BLEAH = 'bleah';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}