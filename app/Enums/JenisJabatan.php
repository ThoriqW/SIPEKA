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

    /**
     * Singkatan untuk digunakan dalam kode jabatan auto-generated.
     */
    public function singkatan(): string
    {
        return match($this) {
            self::Struktural => 'STR',
            self::Fungsional => 'FNG',
            self::Pelaksana  => 'PLK',
        };
    }
}
