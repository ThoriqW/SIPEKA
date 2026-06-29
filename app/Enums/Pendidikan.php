<?php

namespace App\Enums;

enum Pendidikan: string
{
    case SD = 'SD';
    case SMP = 'SMP';
    case SMA = 'SMA';
    case D1 = 'D1';
    case D2 = 'D2';
    case D3 = 'D3';
    case D4S1 = 'D4/S1';
    case S2 = 'S2';
    case S3 = 'S3';

    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'value')
        );
    }
}
