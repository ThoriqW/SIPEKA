# Organisasi Dummy SIPEKA

Dokumen ini digunakan sebagai **data dummy organisasi** untuk
pengembangan dan pengujian aplikasi **SIPEKA**.

## Aturan

-   Struktur organisasi mengikuti pola umum Pemerintah Daerah.
-   Setiap jabatan hanya berada pada satu UNOR.
-   Pegawai hanya boleh ditempatkan pada jabatan yang ada pada struktur
    ini.
-   Jabatan fungsional dapat berada pada Bidang, Sekretariat, maupun
    Kelompok Jabatan Fungsional.
-   Struktur ini hanya sebagai contoh dan dapat dikembangkan.

------------------------------------------------------------------------

# Pemerintah Kota Palu

``` text
Pemerintah Kota Palu
├── BKPSDMD Kota Palu
├── Dinas Kesehatan
└── Dinas Pendidikan
```

------------------------------------------------------------------------

# 1. BKPSDMD Kota Palu

``` text
BKPSDMD Kota Palu
│
├── Kepala Badan
│
├── Sekretariat
│   │
│   ├── Sekretaris
│   │
│   ├── Sub Bagian Umum dan Kepegawaian
│   │   ├── Kepala Sub Bagian Umum dan Kepegawaian
│   │   ├── Pengelola Kepegawaian
│   │   ├── Pengadministrasi Perkantoran
│   │   ├── Operator Layanan Operasional
│   │   └── Pengemudi
│   │
│   ├── Sub Bagian Keuangan
│   │   ├── Kepala Sub Bagian Keuangan
│   │   ├── Pengelola Keuangan
│   │   ├── Verifikator Keuangan
│   │   └── Bendahara
│   │
│   └── Sub Bagian Perencanaan
│       ├── Kepala Sub Bagian Perencanaan
│       ├── Analis Perencanaan
│       ├── Pengolah Data
│       └── Pranata Komputer Ahli Pertama
│
├── Bidang Pengadaan, Pemberhentian dan Informasi
│   ├── Kepala Bidang
│   ├── Analis SDM Aparatur Ahli Pertama (2 formasi)
│   ├── Pranata Komputer Ahli Pertama (3 formasi)
│   └── Pengelola Kepegawaian
│
├── Bidang Mutasi dan Promosi
│   ├── Kepala Bidang
│   ├── Analis SDM Aparatur Ahli Pertama (2 formasi)
│   ├── Pengelola Kepegawaian
│   └── Pengolah Data
│
├── Bidang Pengembangan Kompetensi
│   ├── Kepala Bidang
│   ├── Widyaiswara Ahli Muda
│   ├── Analis SDM Aparatur Ahli Pertama
│   └── Pengadministrasi Perkantoran
│
└── Kelompok Jabatan Fungsional
    ├── Arsiparis Ahli Pertama
    ├── Auditor Ahli Pertama
    ├── Pranata Komputer Ahli Muda
    └── Analis SDM Aparatur Ahli Muda
```

------------------------------------------------------------------------

# 2. Dinas Kesehatan

``` text
Dinas Kesehatan
│
├── Kepala Dinas
│
├── Sekretariat
│   ├── Sekretaris
│   ├── Sub Bagian Umum dan Kepegawaian
│   ├── Sub Bagian Keuangan
│   └── Sub Bagian Perencanaan
│
├── Bidang Pelayanan Kesehatan
│   ├── Kepala Bidang
│   ├── Dokter Ahli Pertama (2 formasi)
│   ├── Perawat Ahli Pertama (3 formasi)
│   └── Administrator Kesehatan Ahli Pertama
│
├── Bidang Pencegahan dan Pengendalian Penyakit
│   ├── Kepala Bidang
│   ├── Epidemiolog Kesehatan Ahli Pertama
│   ├── Sanitarian Ahli Pertama
│   └── Entomolog Kesehatan Ahli Pertama
│
├── Bidang Kesehatan Masyarakat
│   ├── Kepala Bidang
│   ├── Nutrisionis Ahli Pertama
│   ├── Penyuluh Kesehatan Masyarakat
│   └── Administrator Kesehatan
│
└── Kelompok Jabatan Fungsional
    ├── Apoteker Ahli Pertama
    ├── Dokter Gigi Ahli Pertama
    ├── Pranata Laboratorium Kesehatan
    └── Analis Kesehatan
```

------------------------------------------------------------------------

# 3. Dinas Pendidikan

``` text
Dinas Pendidikan
│
├── Kepala Dinas
│
├── Sekretariat
│   ├── Sekretaris
│   ├── Sub Bagian Umum dan Kepegawaian
│   ├── Sub Bagian Keuangan
│   └── Sub Bagian Perencanaan
│
├── Bidang Pendidikan Anak Usia Dini
│   ├── Kepala Bidang
│   ├── Analis Kebijakan Ahli Pertama
│   └── Pengelola Pendidikan
│
├── Bidang Sekolah Dasar
│   ├── Kepala Bidang
│   ├── Pengawas Sekolah Ahli Muda
│   ├── Analis Pendidikan
│   └── Pengelola Pendidikan
│
├── Bidang Sekolah Menengah Pertama
│   ├── Kepala Bidang
│   ├── Pengawas Sekolah Ahli Muda
│   ├── Analis Pendidikan
│   └── Pengelola Pendidikan
│
├── Bidang Guru dan Tenaga Kependidikan
│   ├── Kepala Bidang
│   ├── Analis SDM Aparatur Ahli Pertama
│   ├── Pengelola Kepegawaian
│   └── Pengolah Data
│
└── Kelompok Jabatan Fungsional
    ├── Widyaprada Ahli Pertama
    ├── Arsiparis Ahli Pertama
    ├── Pranata Komputer Ahli Pertama
    └── Pustakawan Ahli Pertama
```

------------------------------------------------------------------------

# Catatan untuk Claude Code

1.  Gunakan struktur organisasi ini sebagai acuan.
2.  Jangan membuat UNOR baru di luar struktur ini kecuali diminta.
3.  Seluruh pegawai harus ditempatkan pada jabatan yang terdapat pada
    struktur ini.
4.  Seluruh jabatan harus mengacu pada master jabatan SIPEKA.
5.  Jabatan yang memiliki lebih dari satu formasi dapat diisi oleh lebih
    dari satu pegawai.
6.  Sebagian jabatan sengaja dapat dibuat kosong atau kelebihan pegawai
    untuk menguji fitur Bezetting, Kebutuhan Jabatan, Dashboard, dan
    Proyeksi Pensiun.
7.  Hubungan organisasi harus mengikuti hierarki parent-child
    sebagaimana ditampilkan pada dokumen ini.
