<?php

namespace App\Enums;

enum JenisJabatan: string
{
    case Struktural = 'Struktural';
    case Fungsional = 'Fungsional';
    case Pelaksana = 'Pelaksana';

    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'value')
        );
    }
}
