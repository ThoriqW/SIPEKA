# PROMPT_REFACTOR_SIPEKA.md

## Tujuan

Baca dan pahami `CLAUDE.md` secara menyeluruh sebelum melakukan pekerjaan apa pun.

`CLAUDE.md` adalah **single source of truth** untuk arsitektur final SIPEKA.

Saya ingin mengubah aplikasi dari arsitektur existing menuju arsitektur final pada `CLAUDE.md`.

## Konteks Data Development

Aplikasi masih tahap development.

Data berikut adalah data development/testing dan **tidak perlu dipertahankan** apabila menghambat arsitektur baru:

- Jabatan lama;
- Pegawai;
- SOTK lama;
- Kebutuhan;
- Bezetting/data turunan;
- Penempatan Pegawai;
- Proyeksi;
- relasi Jabatan-Pegawai;
- data development lain yang bergantung pada arsitektur lama.

Data yang **harus dipertahankan**:

1. User;
2. OPD yang valid.

Data OPD existing dapat digunakan/dimigrasikan sebagai dasar Master UNOR apabila sesuai.

Karena data Jabatan/Pegawai dan turunannya disposable, jangan membuat strategi backfill kompleks hanya untuk mempertahankan data development tersebut. Prioritaskan schema akhir yang bersih, sederhana, konsisten, dan mudah dipelihara.

Namun **jangan menghapus data atau tabel pada tahap analisis ini**.

Evaluasi apakah implementasi nantinya lebih aman menggunakan selective reset atau `php artisan migrate:fresh --seed` dengan backup/reseed/import ulang User dan OPD.

Jangan menjalankan truncate, drop, migrate:fresh, delete massal, atau tindakan destruktif tanpa persetujuan eksplisit saya.

# Batasan Tahap Ini

## HANYA ANALISIS DAN RENCANA IMPLEMENTASI

JANGAN melakukan perubahan terhadap repository atau database.

Jangan membuat/mengubah migration, model, controller, service, Livewire, Blade, route, seeder, test, data, atau commit.

Tahap ini hanya untuk membaca repository, memahami implementasi existing, membandingkannya dengan `CLAUDE.md`, membuat gap analysis, strategi database/reset data, implementation plan, test plan, dan risk analysis.

Setelah laporan selesai, **BERHENTI dan tunggu persetujuan saya**.

# 1. Audit Repository Existing

Analisis minimal:

1. migration dan schema database;
2. model Eloquent dan relationship;
3. seeder/master data;
4. controller;
5. Livewire component;
6. service class;
7. Form Request/validation;
8. Blade/frontend;
9. route;
10. test;
11. query SOTK;
12. query Kebutuhan;
13. query Bezetting;
14. query Proyeksi;
15. foreign key;
16. unique constraint;
17. index;
18. audit trail;
19. alur CRUD OPD/UNOR, Jabatan, Pegawai, Kebutuhan, dan Bezetting.

Cari kode yang masih mengasumsikan:

- Jabatan menjadi pembentuk utama hierarki SOTK;
- level Jabatan menentukan struktur organisasi;
- satu Jabatan/posisi hanya dapat ditempati satu pegawai;
- kebutuhan direpresentasikan dengan node/baris Jabatan berulang;
- Bezetting dihitung dari node SOTK;
- proyeksi menunjukkan jumlah pegawai yang tersisa;
- Kepala Sekolah/Kepala Puskesmas menjadi Jabatan ASN utama.

# 2. Arsitektur Final

Gunakan `CLAUDE.md` sebagai referensi lengkap.

Master utama:

1. Master UNOR
2. Master Jabatan
3. Master Tugas Tambahan

Tidak menggunakan entitas/master **Posisi Organisasi**.

## Master UNOR

UNOR membentuk struktur organisasi dan dapat memiliki parent UNOR.

Contoh:

```text
Pemerintah Kota Palu
└── BKPSDMD
    ├── Sekretariat
    └── Bidang Pengadaan, Informasi, dan Mutasi Kepegawaian
```

Puskesmas dan sekolah juga dapat menjadi UNOR.

SOTK tidak dibentuk berdasarkan level Jabatan ASN.

## Master Jabatan

Digunakan untuk SOTK, Pegawai, Kebutuhan, Bezetting, BUP, dan Proyeksi.

Jabatan Fungsional digunakan langsung sampai jenjangnya:

```text
Pranata Komputer Ahli Pertama
Pranata Komputer Ahli Muda
Pranata Komputer Ahli Madya
```

Jangan membuat parent/rumpun `Pranata Komputer` hanya untuk mengelompokkan jenjang.

Master Jabatan secara konseptual mendukung:

- nama jabatan;
- jenis jabatan;
- jenjang, nullable;
- sub-jabatan, nullable.

Gunakan nomenklatur existing jika lebih tepat dan tidak bertentangan dengan konsep ini.

## Sub-Jabatan

Sub-jabatan adalah **atribut terstruktur**, bukan hierarchy SOTK.

Contoh Guru:

```text
Nama Jabatan : Guru
Jenjang      : Ahli Muda
Sub Jabatan  : Matematika
Display      : Guru Matematika Ahli Muda
```

Contoh Dokter:

```text
Nama Jabatan : Dokter
Jenjang      : Ahli Madya
Sub Jabatan  : Spesialis Anak
Display      : Dokter Spesialis Anak Ahli Madya
```

Jangan membuat sub-jabatan menjadi parent/child organisasi.

# 3. SOTK

SOTK menjawab: “UNOR apa yang terdapat dalam organisasi dan Jabatan apa yang tersedia pada masing-masing UNOR?”

Contoh:

```text
BKPSDMD
└── Bidang Pengadaan
    ├── Kepala Bidang
    ├── Pranata Komputer Ahli Pertama
    ├── Pranata Komputer Ahli Muda
    ├── Pranata Komputer Ahli Madya
    └── Penelaah Teknis Kebijakan
```

Jumlah kebutuhan tidak membentuk node SOTK.

Jika `Pranata Komputer Ahli Pertama` membutuhkan 3 orang, SOTK tetap hanya mempunyai satu entri jabatan tersebut. Jumlah `3` disimpan sebagai data kebutuhan.

SOTK tidak bertanggung jawab menentukan atau menampilkan jumlah kebutuhan.

# 4. Pegawai dan Penempatan

Pegawai mempunyai:

- Jabatan → Master Jabatan;
- penempatan utama → UNOR;
- tugas tambahan jika ada.

Beberapa pegawai boleh mempunyai kombinasi UNOR + Jabatan yang sama.

Cari seluruh constraint/validation yang masih mengasumsikan satu Jabatan/posisi hanya dapat ditempati satu pegawai.

Jika existing mempunyai `penempatan_pegawai` dan berguna untuk riwayat, prioritaskan mempertahankannya.

# 5. Kebutuhan Pegawai

Kebutuhan menjawab: “Berapa pegawai pada Jabatan tertentu yang dibutuhkan pada suatu UNOR?”

Minimal:

```text
UNOR / konteks SOTK
Jabatan
Jumlah Kebutuhan
```

Jika existing membutuhkan tahun/periode, pertahankan bila relevan.

Contoh:

```text
UNOR      : Bidang Pengadaan
Jabatan   : Pranata Komputer Ahli Pertama
Kebutuhan : 3
```

Hanya satu record kebutuhan dengan nilai `3`.

Pelaksana:

```text
UNOR      : Bidang Pengadaan
Jabatan   : Penelaah Teknis Kebijakan
Kebutuhan : 4
```

Jangan membuat empat node/baris Jabatan.

# 6. Bezetting

```text
Bezetting = COUNT(pegawai aktif berdasarkan UNOR + Jabatan)
Selisih   = Bezetting - Kebutuhan
```

Interpretasi:

- negatif = kekurangan;
- 0 = sesuai;
- positif = kelebihan.

Jangan menghitung Bezetting dari node SOTK, jumlah kebutuhan, atau tugas tambahan.

# 7. Proyeksi Kebutuhan 5 Tahun

Proyeksi hanya menghitung kebutuhan yang muncul akibat pensiun.

```text
Proyeksi Tahun Y =
COUNT pegawai aktif pada UNOR + Jabatan
yang pensiun pada tahun Y
```

Asumsi:

```text
1 pegawai pensiun = kebutuhan pengganti 1 pegawai
```

Contoh:

```text
2026 → 1 pensiun
2027 → 0
2028 → 2 pensiun
2029 → 1 pensiun
2030 → 0
```

Hasil:

```text
2026 = 1
2027 = 0
2028 = 2
2029 = 1
2030 = 0
```

Angka adalah kebutuhan akibat pensiun pada masing-masing tahun, **bukan jumlah pegawai yang tersisa** dan bukan nilai kumulatif.

Jangan otomatis menghitung kenaikan jenjang, promosi, mutasi, rekrutmen, atau redistribusi.

# 8. Tugas Tambahan

Tugas tambahan terpisah dari Jabatan utama.

Kepala Sekolah:

```text
Jabatan utama : Guru Matematika Ahli Madya
UNOR           : SMP Negeri 1
Tugas tambahan : Kepala Sekolah
```

Kepala Puskesmas:

```text
Jabatan utama : Dokter Ahli Madya
UNOR           : Puskesmas Talise
Tugas tambahan : Kepala Puskesmas
```

Tugas tambahan tidak mengubah Jabatan maupun penempatan utama.

# 9. Gap Analysis

Klasifikasikan setiap bagian:

- **SUDAH SESUAI** → jangan diubah.
- **PERLU MODIFIKASI**.
- **PERLU DITAMBAHKAN**.
- **OBSOLETE** → kandidat cleanup setelah sistem baru stabil.

Untuk setiap gap jelaskan:

- kondisi existing;
- kondisi target;
- tabel/file terdampak;
- perubahan;
- dependency;
- risiko.

# 10. Database Impact

Jelaskan konkret:

- tabel yang dipertahankan;
- tabel yang dimodifikasi;
- tabel yang perlu ditambahkan;
- kolom baru;
- kolom obsolete;
- foreign key;
- unique constraint;
- index;
- data development yang aman di-reset;
- cara menjaga User dan OPD.

JANGAN membuat migration pada tahap ini.

# 11. Strategi Data Development

Bandingkan:

## Opsi A — Selective Reset

Pertahankan User dan OPD, reset data development yang bergantung pada arsitektur lama.

Jelaskan urutan, dependency FK, keuntungan, dan risiko.

## Opsi B — Fresh Database + Restore/Seed

Evaluasi:

```bash
php artisan migrate:fresh --seed
```

hanya jika User dan OPD dapat diamankan melalui seeder, export/import, backup, atau mekanisme aman lain.

Jelaskan cara mempertahankan User/OPD, keuntungan, dan risiko.

Rekomendasikan pendekatan paling sederhana, bersih, dan aman berdasarkan repository sebenarnya.

Jangan menjalankannya tanpa persetujuan saya.

# 12. Application Impact

Identifikasi file konkret yang terdampak pada:

- Model;
- Relationship;
- Controller;
- Service;
- Livewire;
- Form Request/Validation;
- Blade/UI;
- Route;
- Seeder;
- Factory;
- Test;
- Export;
- Policy/Gate.

# 13. Implementation Phases

Buat fase implementasi kecil dan aman. Sesuaikan dengan hasil audit.

Pertimbangkan:

1. Database dan Master Data
2. SOTK
3. Pegawai dan Penempatan
4. Kebutuhan
5. Bezetting
6. Proyeksi 5 Tahun
7. Tugas Tambahan
8. UI, Export, dan Integrasi
9. Cleanup

Untuk setiap fase jelaskan:

- tujuan;
- tabel/file terdampak;
- migration;
- backend;
- frontend;
- test;
- dependency;
- risiko;
- acceptance criteria.

# 14. Test Plan

Minimal:

### Skenario JF

```text
UNOR      : Bidang Pengadaan
Jabatan   : Pranata Komputer Ahli Pertama
Kebutuhan : 3
```

Pastikan satu entri SOTK, kebutuhan bernilai 3, dan beberapa pegawai dapat menempati UNOR + Jabatan tersebut.

### Skenario Pelaksana

```text
Jabatan   : Penelaah Teknis Kebijakan
Kebutuhan : 4
```

Pastikan satu entri Jabatan dan satu data kebutuhan bernilai 4.

### Skenario Guru

```text
UNOR        : SMP Negeri 1
Nama Jabatan: Guru
Jenjang     : Ahli Madya
Sub Jabatan : Matematika
Display     : Guru Matematika Ahli Madya
Tugas Tambahan: Kepala Sekolah
```

Tugas tambahan tidak mengubah Jabatan/UNOR utama.

### Skenario Dokter

```text
UNOR        : Puskesmas Talise
Nama Jabatan: Dokter
Jenjang     : Ahli Madya
Sub Jabatan : Spesialis Anak
```

Pastikan kebutuhan, Bezetting, dan proyeksi dapat dibedakan dari sub-jabatan Dokter lain.

### Skenario Bezetting

```text
Kebutuhan = 3
Pegawai aktif = 2
Bezetting = 2
Selisih = -1
```

### Skenario Proyeksi

Jika pensiun:

```text
2026 = 1
2027 = 0
2028 = 2
2029 = 1
2030 = 0
```

hasil proyeksi harus persis sama dan bukan jumlah pegawai tersisa.

# 15. Risk Analysis

Identifikasi risiko:

- kehilangan User;
- kehilangan OPD;
- mapping OPD → UNOR;
- FK saat reset;
- duplicate Master Jabatan;
- duplicate Jabatan pada SOTK;
- constraint satu Jabatan/posisi = satu pegawai;
- Bezetting salah;
- Selisih terbalik;
- BUP/proyeksi salah;
- sub-jabatan Guru/Dokter tercampur;
- tugas tambahan mengganti Jabatan utama;
- UI masih memakai asumsi lama.

Berikan mitigasi.

# 16. Format Laporan

Gunakan urutan:

## A. Executive Summary
## B. Arsitektur Existing
## C. Gap Analysis
## D. Database Impact
## E. Data Reset Recommendation
## F. Application Impact
## G. Implementation Phases
## H. Test Plan
## I. Risk Analysis
## J. Recommended Execution Plan

# STOP CONDITION

Setelah laporan selesai, **BERHENTI**.

Jangan mengubah kode, membuat migration, menghapus data, menjalankan `migrate:fresh`, reset database, membuat commit, atau memulai Phase 1.

Tunggu persetujuan eksplisit saya.

Jika menemukan konflik besar antara repository existing dan `CLAUDE.md`, jelaskan konflik tersebut dan jangan mengambil keputusan arsitektur besar secara sepihak.
