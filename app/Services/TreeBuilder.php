<?php

namespace App\Services;

use App\Models\Jabatan;
use Illuminate\Support\Collection;

class TreeBuilder
{
    /**
     * Bangun struktur tree dari koleksi jabatan.
     * Return array dengan struktur nested (children key).
     */
    public function buildTree(int $opdId): array
    {
        $jabatanList = Jabatan::with(['pegawai', 'anak'])
            ->where('opd_id', $opdId)
            ->whereNull('induk_jabatan_id')
            ->get();

        return $this->mapTree($jabatanList);
    }

    /**
     * Rekursif mapping data jabatan ke format tree dengan children.
     */
    private function mapTree(Collection $jabatanList, int $level = 1): array
    {
        return $jabatanList->map(function (Jabatan $jabatan) use ($level) {
            $bezetting = $jabatan->pegawai->count();

            return [
                'id' => $jabatan->id,
                'nama_jabatan' => $jabatan->nama_jabatan,
                'kode_jabatan' => $jabatan->kode_jabatan,
                'jenis_jabatan' => $jabatan->jenis_jabatan,
                'kelas_jabatan' => $jabatan->kelas_jabatan,
                'kebutuhan' => $jabatan->kebutuhan,
                'bezetting' => $bezetting,
                'selisih' => $jabatan->kebutuhan !== null ? $bezetting - $jabatan->kebutuhan : null,
                'level' => $level,
                'pegawai' => $jabatan->pegawai->map(fn($p) => [
                    'nip' => $p->nip,
                    'nama' => $p->nama,
                ]),
                'children' => $level < 4 ? $this->mapTree($jabatan->anak, $level + 1) : [],
            ];
        })->values()->toArray();
    }
}
