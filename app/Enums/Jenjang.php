<?php

namespace App\Enums;

enum Jenjang: string
{
    case Pelaksana = 'Pelaksana';
    case AhliPertama = 'Ahli Pertama';
    case AhliMuda = 'Ahli Muda';
    case AhliMadya = 'Ahli Madya';
    case AhliUtama = 'Ahli Utama';
    case Keterampilan = 'Keterampilan';
    case Guru = 'Guru';
    case PimpinanTinggi = 'Pimpinan Tinggi';

    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'value')
        );
    }
}
