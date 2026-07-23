<?php

namespace App\Console\Commands;

use App\Models\Jabatan;
use App\Models\JabatanAsn;
use App\Models\MasterJabatan;
use App\Models\NodeOrganisasi;
use App\Models\Opd;
use App\Models\Pegawai;
use App\Services\KodeNodeGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateSotkCommand extends Command
{
    protected $signature = 'sipeka:migrate-sotk {--dry-run : Simulasi tanpa menyimpan data}';
    protected $description = 'Migrasi data dari model SOTK lama (jabatan, opd, master_jabatan) ke model baru (node_organisasi, jabatan_asn)';

    private array $stats = [
        'jabatan_asn' => 0,
        'node_unit' => 0,
        'node_posisi' => 0,
        'pegawai_updated' => 0,
        'errors' => 0,
    ];

    private array $mapping = [
        'jabatan_to_node' => [],  // old_jabatan_id => new_node_id (for induk mapping)
        'master_to_jasn' => [],  // old_master_id => new_jabatan_asn_id
    ];

    public function __construct(
        private KodeNodeGenerator $kodeGenerator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('=== SIPEKA SOTK Migration ===');
        $this->info('Migrasi: master_jabatan → jabatan_asn, opd + jabatan → node_organisasi');

        if ($this->option('dry-run')) {
            $this->warn('>>> DRY RUN MODE — tidak ada data yang disimpan <<<');
        }

        DB::beginTransaction();

        try {
            $this->migrateMasterJabatanToJabatanAsn();
            $this->migrateOpdToUnitNodes();
            $this->migrateJabatanToNodes();
            $this->migratePegawaiAssignments();

            if ($this->option('dry-run')) {
                $this->warn('Dry run — rolling back all changes.');
                DB::rollBack();
            } else {
                DB::commit();
                $this->info('Migrasi selesai — data tersimpan.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('ERROR: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }

        $this->printStats();

        return self::SUCCESS;
    }

    /*
     * -------------------------------------------------------------------------
     * Tahap 1: master_jabatan → jabatan_asn
     * -------------------------------------------------------------------------
     */

    private function migrateMasterJabatanToJabatanAsn(): void
    {
        $this->info('--- Tahap 1: Migrasi master_jabatan → jabatan_asn ---');

        $masterList = MasterJabatan::orderBy('parent_id')->orderBy('nama_jabatan')->get();

        // Pertama: root entries
        foreach ($masterList->whereNull('parent_id') as $master) {
            $jasn = JabatanAsn::where('nama_jabatan_asn', $master->nama_jabatan)
                ->where('jenis_jabatan', $master->jenis_jabatan)
                ->first();

            if (!$jasn) {
                $jasn = JabatanAsn::create([
                    'nama_jabatan_asn' => $master->nama_jabatan,
                    'jenis_jabatan' => $master->jenis_jabatan,
                    'jenjang' => null, // master_jabatan tidak memiliki jenjang
                    'kode_jabatan_asn' => $this->kodeGenerator->generateKodeJabatanAsn(),
                ]);
                $this->stats['jabatan_asn']++;
            }

            $this->mapping['master_to_jasn'][$master->id] = $jasn->id;
        }

        // Kedua: child entries
        foreach ($masterList->whereNotNull('parent_id') as $master) {
            $parentId = $this->mapping['master_to_jasn'][$master->parent_id] ?? null;

            $jasn = JabatanAsn::where('nama_jabatan_asn', $master->nama_jabatan)
                ->where('jenis_jabatan', $master->jenis_jabatan)
                ->first();

            if (!$jasn) {
                $jasn = JabatanAsn::create([
                    'nama_jabatan_asn' => $master->nama_jabatan,
                    'jenis_jabatan' => $master->jenis_jabatan,
                    'jenjang' => null,
                    'parent_id' => $parentId,
                    'kode_jabatan_asn' => $this->kodeGenerator->generateKodeJabatanAsn(),
                ]);
                $this->stats['jabatan_asn']++;
            }

            $this->mapping['master_to_jasn'][$master->id] = $jasn->id;
        }

        $this->info("  → {$this->stats['jabatan_asn']} jabatan ASN dibuat");
    }

    /*
     * -------------------------------------------------------------------------
     * Tahap 2: OPD → node_organisasi UNIT
     * -------------------------------------------------------------------------
     */

    private function migrateOpdToUnitNodes(): void
    {
        $this->info('--- Tahap 2: Migrasi OPD → UNIT nodes ---');

        // Cari atau buat root "Pemerintah Kota Palu"
        $root = NodeOrganisasi::whereNull('parent_id')->first();

        if (!$root) {
            $root = NodeOrganisasi::create([
                'nama' => 'Pemerintah Kota Palu',
                'jenis' => 'UNIT',
                'kode' => 'PEMKOT-PALU',
                'parent_id' => null,
            ]);
            $this->stats['node_unit']++;
            $this->info('  → Root "Pemerintah Kota Palu" dibuat');
        }

        // Migrasi setiap OPD menjadi UNIT node di bawah root
        $opdList = Opd::orderBy('nama_opd')->get();

        foreach ($opdList as $opd) {
            $unit = NodeOrganisasi::create([
                'nama' => $opd->nama_opd,
                'kode' => $opd->kode_opd,
                'jenis' => 'UNIT',
                'parent_id' => $root->id,
            ]);
            $this->stats['node_unit']++;

            // Simpan mapping untuk digunakan nanti
            $this->mapping['opd_to_unit'][$opd->id] = $unit->id;
        }

        $this->info("  → {$this->stats['node_unit']} UNIT node dibuat (termasuk root)");
    }

    /*
     * -------------------------------------------------------------------------
     * Tahap 3: jabatan → node_organisasi (UNIT/POSISI)
     * -------------------------------------------------------------------------
     */

    private function migrateJabatanToNodes(): void
    {
        $this->info('--- Tahap 3: Migrasi jabatan → node_organisasi ---');

        // Proses depth-first berdasarkan induk_jabatan_id
        // Urutan: null dulu (root jabatan), lalu anak-anaknya
        $allJabatan = Jabatan::with('opd')->orderBy('induk_jabatan_id')->get();

        // Group by induk
        $grouped = [];
        foreach ($allJabatan as $j) {
            $grouped[$j->induk_jabatan_id ?? 0][] = $j;
        }

        // Proses dari root-level (induk_jabatan_id = null)
        $roots = $grouped[0] ?? [];
        foreach ($roots as $rootJabatan) {
            $this->migrateJabatanRecursive($rootJabatan, $grouped);
        }
    }

    private function migrateJabatanRecursive(Jabatan $jabatan, array $grouped): int
    {
        $hasChildren = isset($grouped[$jabatan->id]);
        $pegawaiCount = $jabatan->pegawai()->count();
        $isStruktural = $jabatan->jenis_jabatan === 'Struktural';
        $isPratama = $isStruktural && $jabatan->jenjang === 'Pimpinan Tinggi Pratama';

        // Tentukan parent node
        $parentNodeId = null;
        if ($jabatan->induk_jabatan_id) {
            $parentNodeId = $this->mapping['jabatan_to_node'][$jabatan->induk_jabatan_id] ?? null;
        } else {
            // Root jabatan → parent = UNIT OPD
            $parentNodeId = $this->mapping['opd_to_unit'][$jabatan->opd_id] ?? null;
        }

        if ($isPratama) {
            // JPTP: langsung menjadi POSISI di bawah UNIT OPD
            $node = NodeOrganisasi::create([
                'nama' => $jabatan->nama_jabatan,
                'kode' => $jabatan->kode_jabatan,
                'jenis' => 'POSISI',
                'parent_id' => $parentNodeId,
                'kelas_jabatan' => $jabatan->kelas_jabatan,
            ]);
            $this->stats['node_posisi']++;
            $this->mapping['jabatan_to_node'][$jabatan->id] = $node->id;

            // Tetap rekursi anak-anak — JPTP bisa punya anak (Sekretariat, Bidang, dll)
            if ($hasChildren) {
                foreach ($grouped[$jabatan->id] as $child) {
                    $this->migrateJabatanRecursive($child, $grouped);
                }
            }
            return $node->id;
        }

        if ($isStruktural && $hasChildren) {
            // Struktural dengan anak: jadikan UNIT (wadah)
            // Mapping selalu ke UNIT agar anak-anak bisa menginduk dengan benar
            $unitNode = NodeOrganisasi::create([
                'nama' => $jabatan->nama_jabatan,
                'kode' => $jabatan->kode_jabatan,
                'jenis' => 'UNIT',
                'parent_id' => $parentNodeId,
            ]);
            $this->stats['node_unit']++;

            // Mapping ke UNIT (bukan POSISI) agar anak-anak menginduk ke UNIT
            $this->mapping['jabatan_to_node'][$jabatan->id] = $unitNode->id;

            // Jika ada pegawai di jabatan ini, buat POSISI child terpisah
            if ($pegawaiCount > 0) {
                $posisiNode = NodeOrganisasi::create([
                    'nama' => $jabatan->nama_jabatan,
                    'kode' => $jabatan->kode_jabatan . '-POS',
                    'jenis' => 'POSISI',
                    'parent_id' => $unitNode->id,
                    'kelas_jabatan' => $jabatan->kelas_jabatan,
                ]);
                $this->stats['node_posisi']++;
                // Simpan mapping tambahan: pegawai di jabatan ini → POSISI child
                $this->mapping['jabatan_pegawai_to_posisi'][$jabatan->id] = $posisiNode->id;
            }

            // Rekursi anak-anak
            foreach ($grouped[$jabatan->id] as $child) {
                $this->migrateJabatanRecursive($child, $grouped);
            }
            return $unitNode->id;
        } elseif (!$isStruktural || !$hasChildren) {
            // Fungsional / Pelaksana / Struktural tanpa anak:
            // Buat POSISI. Jika kebutuhan > 1, buat banyak POSISI.
            $kebutuhan = max(1, $jabatan->kebutuhan ?? 1, $pegawaiCount);

            for ($i = 0; $i < $kebutuhan; $i++) {
                $suffix = $kebutuhan > 1 ? ' ' . ($i + 1) : '';
                $kodeSuffix = $kebutuhan > 1 ? '-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) : '';

                $node = NodeOrganisasi::create([
                    'nama' => $jabatan->nama_jabatan . $suffix,
                    'kode' => $jabatan->kode_jabatan . $kodeSuffix,
                    'jenis' => 'POSISI',
                    'parent_id' => $parentNodeId,
                    'kelas_jabatan' => $jabatan->kelas_jabatan,
                ]);
                $this->stats['node_posisi']++;

                // Simpan mapping (pertama kali saja, untuk backward compat)
                if ($i === 0) {
                    $this->mapping['jabatan_to_node'][$jabatan->id] = $node->id;
                } else {
                    $this->mapping['jabatan_extra_nodes'][] = [
                        'old_jabatan_id' => $jabatan->id,
                        'new_node_id' => $node->id,
                    ];
                }
            }
        }

        // Rekursi anak-anak (jika belum diproses di atas)
        if ($hasChildren && !$isStruktural) {
            foreach ($grouped[$jabatan->id] as $child) {
                $this->migrateJabatanRecursive($child, $grouped);
            }
        }

        return $this->mapping['jabatan_to_node'][$jabatan->id] ?? 0;
    }

    /*
     * -------------------------------------------------------------------------
     * Tahap 4: Update pegawai dengan jabatan_asn_id & posisi_organisasi_id
     * -------------------------------------------------------------------------
     */

    private function migratePegawaiAssignments(): void
    {
        $this->info('--- Tahap 4: Update pegawai assignments ---');

        $pegawaiList = Pegawai::with('jabatan')->whereNotNull('jabatan_id')->get();

        foreach ($pegawaiList as $pegawai) {
            $oldJabatan = $pegawai->jabatan;
            if (!$oldJabatan) {
                continue;
            }

            // Cari atau buat jabatan_asn berdasarkan nama_jabatan + jenis_jabatan + jenjang
            $jasn = JabatanAsn::where('nama_jabatan_asn', $oldJabatan->nama_jabatan)
                ->where('jenis_jabatan', $oldJabatan->jenis_jabatan)
                ->first();

            if (!$jasn) {
                // Buat baru dari data jabatan lama
                $jasn = JabatanAsn::create([
                    'nama_jabatan_asn' => $oldJabatan->nama_jabatan,
                    'jenis_jabatan' => $oldJabatan->jenis_jabatan,
                    'jenjang' => $oldJabatan->jenjang,
                    'kode_jabatan_asn' => $this->kodeGenerator->generateKodeJabatanAsn(),
                ]);
                $this->stats['jabatan_asn']++;
            }

            // Cari node POSISI hasil migrasi
            // Prioritas: mapping khusus pegawai, lalu mapping umum, lalu extra nodes
            $newPosisiId = $this->mapping['jabatan_pegawai_to_posisi'][$oldJabatan->id]
                ?? $this->mapping['jabatan_to_node'][$oldJabatan->id]
                ?? null;

            // Hanya gunakan mapping jika node adalah POSISI
            if ($newPosisiId) {
                $targetNode = NodeOrganisasi::find($newPosisiId);
                if ($targetNode && $targetNode->isUnit()) {
                    // Mapping mengarah ke UNIT — cari POSISI child (untuk struktural dengan anak)
                    $childPosisi = NodeOrganisasi::posisi()
                        ->where('parent_id', $targetNode->id)
                        ->where('nama', $oldJabatan->nama_jabatan)
                        ->first();
                    if ($childPosisi && !$childPosisi->isTerisi()) {
                        $newPosisiId = $childPosisi->id;
                    } else {
                        $newPosisiId = null; // tidak ada posisi yang tersedia
                    }
                }
            }

            if ($newPosisiId) {
                $posisi = NodeOrganisasi::find($newPosisiId);

                // Pastikan node adalah POSISI dan belum terisi
                if ($posisi && $posisi->isPosisi() && !$posisi->isTerisi()) {
                    $pegawai->update([
                        'jabatan_asn_id' => $jasn->id,
                        'posisi_organisasi_id' => $newPosisiId,
                        'jenjang' => $jasn->jenjang ?? $pegawai->jenjang,
                    ]);
                    $this->stats['pegawai_updated']++;
                } elseif ($posisi && $posisi->isPosisi() && $posisi->isTerisi()) {
                    // Posisi sudah terisi — cari posisi kosong dari extra nodes
                    $extraNodes = $this->mapping['jabatan_extra_nodes'] ?? [];
                    $found = false;
                    foreach ($extraNodes as $extra) {
                        if ($extra['old_jabatan_id'] == $oldJabatan->id) {
                            $extraPosisi = NodeOrganisasi::find($extra['new_node_id']);
                            if ($extraPosisi && $extraPosisi->isPosisi() && !$extraPosisi->isTerisi()) {
                                $pegawai->update([
                                    'jabatan_asn_id' => $jasn->id,
                                    'posisi_organisasi_id' => $extra['new_node_id'],
                                    'jenjang' => $jasn->jenjang ?? $pegawai->jenjang,
                                ]);
                                $this->stats['pegawai_updated']++;
                                $found = true;
                                break;
                            }
                        }
                    }
                    if (!$found) {
                        $this->warn("  ⚠ Pegawai {$pegawai->nama} (NIP: {$pegawai->nip}) — posisi sudah terisi, tidak ada posisi kosong.");
                        $this->stats['errors']++;
                    }
                }
            } else {
                $this->warn("  ⚠ Pegawai {$pegawai->nama} — jabatan lama tidak ditemukan mapping nodenya.");
                $this->stats['errors']++;
            }
        }

        $this->info("  → {$this->stats['pegawai_updated']} pegawai berhasil dimigrasi");
    }

    /*
     * -------------------------------------------------------------------------
     * Output
     * -------------------------------------------------------------------------
     */

    private function printStats(): void
    {
        $this->newLine();
        $this->info('=== Statistik Migrasi ===');
        $this->table(
            ['Item', 'Jumlah'],
            [
                ['Jabatan ASN dibuat', $this->stats['jabatan_asn']],
                ['UNIT node dibuat', $this->stats['node_unit']],
                ['POSISI node dibuat', $this->stats['node_posisi']],
                ['Pegawai diupdate', $this->stats['pegawai_updated']],
                ['Error/warning', $this->stats['errors']],
            ]
        );

        if ($this->option('dry-run')) {
            $this->warn('Dry run — data di atas tidak disimpan.');
        }
    }
}
