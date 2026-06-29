<?php

namespace App\Enums;

enum JenisKepegawaian: string
{
    case PNS = 'PNS';
    case PPPK = 'PPPK';

    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'value')
        );
    }
}
