<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BezettingExport implements FromArray, WithHeadings, WithStyles
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
                $row['pegawai_pensiun'] ? implode(', ', array_column($row['pegawai_pensiun'], 'nip')) : '',
                $row['pegawai_pensiun'] ? implode(', ', array_column($row['pegawai_pensiun'], 'nama')) : '',
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
            'Pensiun ' . $this->tahunLabels[1],
            'Pensiun ' . $this->tahunLabels[2],
            'Pensiun ' . $this->tahunLabels[3],
            'Pensiun ' . $this->tahunLabels[4],
            'Pensiun ' . $this->tahunLabels[5],
            'Kebutuhan ' . $this->tahunLabels[1],
            'Kebutuhan ' . $this->tahunLabels[2],
            'Kebutuhan ' . $this->tahunLabels[3],
            'Kebutuhan ' . $this->tahunLabels[4],
            'Kebutuhan ' . $this->tahunLabels[5],
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
