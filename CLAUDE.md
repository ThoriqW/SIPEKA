# CLAUDE.md — SIPEKA

## Project Overview

**SIPEKA — Sistem Perencanaan Kebutuhan Kota Palu** adalah aplikasi perencanaan kebutuhan pegawai ASN untuk Pemerintah Kota Palu.

Tujuan utama sistem:

- menyusun dan menampilkan Struktur Organisasi dan Tata Kerja (SOTK);
- mengelola data pegawai dan penempatannya;
- mengelola kebutuhan pegawai;
- menghitung Bezetting;
- menghitung selisih kebutuhan dan ketersediaan pegawai;
- memproyeksikan kebutuhan pegawai akibat pensiun untuk 5 tahun ke depan;
- mendukung Analisis Beban Kerja (ABK);
- menangani tugas tambahan seperti Kepala Sekolah dan Kepala Puskesmas tanpa mengubah Jabatan ASN utama pegawai.

Pengguna utama adalah **BKD/BKPSDMD** sebagai super admin. Tidak ada admin OPD. Seluruh user didaftarkan oleh super admin.

Skala sistem: puluhan OPD/UNOR, ribuan pegawai, dan puluhan–ratusan pengguna.

---

# Golden Rules

Aturan berikut merupakan sumber kebenaran utama dan harus dibaca sebelum melakukan perubahan kode.

1. **SOTK tidak dibangun berdasarkan level atau hierarki Jabatan ASN.**
2. **UNOR adalah pembentuk utama pohon organisasi.**
3. Jabatan yang tersedia pada suatu UNOR mengacu pada **Master Jabatan**.
4. **Tidak ada lagi entitas/master Posisi Organisasi yang terpisah.**
5. Master data utama sistem hanya:
   - Master UNOR;
   - Master Jabatan;
   - Master Tugas Tambahan.
6. **Master Jabatan digunakan sebagai referensi jabatan resmi dan jabatan yang tersedia pada UNOR.**
7. Jabatan Fungsional ditampilkan langsung sampai jenjangnya. Tidak perlu membuat node/rumpun tanpa jenjang seperti `Pranata Komputer` hanya untuk mengelompokkan `Ahli Pertama`, `Ahli Muda`, dan `Ahli Madya`.
8. Jabatan tertentu seperti Guru dan Dokter dapat mempunyai **sub-jabatan sebagai atribut terstruktur**, bukan sebagai level/hierarki SOTK.
9. Jumlah kebutuhan pegawai **tidak direpresentasikan dengan membuat jabatan/node berulang**.
10. Satu jabatan pada satu UNOR dapat memiliki kebutuhan lebih dari satu orang.
11. Beberapa pegawai dapat memiliki UNOR dan Jabatan yang sama.
12. **Bezetting adalah head count pegawai aktif berdasarkan UNOR + Jabatan.**
13. `Selisih = Bezetting - Kebutuhan`.
14. Nilai selisih:
    - negatif = kekurangan;
    - 0 = sesuai;
    - positif = kelebihan.
15. **Proyeksi 5 tahun saat ini hanya menghitung kebutuhan yang timbul akibat pegawai pensiun.**
16. Angka pada kolom proyeksi suatu tahun adalah jumlah pegawai yang pensiun pada tahun tersebut, sehingga menghasilkan kebutuhan pengganti dengan rasio 1:1.
17. Proyeksi tidak menunjukkan jumlah pegawai existing yang masih tersisa.
18. Proyeksi tidak otomatis menghitung kenaikan jenjang, promosi, mutasi, atau rekrutmen.
19. Tugas tambahan tidak mengubah Jabatan ASN maupun penempatan utama pegawai.
20. Semua perhitungan bisnis dilakukan di sisi server.
21. Penulisan istilah baku di seluruh kode dan UI adalah **“Bezetting”**.
22. Semua perubahan skema database dilakukan melalui migration baru. Jangan mengedit migration yang sudah pernah dijalankan di environment lain.
23. Prioritaskan perubahan minimal yang tetap konsisten secara arsitektur dan tidak merusak data existing.
24. Alur kerja pengembangan: **analisis/rencana terlebih dahulu, baru implementasi kode**.

---

# Tech Stack

- **Backend:** Laravel 11.
- **Database:** MySQL / MariaDB.
- **Frontend:** Laravel Blade + Livewire / Alpine.js.
- **Auth:** Laravel authentication.
- **Role utama:** `bkd` / super admin.
- **Charts:** library yang kompatibel dengan Blade/Livewire.
- **Testing:** PHPUnit / Laravel test suite.

Hindari menambah kompleksitas frontend berupa SPA apabila kebutuhan dapat diselesaikan dengan Blade, Livewire, dan Alpine.js.

---

# Konsep Domain

## 1. UNOR

UNOR adalah unit organisasi yang membentuk struktur organisasi.

UNOR menjawab:

> “Pegawai bekerja pada unit organisasi mana?”

Contoh:

- Pemerintah Kota Palu
- BKPSDMD
- Dinas Pendidikan
- Dinas Kesehatan
- Sekretariat
- Bidang Pengadaan, Informasi, dan Mutasi Kepegawaian
- Puskesmas Talise
- SMP Negeri 1

UNOR dapat mempunyai parent UNOR sehingga membentuk pohon organisasi.

Contoh:

```text
Pemerintah Kota Palu
└── BKPSDMD
    ├── Sekretariat
    └── Bidang Pengadaan, Informasi, dan Mutasi Kepegawaian
```

Puskesmas dan sekolah juga merupakan UNOR dan tidak memerlukan rekayasa hierarki Jabatan ASN untuk dapat masuk ke SOTK.

---

## 2. Master Jabatan

Master Jabatan adalah sumber referensi jabatan yang digunakan oleh sistem.

Master Jabatan dapat digunakan untuk:

- menentukan jabatan pegawai;
- menentukan jabatan yang tersedia pada suatu UNOR/SOTK;
- menentukan kebutuhan pegawai;
- menghitung Bezetting;
- menentukan BUP;
- menghitung proyeksi kebutuhan akibat pensiun.

Secara konseptual Master Jabatan minimal dapat memiliki:

- nama jabatan;
- jenis jabatan;
- jenjang, nullable;
- sub-jabatan, nullable;
- atribut referensi lain yang memang sudah dibutuhkan implementasi.

### Jenis Jabatan

Jenis jabatan mengikuti kebutuhan data ASN, misalnya:

- Struktural/Manajerial;
- Fungsional;
- Pelaksana.

Gunakan nomenklatur existing jika implementasi hasil refactoring sudah menggunakan istilah yang lebih sesuai. Jangan mengganti nama hanya demi mengikuti contoh dokumentasi ini.

### Jenjang

Jenjang digunakan jika jabatan memang mempunyai jenjang.

Contoh:

```text
Pranata Komputer | Fungsional | Ahli Pertama
Pranata Komputer | Fungsional | Ahli Muda
Pranata Komputer | Fungsional | Ahli Madya
```

Pada UI, nama dapat ditampilkan sebagai:

```text
Pranata Komputer Ahli Pertama
Pranata Komputer Ahli Muda
Pranata Komputer Ahli Madya
```

Tidak perlu membuat record/node tambahan `Pranata Komputer` tanpa jenjang hanya untuk menjadi parent dari ketiga jabatan tersebut.

### Sub-Jabatan

`sub_jabatan` adalah atribut terstruktur untuk jabatan yang membutuhkan rincian tambahan.

Sub-jabatan **bukan parent/child pembentuk SOTK**.

Contoh Guru:

```text
nama_jabatan : Guru
jenis        : Fungsional
jenjang      : Ahli Muda
sub_jabatan  : Matematika
```

Nama tampilannya dapat menjadi:

```text
Guru Matematika Ahli Muda
```

Contoh Dokter:

```text
nama_jabatan : Dokter
jenis        : Fungsional
jenjang      : Ahli Madya
sub_jabatan  : Spesialis Anak
```

Nama tampilannya dapat menjadi:

```text
Dokter Spesialis Anak Ahli Madya
```

Jangan membuat sub-jabatan menjadi level organisasi seperti:

```text
Dokter
└── Spesialis Anak
    └── Ahli Madya
```

kecuali ada keputusan arsitektur baru yang secara eksplisit mengubah aturan ini.

---

## 3. Master Tugas Tambahan

Master Tugas Tambahan berisi peran tambahan yang dapat dijalankan pegawai tanpa mengganti Jabatan ASN dan penempatan utamanya.

Contoh:

- Kepala Sekolah;
- Kepala Puskesmas.

Tugas tambahan menjawab:

> “Peran tambahan apa yang dijalankan pegawai tanpa mengganti jabatan dan penempatan utamanya?”

Contoh:

```text
Pegawai       : A
Jabatan       : Guru Ahli Madya
UNOR          : SMP Negeri 1
Tugas Tambahan: Kepala Sekolah
```

Jabatan pegawai tetap `Guru Ahli Madya`.

Contoh:

```text
Pegawai       : B
Jabatan       : Dokter Ahli Madya
UNOR          : Puskesmas Talise
Tugas Tambahan: Kepala Puskesmas
```

Jabatan pegawai tetap `Dokter Ahli Madya`.

---

# SOTK

SOTK menunjukkan:

> “UNOR apa yang terdapat dalam organisasi dan jabatan apa yang tersedia pada masing-masing UNOR?”

SOTK tidak bertanggung jawab menentukan jumlah kebutuhan pegawai.

Secara konseptual:

```text
UNOR
├── UNOR
├── Jabatan
├── Jabatan
└── Jabatan
```

Contoh:

```text
BKPSDMD
└── Bidang Pengadaan, Informasi, dan Mutasi Kepegawaian
    ├── Kepala Bidang
    ├── Pranata Komputer Ahli Pertama
    ├── Pranata Komputer Ahli Muda
    ├── Pranata Komputer Ahli Madya
    └── Penelaah Teknis Kebijakan
```

Jika kebutuhan `Pranata Komputer Ahli Pertama = 3`, SOTK tetap hanya memiliki satu entri:

```text
Pranata Komputer Ahli Pertama
```

JANGAN membuat:

```text
Pranata Komputer Ahli Pertama
Pranata Komputer Ahli Pertama
Pranata Komputer Ahli Pertama
```

Jumlah `3` merupakan data kebutuhan, bukan jumlah node SOTK.

Contoh sekolah:

```text
SMP Negeri 1
├── Guru Matematika Ahli Pertama
├── Guru Matematika Ahli Muda
├── Guru Bahasa Indonesia Ahli Pertama
└── Guru IPA Ahli Muda
```

Contoh Puskesmas:

```text
Puskesmas Talise
├── Dokter Ahli Pertama
├── Dokter Spesialis Anak Ahli Madya
├── Perawat Ahli Pertama
└── Bidan Ahli Pertama
```

Jangan kembali menggunakan level jabatan struktural sebagai pembentuk hierarki SOTK.

---

# Pegawai dan Penempatan

Pegawai mempunyai Jabatan ASN yang mengacu pada Master Jabatan serta penempatan utama pada UNOR.

Secara konseptual:

```text
Pegawai
├── Jabatan → Master Jabatan
├── Penempatan Utama → UNOR
└── Tugas Tambahan → Master Tugas Tambahan (opsional)
```

Jika implementasi existing sudah mempunyai tabel khusus `penempatan_pegawai`, pertahankan apabila tabel tersebut berguna untuk integritas data dan/atau riwayat penempatan.

Jangan memindahkan kembali data penempatan langsung ke tabel pegawai apabila hal tersebut menghilangkan kemampuan existing untuk menyimpan riwayat.

Contoh:

```text
Nama    : Moh Thoriq
Jabatan : Pranata Komputer Ahli Pertama
UNOR    : Bidang Pengadaan, Informasi, dan Mutasi Kepegawaian
```

Beberapa pegawai boleh mempunyai kombinasi UNOR + Jabatan yang sama.

Tidak boleh ada unique constraint yang secara tidak sengaja membatasi satu Jabatan pada satu UNOR hanya dapat ditempati satu pegawai.

---

# Kebutuhan Pegawai

Kebutuhan menjawab:

> “Berapa pegawai pada Jabatan tertentu yang dibutuhkan pada suatu UNOR?”

Data kebutuhan minimal mempunyai dimensi:

- UNOR / konteks SOTK;
- Jabatan;
- tahun/periode kebutuhan jika implementasi memang menyimpan periode;
- jumlah kebutuhan.

Nama tabel dan kolom harus menyesuaikan implementasi existing hasil refactoring. Jangan membuat tabel baru atau mengganti nomenklatur jika struktur existing sudah dapat memenuhi konsep ini dengan baik.

Contoh:

```text
UNOR    : Bidang Pengadaan
Jabatan : Pranata Komputer Ahli Pertama
Kebutuhan: 3
```

Hanya terdapat satu baris kebutuhan dengan nilai `3`.

Untuk Pelaksana:

```text
UNOR    : Bidang Pengadaan
Jabatan : Penelaah Teknis Kebijakan
Kebutuhan: 4
```

Hanya terdapat satu baris dengan kebutuhan `4`.

Untuk Guru:

```text
UNOR    : SMP Negeri 1
Jabatan : Guru Matematika Ahli Pertama
Kebutuhan: 4
```

Untuk Dokter:

```text
UNOR    : Puskesmas Talise
Jabatan : Dokter Spesialis Anak Ahli Madya
Kebutuhan: 2
```

---

# Bezetting

Bezetting adalah jumlah pegawai aktif yang tersedia pada kombinasi:

```text
UNOR + Jabatan
```

Formula:

```text
Bezetting = COUNT(pegawai aktif pada UNOR dan Jabatan tersebut)
```

Jangan menghitung Bezetting dari:

- jumlah node SOTK;
- jumlah record kebutuhan;
- jumlah tugas tambahan.

Contoh:

```text
UNOR: Bidang Pengadaan
Jabatan: Pranata Komputer Ahli Pertama

Pegawai A → Pranata Komputer Ahli Pertama
Pegawai B → Pranata Komputer Ahli Pertama

Bezetting = 2
```

Jika:

```text
Kebutuhan = 3
Bezetting = 2
```

maka:

```text
Selisih = Bezetting - Kebutuhan
        = 2 - 3
        = -1
```

Artinya kekurangan 1 pegawai.

---

# Proyeksi Kebutuhan 5 Tahun

## Prinsip

Proyeksi 5 tahun saat ini **hanya menghitung kebutuhan yang timbul akibat pensiun**.

Tidak menghitung:

- jumlah pegawai existing yang masih tersisa;
- kenaikan jenjang otomatis;
- promosi;
- mutasi;
- rekrutmen;
- redistribusi;
- perubahan kebutuhan organisasi di masa depan;
- faktor lain yang belum dimodelkan secara eksplisit.

Untuk setiap Jabatan pada suatu UNOR:

```text
Proyeksi Kebutuhan Tahun Y
    = jumlah pegawai aktif pada UNOR + Jabatan tersebut
      yang tanggal pensiunnya berada pada tahun Y
```

Asumsi saat ini:

```text
1 pegawai pensiun = kebutuhan pengganti 1 pegawai
```

Contoh:

Pegawai pada `Pranata Komputer Ahli Pertama`:

```text
2026 → 1 orang pensiun
2027 → 0
2028 → 2 orang pensiun
2029 → 1 orang pensiun
2030 → 0
```

Maka:

| Jabatan | 2026 | 2027 | 2028 | 2029 | 2030 |
|---|---:|---:|---:|---:|---:|
| Pranata Komputer Ahli Pertama | 1 | 0 | 2 | 1 | 0 |

Angka `2028 = 2` berarti terdapat dua pegawai yang pensiun pada tahun 2028 sehingga menghasilkan kebutuhan pengganti dua pegawai pada tahun tersebut.

Angka tersebut **BUKAN jumlah pegawai yang masih tersedia pada 2028**.

Proyeksi tidak bersifat kumulatif kecuali ada keputusan bisnis baru yang secara eksplisit mengubah aturan ini.

---

# BUP dan Tanggal Pensiun

Pertahankan mekanisme BUP existing yang sudah teruji selama tidak bertentangan dengan regulasi/data yang digunakan aplikasi.

CLAUDE.md tidak boleh mengubah aturan BUP existing secara spekulatif.

Secara umum:

```text
Tanggal Pensiun = tanggal lahir + BUP sesuai aturan jabatan/pegawai
```

`BupCalculator` harus menggunakan atribut Master Jabatan dan data pegawai yang relevan.

Sub-jabatan Guru/Dokter tidak boleh secara otomatis mengubah BUP kecuali memang ada aturan bisnis/regulasi yang sudah dimodelkan dalam aplikasi.

Proyeksi menggunakan tahun dari tanggal pensiun yang dihasilkan mekanisme BUP tersebut.

---

# Data Model Konseptual

Gunakan implementasi existing sebagai titik awal. Jangan membuat ulang tabel yang sudah memenuhi kebutuhan.

Entitas inti secara konseptual:

```text
UNOR
├── parent UNOR
└── memiliki banyak Jabatan pada SOTK

MASTER JABATAN
├── jenis
├── jenjang (nullable)
└── sub_jabatan (nullable)

SOTK / PENEMPATAN JABATAN PADA UNOR
├── UNOR
└── Master Jabatan

PEGAWAI
└── data identitas ASN

PENEMPATAN PEGAWAI
├── Pegawai
├── UNOR / konteks SOTK
└── Jabatan

KEBUTUHAN PEGAWAI
├── UNOR / konteks SOTK
├── Jabatan
├── periode/tahun jika digunakan
└── jumlah

MASTER TUGAS TAMBAHAN

TUGAS TAMBAHAN PEGAWAI
├── Pegawai
├── Tugas Tambahan
└── UNOR
```

Nama fisik tabel/kolom tidak wajib sama dengan nama konseptual di atas.

Sebelum membuat migration:

1. inspeksi schema existing;
2. inspeksi model dan relasi existing;
3. identifikasi constraint existing;
4. gunakan struktur existing jika masih cocok;
5. hanya buat migration baru untuk perubahan yang benar-benar diperlukan.

---

# Relasi Konseptual

```text
UNOR 1 --- * UNOR
UNOR * --- * MASTER JABATAN       melalui data SOTK
MASTER JABATAN 1 --- * PENEMPATAN PEGAWAI
PEGAWAI 1 --- * PENEMPATAN PEGAWAI
UNOR 1 --- * PENEMPATAN PEGAWAI

UNOR / SOTK 1 --- * KEBUTUHAN
MASTER JABATAN 1 --- * KEBUTUHAN

PEGAWAI 1 --- * TUGAS TAMBAHAN PEGAWAI
MASTER TUGAS TAMBAHAN 1 --- * TUGAS TAMBAHAN PEGAWAI
```

Jika sistem existing menetapkan hanya satu penempatan aktif per pegawai, enforce aturan tersebut tanpa menghapus riwayat penempatan lama.

---

# Validasi dan Integritas Data

Wajib dilakukan server-side.

## Pegawai

- NIP unik.
- NIP standar 18 digit jika aturan existing menggunakan NIP BKN.
- Auto-fill tanggal lahir dari NIP dapat dipertahankan.
- Pegawai harus mengacu pada Master Jabatan yang valid.
- Penempatan harus mengacu pada UNOR/SOTK yang valid.

## SOTK

- Parent UNOR harus valid.
- Cegah circular reference pada UNOR.
- Jabatan yang ditempatkan harus berasal dari Master Jabatan.
- Hindari duplikasi kombinasi UNOR + Jabatan yang merepresentasikan entri SOTK yang sama.
- Jangan membatasi jumlah pegawai berdasarkan jumlah node SOTK.

## Master Jabatan

- `jenjang` nullable.
- `sub_jabatan` nullable.
- Pelaksana dapat tidak memiliki jenjang.
- Jangan memaksa semua Jabatan Fungsional mempunyai sub-jabatan.
- Sub-jabatan tidak boleh digunakan sebagai parent pembentuk hierarki SOTK.
- Kombinasi atribut yang menentukan satu jabatan harus dijaga dari duplikasi sesuai kebutuhan implementasi.

## Kebutuhan

- `jumlah_kebutuhan >= 0`.
- Satu kombinasi konteks SOTK/UNOR + Jabatan + periode tidak boleh mempunyai baris kebutuhan ganda jika secara bisnis merepresentasikan data yang sama.
- Kebutuhan tidak menghasilkan node SOTK baru.

## Penempatan

- Beberapa pegawai boleh ditempatkan pada UNOR + Jabatan yang sama.
- Hapus/ubah seluruh unique constraint lama yang mengasumsikan satu jabatan/posisi hanya dapat ditempati satu pegawai.
- Penempatan utama dan tugas tambahan adalah konsep berbeda.

## Tugas Tambahan

- Tidak mengganti Jabatan ASN.
- Tidak mengganti penempatan utama.
- Harus mengacu pada Master Tugas Tambahan.
- Dapat memiliki konteks UNOR.

---

# Screens / Menus

## Dashboard

Minimal menampilkan:

- total PNS;
- total PPPK;
- komposisi PNS vs PPPK;
- statistik kebutuhan/Bezetting jika sudah tersedia pada implementasi existing.

## Pegawai

- daftar pegawai;
- CRUD identitas;
- Jabatan;
- penempatan UNOR;
- informasi tugas tambahan;
- data relevan untuk perhitungan BUP/pensiun.

## Master UNOR

- daftar UNOR;
- CRUD;
- parent UNOR;
- kode/nomenklatur existing jika tersedia.

## Master Jabatan

- daftar Jabatan;
- CRUD;
- jenis Jabatan;
- jenjang;
- sub-jabatan jika relevan.

UI harus dapat membentuk label jabatan yang mudah dipahami, misalnya:

```text
Pranata Komputer Ahli Pertama
Guru Matematika Ahli Muda
Dokter Spesialis Anak Ahli Madya
Penelaah Teknis Kebijakan
```

## Master Tugas Tambahan

- daftar tugas tambahan;
- CRUD.

## SOTK

Menampilkan tree UNOR beserta Jabatan yang tersedia pada masing-masing UNOR.

SOTK **tidak menampilkan atau menentukan jumlah kebutuhan**.

Jabatan tidak boleh berulang hanya karena kebutuhan lebih dari satu.

## Jabatan / Kebutuhan

Pertahankan nomenklatur menu existing jika halaman kebutuhan saat ini berada pada modul Jabatan.

Halaman ini menampilkan minimal:

- UNOR;
- Jabatan;
- Kebutuhan;
- Bezetting;
- Selisih;
- Proyeksi kebutuhan akibat pensiun per tahun untuk 5 tahun.

Contoh:

| Jabatan | Kebutuhan | Bezetting | Selisih | 2026 | 2027 | 2028 | 2029 | 2030 |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Pranata Komputer Ahli Pertama | 3 | 2 | -1 | 1 | 0 | 1 | 0 | 0 |
| Penelaah Teknis Kebijakan | 4 | 3 | -1 | 0 | 1 | 0 | 0 | 1 |

Kolom tahun menunjukkan **kebutuhan akibat pensiun pada tahun tersebut**.

## Bezetting

Jika terdapat layar Bezetting tersendiri, perhitungannya harus menggunakan sumber data yang sama dengan halaman kebutuhan:

```text
UNOR + Jabatan + Pegawai aktif
```

Jangan membuat formula Bezetting berbeda antarhalaman.

## Export

Pertahankan export Excel untuk kebutuhan/Bezetting apabila sudah tersedia.

---

# Service Classes

Gunakan service existing bila masih sesuai. Refactor hanya bagian yang masih membawa asumsi arsitektur lama.

| Service | Tanggung Jawab |
|---|---|
| `BupCalculator` | Menentukan BUP dan tanggal pensiun berdasarkan data pegawai/Jabatan. |
| `ProjectionService` | Mengelompokkan pegawai yang pensiun berdasarkan UNOR + Jabatan + tahun dan menghasilkan proyeksi kebutuhan 5 tahun. |
| `FlattenedTreeService` atau service tree existing | Membentuk pohon UNOR/SOTK untuk UI tanpa bergantung pada level Jabatan ASN. |
| `NipParser` | Mengekstrak informasi yang valid dari NIP sesuai aturan existing. |

`KodeJabatanGenerator` hanya dipertahankan apabila memang masih digunakan oleh desain Master Jabatan existing. Jangan mempertahankan service yang sudah tidak mempunyai fungsi bisnis.

---

# ProjectionService

`ProjectionService` harus mengikuti prinsip:

```text
baseYear = tahun kalender yang digunakan aplikasi

for year in baseYear .. baseYear + 4:
    proyeksi[year] =
        COUNT(
            pegawai aktif
            pada UNOR + Jabatan
            dengan YEAR(tanggal_pensiun) == year
        )
```

Jangan menggunakan:

```text
existing - cumulative_retirement
```

sebagai nilai kolom proyeksi.

Jangan memasukkan kekurangan existing ke kolom proyeksi pensiun.

Contoh:

```text
Kebutuhan existing = 5
Bezetting existing = 3
Pensiun 2027 = 1
```

Maka:

```text
Selisih existing = -2
Proyeksi 2027 = 1
```

Bukan:

```text
Proyeksi 2027 = 3
```

dan bukan:

```text
Proyeksi 2027 = 2
```

---

# Aturan Tugas Tambahan

Tugas tambahan harus tetap terpisah dari Jabatan.

## Kepala Sekolah

```text
Pegawai:
Jabatan = Guru Matematika Ahli Madya
UNOR = SMP Negeri 1

Tugas Tambahan:
Kepala Sekolah
UNOR = SMP Negeri 1
```

Jangan mengubah Jabatan pegawai menjadi `Kepala Sekolah`.

## Kepala Puskesmas

```text
Pegawai:
Jabatan = Dokter Ahli Madya
UNOR = Puskesmas Talise

Tugas Tambahan:
Kepala Puskesmas
UNOR = Puskesmas Talise
```

Jangan mengubah Jabatan pegawai menjadi `Kepala Puskesmas`.

---

# Migration Strategy

Sebelum melakukan perubahan database:

1. Analisis seluruh migration existing.
2. Analisis schema database hasil migration saat ini.
3. Analisis model Eloquent dan relasinya.
4. Cari seluruh referensi terhadap konsep lama:
   - `posisi_organisasi`;
   - satu posisi = satu pegawai;
   - unique constraint penempatan;
   - kebutuhan berdasarkan jumlah node;
   - hierarki berdasarkan level Jabatan;
   - proyeksi berupa jumlah pegawai tersisa.
5. Identifikasi data existing yang perlu dipertahankan.
6. Gunakan migration baru.
7. Jangan edit migration lama yang sudah pernah dijalankan.
8. Lakukan backfill/mapping data jika diperlukan sebelum menghapus constraint/kolom lama.
9. Jangan drop data existing sampai data baru sudah tervalidasi.
10. Pastikan rollback aman sejauh memungkinkan.

Jika ditemukan konflik antara implementasi existing dengan aturan CLAUDE.md ini, **jangan membuat asumsi besar secara sepihak**. Jelaskan konflik dan pilih perubahan yang paling menjaga integritas data dan prinsip domain.

---

# Testing Requirements

Business logic wajib mempunyai automated test.

Minimal uji:

## Master Jabatan

- JF tanpa sub-jabatan.
- Guru dengan sub-jabatan/mapel.
- Dokter dengan sub-jabatan/spesialisasi.
- Pelaksana tanpa jenjang.
- label/display name yang benar.

## SOTK

- membuat UNOR;
- membuat child UNOR;
- mencegah circular reference;
- menambahkan Jabatan ke UNOR;
- mencegah duplikasi entri Jabatan yang sama pada UNOR;
- memastikan kebutuhan > 1 tidak membuat node Jabatan berulang.

## Penempatan

- menempatkan satu pegawai;
- menempatkan beberapa pegawai pada UNOR + Jabatan yang sama;
- memastikan tidak ada constraint satu Jabatan = satu pegawai;
- mempertahankan satu penempatan aktif per pegawai jika itu aturan existing.

## Kebutuhan

Skenario:

```text
UNOR:
Bidang Pengadaan

Jabatan:
Pranata Komputer Ahli Pertama

Kebutuhan:
3
```

Pastikan hanya satu entri Jabatan pada SOTK dan satu data kebutuhan bernilai `3`.

Pelaksana:

```text
Jabatan:
Penelaah Teknis Kebijakan

Kebutuhan:
4
```

Pastikan tidak membuat empat node/baris Jabatan.

## Bezetting

```text
Kebutuhan = 3
Pegawai aktif = 2

Bezetting = 2
Selisih = -1
```

## Proyeksi

Jika:

```text
2026: 1 pegawai pensiun
2027: 0
2028: 2
2029: 1
2030: 0
```

hasil wajib:

```text
2026 = 1
2027 = 0
2028 = 2
2029 = 1
2030 = 0
```

Bukan jumlah pegawai yang tersisa.

## Guru

```text
UNOR:
SMP Negeri 1

Jabatan:
Guru

Jenjang:
Ahli Madya

Sub-jabatan:
Matematika

Tugas Tambahan:
Kepala Sekolah
```

Pastikan tugas tambahan tidak mengubah Jabatan maupun penempatan utama.

## Dokter

```text
UNOR:
Puskesmas Talise

Jabatan:
Dokter

Jenjang:
Ahli Madya

Sub-jabatan:
Spesialis Anak
```

Pastikan Bezetting dan proyeksi dapat dibedakan dari sub-jabatan Dokter lainnya.

---

# Security

- Role-based access.
- Hanya `bkd`/super admin yang dapat mengelola user dan master sesuai kebijakan existing.
- CSRF protection.
- Server-side validation.
- Gunakan authorization policy/gate jika relevan.
- Secret hanya di `.env`, jangan commit.
- Gunakan `@json()` untuk mengirim data Blade ke JavaScript.
- Jangan menggunakan `{!! !!}` untuk data database/input user tanpa sanitasi yang benar.
- HTTPS/TLS pada production.
- Backup database dan prosedur restore.
- Logging dan monitoring dasar.
- Audit trail untuk perubahan data utama jika sudah tersedia.

---

# Coding Principles

1. Jangan menulis kode sebelum memahami schema dan alur existing.
2. Jangan membuat ulang fitur yang sudah sesuai.
3. Jangan membuat abstraction baru tanpa kebutuhan nyata.
4. Business logic kompleks ditempatkan pada service/domain layer, bukan Blade.
5. Query agregasi Bezetting dan proyeksi dilakukan server-side.
6. Hindari N+1 query.
7. Gunakan eager loading/agregasi SQL sesuai kebutuhan.
8. Pertahankan foreign key dan integritas referensial.
9. Semua perubahan schema melalui migration baru.
10. Tambahkan test sebelum/bersamaan dengan perubahan business logic.
11. Gunakan transaksi database untuk operasi multi-step yang harus atomic.
12. Jangan menghapus data existing tanpa strategi migrasi yang eksplisit.

---

# Working Agreement

Untuk perubahan arsitektur atau fitur besar:

1. **Analisis implementasi existing terlebih dahulu.**
2. Identifikasi:
   - migration/schema;
   - model;
   - relasi;
   - service;
   - controller/API;
   - Livewire/component;
   - validation;
   - query;
   - test;
   - UI.
3. Bandingkan implementasi existing dengan aturan domain pada dokumen ini.
4. Buat rencana perubahan minimal.
5. Jelaskan migration/data migration yang diperlukan.
6. Identifikasi risiko terhadap data existing.
7. Baru implementasikan perubahan.
8. Jalankan automated test.
9. Lakukan pemeriksaan ulang alur utama.

Untuk redesign besar yang belum disetujui, urutan kerja:

1. Rencana + arsitektur.
2. Skema database + relasi + outline migration.
3. Daftar layar/perubahan UI.
4. Review dampak data existing.
5. Implementasi setelah desain disetujui.

---

# Final Architecture Summary

Sumber kebenaran domain SIPEKA:

```text
MASTER
├── UNOR
├── JABATAN
└── TUGAS TAMBAHAN


SOTK
└── UNOR
    ├── child UNOR
    ├── JABATAN
    └── JABATAN


PEGAWAI
├── JABATAN
├── PENEMPATAN UNOR
└── TUGAS TAMBAHAN (opsional)


KEBUTUHAN
├── UNOR / konteks SOTK
├── JABATAN
└── JUMLAH


BEZETTING
└── COUNT PEGAWAI AKTIF
    berdasarkan UNOR + JABATAN


SELISIH
└── BEZETTING - KEBUTUHAN


PROYEKSI 5 TAHUN
└── COUNT PEGAWAI PENSIUN
    berdasarkan UNOR + JABATAN + TAHUN
```

Prinsip utama:

> **SOTK menjelaskan struktur dan jabatan yang tersedia. Kebutuhan menjelaskan jumlah orang yang dibutuhkan. Bezetting menjelaskan jumlah pegawai yang tersedia. Proyeksi 5 tahun menjelaskan kebutuhan pengganti yang muncul akibat pensiun pada masing-masing tahun. Tugas tambahan menjelaskan peran tambahan tanpa mengganti Jabatan ASN maupun penempatan utama pegawai.**
