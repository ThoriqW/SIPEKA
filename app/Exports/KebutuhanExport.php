<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KebutuhanExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(private array $tree, private array $tahunLabels) {}

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
