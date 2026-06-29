## Project Overview

"Sistem Perencanaan Kebutuhan Kota Palu" — aplikasi perencanaan kebutuhan pegawai ASN untuk Pemerintah Kota Palu. Tujuan: mengelola data pegawai, jabatan, dan OPD; menghitung kebutuhan vs ketersediaan pegawai (bezetting); serta memproyeksikan pensiun dan kebutuhan pegawai 5 tahun ke depan secara per tahun.

- Pengguna: **BKD** (super admin, akses semua) dan **admin OPD** (akses hanya OPD-nya sendiri).
- Skala: puluhan OPD, ribuan pegawai, puluhan–ratusan pengguna.

## Golden Rules (baca dulu sebelum coding)

- **jabatan ↔ pegawai adalah SATU-KE-BANYAK.** Satu jabatan dapat diisi banyak pegawai; satu pegawai menempati satu jabatan.
- **Semua perhitungan dilakukan di sisi server.**
- **Proyeksi dihitung berbasis tahun berjalan (tahun kalender).**
- Penulisan istilah baku: **"Bezetting"** (konsisten di seluruh kode & UI).
- **Kepala OPD = baris Level 1.** Dalam tabel pohon Kebutuhan & Bezetting, jabatan **Kepala OPD** adalah simpul akar (root) per OPD dan menempati **Level 1** (mewakili Nama OPD). Seluruh jabatan lain bercabang dari sana melalui `induk_jabatan`.
- **Hanya jabatan Struktural yang boleh menjadi induk (parent).** Jabatan Fungsional dan Pelaksana **tidak boleh** memiliki anak (tidak bisa dipilih sebagai `induk_jabatan_id`). Hirarki: Struktural → Fungsional/Pelaksana (level 3→4), atau Struktural → Struktural (level 1→2→3).
- Alur kerja: **rencana dulu, baru kode** (lihat Working Agreement).

## Tech Stack

- **Backend:** Laravel (PHP), versi LTS terbaru (Laravel 11).
- **Database:** MySQL / MariaDB.
- **Frontend:** Laravel Blade + Livewire / Alpine.js (interaktivitas ringan, bukan SPA).
- **Auth & Roles:** Laravel auth + role-based access control (BKD super admin; admin OPD dibatasi ke OPD-nya).
- **Charts:** library chart yang kompatibel Blade untuk dashboard.

## Data Model

Tabel inti (gunakan migrations untuk semua skema):

- **opd**: `id`, `nama_opd`, `kode_opd`.
- **pegawai**: `id`, `nama`, `nip` (unik, divalidasi), `jenis_kepegawaian` (PNS | PPPK), `tanggal_lahir`, `golongan_pangkat` (enum I/a … IV/e), `pendidikan` (SD … S3), `jenjang` (Pelaksana, Ahli Pertama, Ahli Muda, Ahli Madya, Ahli Utama, Keterampilan, Guru, Pimpinan Tinggi), `opd_id` (FK), `jabatan_id` (FK, nullable).
- **jabatan**: `id`, `nama_jabatan`, `kode_jabatan`, `jenis_jabatan` (Struktural | Fungsional | Pelaksana), `kelas_jabatan` (int), `kebutuhan` (int, **hanya diisi untuk Fungsional & Pelaksana**; NULL untuk Struktural), `opd_id` (FK), `induk_jabatan_id` (self-FK, nullable).
- **Tabel pendukung:** users & roles dan tabel audit trail.

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
  60  jika jenjang ∈ {"Ahli Madya", "Pimpinan Tinggi"}
        ATAU (jenis_kepegawaian = "PPPK" DAN jenjang = "Guru")
  58  selain itu

Tanggal Pensiun = tanggal_lahir + BUP tahun

Bezetting(jabatan) = COUNT(pegawai pada jabatan tsb)   // head count murni
Selisih(jabatan)   = Bezetting - kebutuhan
Level(jabatan)     = kedalaman rantai induk_jabatan (0..4); Level 0 = root "Instansi Pemerintah Kota Palu", Level 4 = maksimal (hard constraint)

Proyeksi pensiun per tahun (berbasis TAHUN BERJALAN / tahun kalender):
  T = YEAR(today())   // tahun berjalan saat ini, mis. 2026
  Pensiun Thn N = jumlah pegawai dgn YEAR(Tanggal Pensiun) = T + (N - 1)   (N = 1..5)

Proyeksi kebutuhan per tahun:
  Kebutuhan Thn 1 = max(kebutuhan - Bezetting, 0) + Pensiun Thn 1
  Kebutuhan Thn N = Pensiun Thn N   (N = 2..5)
  // Asumsi penggantian 1:1; dokumentasikan & buat mudah diganti.
```

## Hirarki & Level (tree table)

Level ditentukan oleh `induk_jabatan`. **Maksimal 4 level (hard constraint).** Struktur level di bawah akar root:

- **Root (Level 0)** — **"Instansi Pemerintah Kota Palu"** (baris akar untuk layar Bezetting seluruh OPD)
- **Level 1** — **Kepala OPD** (baris Level 1 dalam tabel pohon = jabatan Kepala OPD per OPD; bukan baris OPD terpisah)
- **Level 2** — Sekretariat, Bidang & Jabatan Fungsional & Pelaksana
- **Level 3** — Sub Bagian / Jabatan Fungsional & Pelaksana
- **Level 4** — Jabatan Fungsional & Pelaksana (level terdalam, tidak boleh ada anak lebih lanjut)

Layar **Kebutuhan** dan **Bezetting** harus tampil sebagai **tabel pohon yang bisa di-expand** hingga 4 level. Prioritaskan UX expand/collapse yang mulus.

## Screens / Menus

- **Dashboard:** total PNS dan total PPPK + grafik komposisi PNS vs PPPK.
- **Pegawai:** daftar + CRUD identitas pegawai.
- **Jabatan:** daftar + CRUD. `kebutuhan` hanya berlaku untuk jabatan **Fungsional** dan **Pelaksana** (diinput manual). Jabatan **Struktural** tidak memiliki `kebutuhan` (NULL); kolom Bezetting dan Selisih untuk jabatan struktural tetap ditampilkan berdasarkan data pegawai yang menempati.
- **OPD:** daftar + CRUD. Layar OPD menampilkan `nama_opd` dan `kode_opd`, serta **daftar turunan jabatan struktural** (nama jabatan struktural & kode jabatan struktural milik OPD tsb). Daftar ini diturunkan otomatis dari tabel `jabatan` yang `jenis_jabatan = Struktural` dan `opd_id` = OPD ini.
- **Kebutuhan:** tabel pohon — kolom: No, Jabatan, Kelas, Kebutuhan, Bezetting, Selisih, NIP, Nama, serta Kebutuhan Thn 1–5.
- **Bezetting:** tabel pohon **seluruh OPD** dalam satu tampilan — akar tree adalah **"Instansi Pemerintah Kota Palu"**, di bawahnya bercabang per OPD (level 1 = Kepala OPD). Kolom: No, Jabatan, Kelas Jabatan, Kebutuhan, Bezetting, Proyeksi (pensiun & kebutuhan Thn 1–5), NIP, Nama.

## Validasi & Kualitas Data

- `nip` unik + validasi format 18-digit; tawarkan **auto-fill `tanggal_lahir` dari NIP** (8 digit pertama = YYYYMMDD). Semua pegawai menggunakan format NIP 18-digit standar BKN.
- `golongan_pangkat` sebagai dropdown enum (bukan teks bebas).
- Standarisasi penulisan **"Bezetting"**.
- **Audit trail** untuk create/update/delete pada tabel utama.
- **Export** Excel untuk tabel Kebutuhan dan Bezetting.

## Security

- Role-based access; admin OPD dibatasi via policy/middleware ke OPD miliknya.
- Praktik OWASP dasar, proteksi CSRF, validasi sisi server, secret disimpan di `.env` (tidak di-commit).
- Sediakan HTTPS/TLS, backup database otomatis + prosedur restore, serta logging/monitoring dasar.

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