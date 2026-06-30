<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BezettingExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(private array $tree) {}

    public function array(): array
    {
        return array_map(function ($row) {
            $indent = $row['level'] > 0 ? str_repeat('  ', $row['level'] - 1) : '';
            return [
                $indent . $row['nama_jabatan'],
                $row['kelas_jabatan'] ?? '',
                $row['kebutuhan'] ?? '',
                $row['bezetting'],
                $row['pensiun_proyeksi'][1] ?? 0,
                $row['pensiun_proyeksi'][2] ?? 0,
                $row['pensiun_proyeksi'][3] ?? 0,
                $row['pensiun_proyeksi'][4] ?? 0,
                $row['pensiun_proyeksi'][5] ?? 0,
                $row['kebutuhan_proyeksi'][1] ?? 0,
                $row['kebutuhan_proyeksi'][2] ?? 0,
                $row['kebutuhan_proyeksi'][3] ?? 0,
                $row['kebutuhan_proyeksi'][4] ?? 0,
                $row['kebutuhan_proyeksi'][5] ?? 0,
                $row['pegawai'] ? implode(', ', array_column($row['pegawai'], 'nip')) : '',
                $row['pegawai'] ? implode(', ', array_column($row['pegawai'], 'nama')) : '',
            ];
        }, $this->tree);
    }

    public function headings(): array
    {
        return [
            'Jabatan',
            'Kelas Jabatan',
            'Kebutuhan',
            'Bezetting',
            'Pensiun Thn 1',
            'Pensiun Thn 2',
            'Pensiun Thn 3',
            'Pensiun Thn 4',
            'Pensiun Thn 5',
            'Kebutuhan Thn 1',
            'Kebutuhan Thn 2',
            'Kebutuhan Thn 3',
            'Kebutuhan Thn 4',
            'Kebutuhan Thn 5',
            'NIP',
            'Nama',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
