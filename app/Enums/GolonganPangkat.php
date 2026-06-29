<?php

namespace App\Enums;

enum GolonganPangkat: string
{
    case IA = 'I/a';
    case IB = 'I/b';
    case IC = 'I/c';
    case ID = 'I/d';
    case IIA = 'II/a';
    case IIB = 'II/b';
    case IIC = 'II/c';
    case IID = 'II/d';
    case IIIA = 'III/a';
    case IIIB = 'III/b';
    case IIIC = 'III/c';
    case IIID = 'III/d';
    case IVA = 'IV/a';
    case IVB = 'IV/b';
    case IVC = 'IV/c';
    case IVD = 'IV/d';
    case IVE = 'IV/e';

    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'value')
        );
    }
}
