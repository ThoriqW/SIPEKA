<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KebutuhanExport implements FromArray, WithHeadings, WithStyles
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
                $row['selisih'] ?? '',
                $row['pegawai'] ? implode(', ', array_column($row['pegawai'], 'nip')) : '',
                $row['pegawai'] ? implode(', ', array_column($row['pegawai'], 'nama')) : '',
                $row['kebutuhan_proyeksi'][1] ?? 0,
                $row['kebutuhan_proyeksi'][2] ?? 0,
                $row['kebutuhan_proyeksi'][3] ?? 0,
                $row['kebutuhan_proyeksi'][4] ?? 0,
                $row['kebutuhan_proyeksi'][5] ?? 0,
            ];
        }, $this->tree);
    }

    public function headings(): array
    {
        return [
            'Jabatan',
            'Kelas',
            'Kebutuhan',
            'Bezetting',
            'Selisih',
            'NIP',
            'Nama',
            'Kebutuhan Thn 1',
            'Kebutuhan Thn 2',
            'Kebutuhan Thn 3',
            'Kebutuhan Thn 4',
            'Kebutuhan Thn 5',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Bold header row
            1 => ['font' => ['bold' => true]],
        ];
    }
}
