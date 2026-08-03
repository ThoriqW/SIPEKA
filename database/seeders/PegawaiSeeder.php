<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\Unor;
use Illuminate\Database\Seeder;

class PegawaiSeeder extends Seeder
{
    public function run(): void
    {
        // Helper closure
        $j = fn(string $kodeUnor, string $namaJabatan, ?string $jenjang = null) =>
            Jabatan::where('nama_jabatan', $namaJabatan)
                ->where('jenjang', $jenjang)
                ->whereHas('sotkEntries', fn($q) => $q->whereHas('unor', fn($uq) => $uq->where('kode_unor', $kodeUnor)))
                ->first()?->id;

        // =====================================================
        // BKPSDMD — 21 pegawai
        // =====================================================

        // Kepala Badan (kebutuhan=1, bezetting=1) — CUKUP
        $this->buatPegawai('Drs. Abdul Muis, M.Si.', '197205202000011001', 'PNS', '1972-05-20', 'IV/c', 'S2', $j('BKPSDM', 'Kepala Badan', 'Pimpinan Tinggi Pratama'), 'BKPSDM');

        // Sekretaris (kebutuhan=1, bezetting=1) — CUKUP
        $this->buatPegawai('Hj. Nurjannah, S.Sos., M.M.', '196903102005012002', 'PNS', '1969-03-10', 'IV/b', 'S2', $j('BKPSDM-SEKR', 'Sekretaris', 'Administrator'), 'BKPSDM-SEKR');

        // Sub Bagian Umum — Kepala Sub Bagian (kebutuhan=1, bezetting=1)
        $this->buatPegawai('I Made Suardika, S.H.', '197911152010011003', 'PNS', '1979-11-15', 'III/d', 'S1', $j('BKPSDM-SUB-UMUM', 'Kepala Sub Bagian', 'Pengawas'), 'BKPSDM-SUB-UMUM');

        // Sub Bagian Umum — Pengelola Kepegawaian (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Indah Permatasari, S.E.', '198412202015012004', 'PNS', '1984-12-20', 'III/b', 'S1', $j('BKPSDM-SUB-UMUM', 'Pengelola Kepegawaian', 'Pelaksana'), 'BKPSDM-SUB-UMUM');

        // Sub Bagian Umum — Pengemudi (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Supriyadi', '198506102015011005', 'PNS', '1985-06-10', 'II/b', 'SMA', $j('BKPSDM-SUB-UMUM', 'Pengemudi', 'Pelaksana'), 'BKPSDM-SUB-UMUM');

        // Sub Bagian Keuangan — Kepala Sub Bagian (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Rahmawati, S.E., M.M.', '198003152010012006', 'PNS', '1980-03-15', 'III/c', 'S2', $j('BKPSDM-SUB-KEU', 'Kepala Sub Bagian', 'Pengawas'), 'BKPSDM-SUB-KEU');

        // Sub Bagian Keuangan — Pengelola Keuangan (kebutuhan=1, bezetting=2) — LEBIH
        $this->buatPegawai('Ahmad Fauzi, S.E.', '198807202015011007', 'PNS', '1988-07-20', 'III/a', 'S1', $j('BKPSDM-SUB-KEU', 'Pengelola Keuangan', 'Pelaksana'), 'BKPSDM-SUB-KEU');
        $this->buatPegawai('Dian Permata, A.Md.', '199203102020012008', 'PPPK', '1992-03-10', 'II/c', 'D3', $j('BKPSDM-SUB-KEU', 'Pengelola Keuangan', 'Pelaksana'), 'BKPSDM-SUB-KEU');

        // Sub Bagian Keuangan — Bendahara (kebutuhan=1, bezetting=0) — KOSONG (tidak dibuat)

        // Sub Bagian Perencanaan — Kepala Sub Bagian (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Dr. Andi Setiawan, S.T., M.T.', '197512102000011009', 'PNS', '1975-12-10', 'III/d', 'S2', $j('BKPSDM-SUB-REN', 'Kepala Sub Bagian', 'Pengawas'), 'BKPSDM-SUB-REN');

        // Sub Bagian Perencanaan — Pranata Komputer (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Moh. Thoriq, S.Kom.', '199005152015011010', 'PNS', '1990-05-15', 'III/a', 'S1', $j('BKPSDM-SUB-REN', 'Pranata Komputer', 'Ahli Pertama'), 'BKPSDM-SUB-REN');

        // Bidang PPI — Kepala Bidang (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Zulkifli, S.Sos., M.Si.', '197808102010011011', 'PNS', '1978-08-10', 'III/c', 'S2', $j('BKPSDM-BID-PPI', 'Kepala Bidang', 'Administrator'), 'BKPSDM-BID-PPI');

        // Bidang PPI — Pranata Komputer (kebutuhan=3, bezetting=2) — KURANG
        $this->buatPegawai('Kartika Dewi, S.Kom.', '199307202020012012', 'PPPK', '1993-07-20', 'III/a', 'S1', $j('BKPSDM-BID-PPI', 'Pranata Komputer', 'Ahli Pertama'), 'BKPSDM-BID-PPI');
        $this->buatPegawai('Bayu Prasetyo, S.Kom.', '199505102020012013', 'PPPK', '1995-05-10', 'III/a', 'S1', $j('BKPSDM-BID-PPI', 'Pranata Komputer', 'Ahli Pertama'), 'BKPSDM-BID-PPI');

        // Bidang PPI — Pengelola Kepegawaian (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Sari Dewi Utami, A.Md.', '199110152015012014', 'PNS', '1991-10-15', 'II/c', 'D3', $j('BKPSDM-BID-PPI', 'Pengelola Kepegawaian', 'Pelaksana'), 'BKPSDM-BID-PPI');

        // Bidang Mutasi — Kepala Bidang (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Hendra Setiawan, S.H., M.H.', '197611202010011015', 'PNS', '1976-11-20', 'III/d', 'S2', $j('BKPSDM-BID-MP', 'Kepala Bidang', 'Administrator'), 'BKPSDM-BID-MP');

        // Bidang Mutasi — Pengelola Kepegawaian (kebutuhan=1, bezetting=0) — KOSONG

        // Bidang PK — Kepala Bidang (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Dra. Megawati, M.Pd.', '197403082005012016', 'PNS', '1974-03-08', 'IV/a', 'S2', $j('BKPSDM-BID-PK', 'Kepala Bidang', 'Administrator'), 'BKPSDM-BID-PK');

        // Bidang PK — Widyaiswara (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Prof. Dr. Syamsuddin, M.Si.', '196512152000011017', 'PNS', '1965-12-15', 'IV/c', 'S3', $j('BKPSDM-BID-PK', 'Widyaiswara', 'Ahli Muda'), 'BKPSDM-BID-PK');

        // =====================================================
        // Dinas Kesehatan — 14 pegawai
        // =====================================================

        // Kepala Dinas (kebutuhan=1, bezetting=1) — CUKUP
        $this->buatPegawai('dr. Hj. Rahmaniar, M.Kes.', '197003152005012001', 'PNS', '1970-03-15', 'IV/c', 'S2', $j('DINKES', 'Kepala Dinas', 'Pimpinan Tinggi Pratama'), 'DINKES');

        // Sekretaris (kebutuhan=1, bezetting=1)
        $this->buatPegawai('drg. Markus Latuconsina', '197508102000011002', 'PNS', '1975-08-10', 'III/d', 'S1', $j('DINKES-SEKR', 'Sekretaris', 'Administrator'), 'DINKES-SEKR');

        // Bidang Yankes — Kepala Bidang (kebutuhan=1, bezetting=1)
        $this->buatPegawai('dr. Nurul Hidayah, M.K.M.', '197807152000012003', 'PNS', '1978-07-15', 'III/d', 'S2', $j('DINKES-BID-YANKES', 'Kepala Bidang', 'Administrator'), 'DINKES-BID-YANKES');

        // Bidang Yankes — Dokter (kebutuhan=2, bezetting=1) — KURANG
        $this->buatPegawai('dr. Andini Putri', '198506152010012004', 'PNS', '1985-06-15', 'III/b', 'S1', $j('DINKES-BID-YANKES', 'Dokter', 'Ahli Pertama'), 'DINKES-BID-YANKES');

        // Bidang Yankes — Perawat (kebutuhan=3, bezetting=3) — CUKUP
        $this->buatPegawai('Nurul Hidayah, A.Md.Kep.', '198812012010012005', 'PNS', '1988-12-01', 'II/d', 'D3', $j('DINKES-BID-YANKES', 'Perawat', 'Ahli Pertama'), 'DINKES-BID-YANKES');
        $this->buatPegawai('Rini Astuti, A.Md.Kep.', '199302202020012006', 'PPPK', '1993-02-20', 'II/b', 'D3', $j('DINKES-BID-YANKES', 'Perawat', 'Ahli Pertama'), 'DINKES-BID-YANKES');
        $this->buatPegawai('Fitriani, A.Md.Kep.', '199507152020012007', 'PPPK', '1995-07-15', 'II/b', 'D3', $j('DINKES-BID-YANKES', 'Perawat', 'Ahli Pertama'), 'DINKES-BID-YANKES');

        // Bidang Yankes — Administrator Kesehatan (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Agus Salim, S.K.M.', '199006152015011008', 'PNS', '1990-06-15', 'III/a', 'S1', $j('DINKES-BID-YANKES', 'Administrator Kesehatan', 'Ahli Pertama'), 'DINKES-BID-YANKES');

        // Bidang P2P — Kepala Bidang (kebutuhan=1, bezetting=1)
        $this->buatPegawai('dr. H. Syamsul Bahri, M.Kes.', '196512102000011009', 'PNS', '1965-12-10', 'IV/b', 'S2', $j('DINKES-BID-P2P', 'Kepala Bidang', 'Administrator'), 'DINKES-BID-P2P');

        // Bidang P2P — Epidemiolog (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Rosmawati, S.K.M., M.Epid.', '198502152010012010', 'PNS', '1985-02-15', 'III/b', 'S2', $j('DINKES-BID-P2P', 'Epidemiolog Kesehatan', 'Ahli Pertama'), 'DINKES-BID-P2P');

        // Bidang P2P — Entomolog (kebutuhan=1, bezetting=0) — KOSONG

        // Bidang Kesmas — Kepala Bidang (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Linda Marlina, S.K.M., M.Kes.', '197908152010012011', 'PNS', '1979-08-15', 'III/c', 'S2', $j('DINKES-BID-KESMAS', 'Kepala Bidang', 'Administrator'), 'DINKES-BID-KESMAS');

        // Bidang Kesmas — Nutrisionis (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Dewi Sartika, S.Gz.', '199103152020012012', 'PPPK', '1991-03-15', 'III/a', 'S1', $j('DINKES-BID-KESMAS', 'Nutrisionis', 'Ahli Pertama'), 'DINKES-BID-KESMAS');

        // =====================================================
        // Dinas Pendidikan — 11 pegawai
        // =====================================================

        // Kepala Dinas (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Dr. Andi Mahmud, M.Pd.', '197505152000011001', 'PNS', '1975-05-15', 'IV/c', 'S3', $j('DIKBUD', 'Kepala Dinas', 'Pimpinan Tinggi Pratama'), 'DIKBUD');

        // Sekretaris (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Siti Rahayu, S.E., M.M.', '198002202005012002', 'PNS', '1980-02-20', 'III/d', 'S2', $j('DIKBUD-SEKR', 'Sekretaris', 'Administrator'), 'DIKBUD-SEKR');

        // Bidang PAUD — Kepala Bidang (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Budi Santoso, S.Pd., M.Si.', '197811102003011003', 'PNS', '1978-11-10', 'III/c', 'S2', $j('DIKBUD-BID-PAUD', 'Kepala Bidang', 'Administrator'), 'DIKBUD-BID-PAUD');

        // Bidang PAUD — Analis Kebijakan (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Dewi Anggraini, S.E.', '198505252010012004', 'PNS', '1985-05-25', 'III/a', 'S1', $j('DIKBUD-BID-PAUD', 'Analis Kebijakan', 'Ahli Pertama'), 'DIKBUD-BID-PAUD');

        // Bidang SD — Kepala Bidang (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Rahmat Hidayat, S.Pd.', '198210102015011005', 'PNS', '1982-10-10', 'III/b', 'S1', $j('DIKBUD-BID-SD', 'Kepala Bidang', 'Administrator'), 'DIKBUD-BID-SD');

        // Bidang SMP — Kepala Bidang (kebutuhan=1, bezetting=0) — KOSONG

        // Bidang GTK — Kepala Bidang (kebutuhan=1, bezetting=1)
        $this->buatPegawai('Dra. Nurhayati', '196701011990012006', 'PNS', '1967-01-01', 'IV/a', 'S1', $j('DIKBUD-BID-GTK', 'Kepala Bidang', 'Administrator'), 'DIKBUD-BID-GTK');

        // Bidang GTK — Pengelola Kepegawaian (kebutuhan=1, bezetting=2) — LEBIH
        $this->buatPegawai('Suparno, A.Md.', '199003122015011007', 'PNS', '1990-03-12', 'II/c', 'D3', $j('DIKBUD-BID-GTK', 'Pengelola Kepegawaian', 'Pelaksana'), 'DIKBUD-BID-GTK');
        $this->buatPegawai('Rina Kusuma, A.Md.', '199508082020012008', 'PPPK', '1995-08-08', 'II/a', 'D3', $j('DIKBUD-BID-GTK', 'Pengelola Kepegawaian', 'Pelaksana'), 'DIKBUD-BID-GTK');

    }

    /**
     * Buat pegawai + penempatan aktif.
     */
    private function buatPegawai(
        string $nama,
        string $nip,
        string $jenis,
        string $tglLahir,
        string $golongan,
        string $pendidikan,
        ?int $jabatanId,
        string $kodeUnor
    ): void {
        if ($jabatanId === null) return;

        $unor = Unor::where('kode_unor', $kodeUnor)->first();
        if (!$unor) return;

        $pegawai = Pegawai::create([
            'nama'               => $nama,
            'nip'                => $nip,
            'jenis_kepegawaian'  => $jenis,
            'tanggal_lahir'      => $tglLahir,
            'golongan_pangkat'   => $golongan,
            'pendidikan'         => $pendidikan,
            'jabatan_id'         => $jabatanId,
        ]);

        PenempatanPegawai::create([
            'pegawai_id'      => $pegawai->id,
            'unor_id'         => $unor->id,
            'jabatan_id'      => $jabatanId,
            'tanggal_mulai'   => '2020-01-01',
            'tanggal_selesai' => null,
            'is_active'       => true,
        ]);
    }
}
