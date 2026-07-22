## Project Overview

"Sistem Perencanaan Kebutuhan Kota Palu" — aplikasi perencanaan kebutuhan pegawai ASN untuk Pemerintah Kota Palu. Tujuan: mengelola data pegawai, jabatan, dan OPD; menghitung kebutuhan vs ketersediaan pegawai (bezetting); serta memproyeksikan pensiun dan kebutuhan pegawai 5 tahun ke depan secara per tahun.

- Pengguna: **BKD** (super admin, akses penuh). Tidak ada admin OPD — semua user didaftarkan oleh super admin.
- Skala: puluhan OPD, ribuan pegawai, puluhan–ratusan pengguna.

## Golden Rules (baca dulu sebelum coding)

- **jabatan ↔ pegawai adalah SATU-KE-BANYAK.** Satu jabatan dapat diisi banyak pegawai; satu pegawai menempati satu jabatan.
- **Semua perhitungan dilakukan di sisi server.**
- **Proyeksi dihitung berbasis tahun berjalan (tahun kalender).**
- Penulisan istilah baku: **"Bezetting"** (konsisten di seluruh kode & UI).
- **Kepala OPD = baris Level 1.** Dalam tabel pohon Kebutuhan & Bezetting, jabatan **Kepala OPD** adalah simpul akar (root) per OPD dan menempati **Level 1** (mewakili Nama OPD). Seluruh jabatan lain bercabang dari sana melalui `induk_jabatan`.
- **Hanya jabatan Struktural yang boleh menjadi induk (parent).** Jabatan Fungsional dan Pelaksana **tidak boleh** memiliki anak (tidak bisa dipilih sebagai `induk_jabatan_id`). Hirarki: Struktural → Fungsional/Pelaksana (level 3→4), atau Struktural → Struktural (level 1→2→3).
- **Satu OPD hanya boleh memiliki satu JPTP (Jabatan Pimpinan Tinggi Pratama).** Validasi server-side mencegah duplikasi. Unique constraint di database sebagai lapisan tambahan.
- **Induk jabatan harus satu OPD dengan anaknya (cross-OPD prevention).** Tidak boleh jabatan OPD A menginduk ke jabatan OPD B.
- **Tidak boleh circular reference pada induk jabatan.** Induk tidak boleh merupakan turunan (anak/cucu) dari jabatan yang sedang diedit.
- Alur kerja: **rencana dulu, baru kode** (lihat Working Agreement).

## Tech Stack

- **Backend:** Laravel (PHP), versi LTS terbaru (Laravel 11).
- **Database:** MySQL / MariaDB.
- **Frontend:** Laravel Blade + Livewire / Alpine.js (interaktivitas ringan, bukan SPA).
- **Auth & Roles:** Laravel auth. Hanya role `bkd` (super admin). User didaftarkan oleh BKD.
- **Charts:** library chart yang kompatibel Blade untuk dashboard.

## Data Model

Tabel inti (gunakan migrations untuk semua skema):

- **opd**: `id`, `nama_opd`, `kode_opd`.
- **pegawai**: `id`, `nama`, `nip` (unik, divalidasi 18-digit), `jenis_kepegawaian` (PNS | PPPK), `tanggal_lahir`, `golongan_pangkat` (enum I/a … IV/e), `pendidikan` (SD … S3), `jenjang` (Pelaksana, Ahli Pertama, Ahli Muda, Ahli Madya, Ahli Utama, Keterampilan, Guru, Pimpinan Tinggi Pratama), `opd_id` (FK), `jabatan_id` (FK, nullable).
- **jabatan**: `id`, `nama_jabatan`, `kode_jabatan`, `jenis_jabatan` (Struktural | Fungsional | Pelaksana), `kelas_jabatan` (int), `kebutuhan` (int, diinput manual untuk Fungsional & Pelaksana; **selalu 1** untuk Struktural), `opd_id` (FK), `induk_jabatan_id` (self-FK, nullable).
- **Tabel pendukung:** users & roles, master_jabatan, dan tabel audit trail.

**Relasi:**

- opd 1—* jabatan
- opd 1—* pegawai
- jabatan 1—* pegawai ← **satu-ke-banyak**
- jabatan 1—* jabatan (induk → sub, membentuk hirarki)

## Business Logic

Letakkan logika ini di service class khusus dan/atau query DB, dan wajib diberi unit test.

```
BUP (Batas Usia Pensiun, dalam tahun):
  65  jika jenjang = "Ahli Utama"
  60  jika jenjang ∈ {"Ahli Madya", "Pimpinan Tinggi Pratama"}
        ATAU (jenis_kepegawaian = "PPPK" DAN jenjang = "Guru")
        ATAU (jenis_kepegawaian = "PPPK" DAN nama jabatan mengandung "Guru")
  58  selain itu
  // Deteksi Guru: karena enum Jenjang tidak memiliki case "Guru",
  // BupCalculator juga mendeteksi dari str_contains(nama_jabatan, 'Guru').

Tanggal Pensiun = tanggal_lahir + BUP tahun

Bezetting(jabatan) = COUNT(pegawai pada jabatan tsb)   // head count murni
Selisih(jabatan)   = Bezetting - kebutuhan
Level(jabatan)     = kedalaman rantai induk_jabatan (0..4); Level 0 = root "Pemerintah Kota Palu", Level 4 = maksimal (hard constraint)

Proyeksi pensiun per tahun (berbasis TAHUN BERJALAN / tahun kalender):
  T = YEAR(today())   // tahun berjalan saat ini, mis. 2026
  Pensiun Thn N = jumlah pegawai dgn YEAR(Tanggal Pensiun) = T + (N - 1)   (N = 1..5)

Proyeksi kebutuhan per tahun:
  Kebutuhan Thn N = Pensiun Thn N   (N = 1..5)
  // Hanya menghitung dari pegawai yang pensiun, tanpa selisih.
  // Asumsi penggantian 1:1; dokumentasikan & buat mudah diganti.
```

## Hirarki & Level (tree table)

Level ditentukan oleh `induk_jabatan`. **Maksimal 4 level (hard constraint).** Struktur level di bawah akar root:

- **Root (Level 0)** — **"Pemerintah Kota Palu"** (baris akar untuk layar Bezetting seluruh OPD)
- **Level 1** — **Kepala OPD** (baris Level 1 dalam tabel pohon = jabatan Kepala OPD per OPD; bukan baris OPD terpisah)
- **Level 2** — Sekretariat, Bidang & Jabatan Fungsional & Pelaksana
- **Level 3** — Sub Bagian / Jabatan Fungsional & Pelaksana
- **Level 4** — Jabatan Fungsional & Pelaksana (level terdalam, tidak boleh ada anak lebih lanjut)

Layar **Kebutuhan** dan **Bezetting** harus tampil sebagai **tabel pohon yang bisa di-expand** hingga 4 level. Prioritaskan UX expand/collapse yang mulus.

## Screens / Menus

- **Dashboard:** total PNS dan total PPPK + grafik komposisi PNS vs PPPK.
- **Pegawai:** daftar + CRUD identitas pegawai.
- **Jabatan:** daftar + CRUD. `kebutuhan` bernilai **1 (tetap)** untuk jabatan **Struktural**; untuk **Fungsional** dan **Pelaksana** diinput manual.
- **OPD:** daftar + CRUD. Layar OPD menampilkan `nama_opd` dan `kode_opd`, serta **daftar turunan jabatan struktural** (nama jabatan struktural & kode jabatan struktural milik OPD tsb). Daftar ini diturunkan otomatis dari tabel `jabatan` yang `jenis_jabatan = Struktural` dan `opd_id` = OPD ini.
- **Kebutuhan:** tabel pohon — kolom: No, Jabatan, Kelas, Kebutuhan, Bezetting, Selisih, NIP, Nama, serta Kebutuhan Thn 1–5.
- **Bezetting:** tabel pohon **seluruh OPD** dalam satu tampilan — akar tree adalah **"Pemerintah Kota Palu"**, di bawahnya bercabang per OPD (level 1 = Kepala OPD). Kolom: No, Jabatan, Kelas Jabatan, Kebutuhan, Bezetting, Proyeksi (pensiun & kebutuhan Thn 1–5), NIP, Nama.

## Validasi & Kualitas Data

- `nip` unik + validasi format 18-digit; tawarkan **auto-fill `tanggal_lahir` dari NIP** (8 digit pertama = YYYYMMDD). Semua pegawai menggunakan format NIP 18-digit standar BKN.
- `golongan_pangkat` sebagai dropdown enum (bukan teks bebas).
- Standarisasi penulisan **"Bezetting"**.
- **JPTP** (Jabatan Pimpinan Tinggi Pratama) wajib unik per OPD — dicek di server + unique constraint di database.
- **Induk jabatan** wajib satu OPD dengan anaknya (cross-OPD prevention).
- **Circular reference** dicegah: saat edit, induk tidak boleh merupakan turunan dari jabatan itu sendiri.
- **Struktural berturunan** tidak boleh diubah jenisnya ke Fungsional/Pelaksana.
- **Audit trail** untuk create/update/delete pada tabel utama.
- **Export** Excel untuk tabel Kebutuhan dan Bezetting.

## Security

- Role-based access; hanya role `bkd` yang dapat mengelola User dan Master Jabatan.
- Semua user terautentikasi dapat mengakses CRUD OPD, Pegawai, Jabatan, Kebutuhan, Bezetting.
- Praktik OWASP dasar, proteksi CSRF, validasi sisi server, secret disimpan di `.env` (tidak di-commit).
- **Blade:** Gunakan `@json()` untuk output data ke JavaScript. JANGAN gunakan `{!! !!}` untuk data yang berasal dari database/input user.
- Sediakan HTTPS/TLS, backup database otomatis + prosedur restore, serta logging/monitoring dasar.

## Service Classes

| Service | Fungsi |
|---------|--------|
| `BupCalculator` | Hitung BUP (65/60/58) dan tanggal pensiun. Deteksi Guru dari jenjang ATAU nama jabatan. |
| `ProjectionService` | Hitung proyeksi pensiun per jabatan (`hitungProyeksiPensiunPerJabatan`) + label tahun. |
| `FlattenedTreeService` | Bangun flat tree depth-first untuk layar Kebutuhan & Bezetting. |
| `KodeJabatanGenerator` | Auto-generate kode jabatan format `{KODE_OPD}-{SINGKATAN}-{NOMOR}`. |
| `NipParser` | Ekstrak tanggal lahir dari 8 digit pertama NIP 18-digit. |

## Konvensi & Perintah (Laravel)

- Semua skema lewat **migrations**; **seeders** untuk data referensi + data contoh.
- Relasi Eloquent mengikuti Data Model di atas.
- Logika proyeksi di **service class** terpisah dan teruji.
- Tulis test untuk: BUP, bucketing pensiun per tahun, Bezetting, dan derivasi Level.

```bash
composer install
php artisan migrate --seed
php artisan test
npm run dev      # atau: npm run build
```

## Working Agreement (rencana dulu, baru kode)

1. Rencana + arsitektur.
2. Skema database + ER diagram + outline migration.
3. Daftar layar + deskripsi wireframe.
4. Baru tulis kode setelah disetujui.
