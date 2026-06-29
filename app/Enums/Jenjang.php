<?php

namespace App\Enums;

enum Jenjang: string
{
    // Struktural
    case Pengawas = 'Pengawas';
    case Administrator = 'Administrator';
    case PimpinanTinggi = 'Pimpinan Tinggi';

    // Fungsional
    case AhliPertama = 'Ahli Pertama';
    case AhliMuda = 'Ahli Muda';
    case AhliMadya = 'Ahli Madya';
    case AhliUtama = 'Ahli Utama';
    case KeterampilanPenyelia = 'Keterampilan - Penyelia';
    case KeterampilanMahir = 'Keterampilan - Mahir';
    case KeterampilanTerampil = 'Keterampilan - Terampil';
    case KeterampilanPemula = 'Keterampilan - Pemula';

    // Pelaksana
    case Pelaksana = 'Pelaksana';

    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'value')
        );
    }

    /**
     * Get jenjang options filtered by jenis jabatan.
     */
    public static function forJenisJabatan(string $jenis): array
    {
        return match ($jenis) {
            'Struktural' => [
                self::Pengawas->value          => self::Pengawas->value,
                self::Administrator->value     => self::Administrator->value,
                self::PimpinanTinggi->value    => self::PimpinanTinggi->value,
            ],
            'Fungsional' => [
                self::AhliPertama->value            => self::AhliPertama->value,
                self::AhliMuda->value               => self::AhliMuda->value,
                self::AhliMadya->value              => self::AhliMadya->value,
                self::AhliUtama->value              => self::AhliUtama->value,
                self::KeterampilanPenyelia->value   => self::KeterampilanPenyelia->value,
                self::KeterampilanMahir->value      => self::KeterampilanMahir->value,
                self::KeterampilanTerampil->value   => self::KeterampilanTerampil->value,
                self::KeterampilanPemula->value     => self::KeterampilanPemula->value,
            ],
            'Pelaksana' => [
                self::Pelaksana->value => self::Pelaksana->value,
            ],
            default => [],
        };
    }
}
