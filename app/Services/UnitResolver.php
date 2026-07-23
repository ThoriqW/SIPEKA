<?php

namespace App\Services;

use App\Models\NodeOrganisasi;

/**
 * Mencari UNIT ancestor dari sebuah node POSISI di pohon organisasi.
 *
 * Digunakan untuk menentukan OPD asal pegawai berdasarkan posisi organisasinya.
 */
class UnitResolver
{
    /**
     * Cari root UNIT (OPD) dari sebuah node.
     * Naik ke atas pohon sampai menemukan node dengan jenis UNIT
     * yang parent-nya adalah root "Pemerintah Kota Palu" (parent_id = null
     * dengan nama "Pemerintah Kota Palu").
     *
     * Alternatif: cari UNIT ancestor dengan parent_id yang merupakan root.
     *
     * @return NodeOrganisasi|null
     */
    public function resolveOpd(NodeOrganisasi $node): ?NodeOrganisasi
    {
        // Jika node sendiri adalah UNIT yang langsung di bawah root
        if ($node->isUnit() && $this->isDirectChildOfRoot($node)) {
            return $node;
        }

        // Naik ke parent sampai menemukan UNIT di bawah root
        $current = $node;
        while ($current->parent) {
            $current = $current->parent;
            if ($current->isUnit() && $this->isDirectChildOfRoot($current)) {
                return $current;
            }
        }

        return null;
    }

    /**
     * Cari rantai UNIT dari node ke root.
     * Return array NodeOrganisasi dari root OPD → ... → node.
     *
     * @return NodeOrganisasi[]
     */
    public function resolveUnitChain(NodeOrganisasi $node): array
    {
        $chain = [];
        $current = $node;

        if ($node->isUnit()) {
            $chain[] = $node;
        }

        while ($current->parent) {
            $current = $current->parent;
            if ($current->isUnit()) {
                array_unshift($chain, $current);
            }
        }

        return $chain;
    }

    /**
     * Dapatkan nama unit path (contoh: "Dinas Kesehatan → Puskesmas Talise")
     */
    public function resolveUnitPath(NodeOrganisasi $node): string
    {
        $chain = $this->resolveUnitChain($node);
        return implode(' → ', array_map(fn($n) => $n->nama, $chain));
    }

    /**
     * Cek apakah node adalah anak langsung dari root "Pemerintah Kota Palu".
     */
    private function isDirectChildOfRoot(NodeOrganisasi $node): bool
    {
        if ($node->parent_id === null) {
            return false;
        }

        $parent = $node->parent;
        return $parent && $parent->parent_id === null && $parent->nama === 'Pemerintah Kota Palu';
    }
}
