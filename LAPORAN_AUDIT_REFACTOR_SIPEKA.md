# LAPORAN AUDIT & RENCANA REFACTOR SIPEKA

**Tanggal:** 2026-07-28
**Tujuan:** Gap analysis antara implementasi existing dengan arsitektur final CLAUDE.md
**Status:** MENUNGGU PERSETUJUAN — Tidak ada perubahan kode/DB yang dilakukan

---

## A. Executive Summary

Aplikasi SIPEKA existing dibangun dengan arsitektur di mana **Jabatan menjadi pembentuk utama hierarki SOTK** melalui self-referencing `induk_jabatan_id`. OPD/UNOR hanya berperan sebagai atribut lokasi, bukan pembentuk pohon organisasi. Kolom `kebutuhan` disimpan langsung di tabel `jabatan`.

**CLAUDE.md** menetapkan arsitektur berbeda: **UNOR adalah pembentuk utama pohon organisasi**, Jabatan ditempatkan pada UNOR melalui mekanisme SOTK (pivot/junction), dan Kebutuhan adalah entitas terpisah dengan dimensi UNOR + Jabatan + Jumlah.

**Temuan utama gap:**
1. ❌ Hierarki SOTK dibangun dari `jabatan.induk_jabatan_id`, bukan dari `unor.parent_id`
2. ❌ OPD/UNOR tidak memiliki hierarki (tabel flat, tidak ada `parent_id`)
3. ❌ Kebutuhan disimpan di kolom `jabatan.kebutuhan`, bukan sebagai entitas terpisah
4. ❌ Tidak ada tabel SOTK (junction UNOR-Jabatan)
5. ❌ Tidak ada tabel Penempatan Pegawai
6. ❌ Tidak ada Master Tugas Tambahan
7. ❌ Constraint "1 jabatan struktural = 1 pegawai" masih ada
8. ⚠️ Proyeksi sudah benar (menghitung pensiun, bukan pegawai tersisa)
9. ⚠️ Bezetting formula sudah benar (`COUNT pegawai`) tapi dari jabatan, bukan UNOR+Jabatan
10. ⚠️ BupCalculator sudah sesuai
11. ❌ Tidak ada fitur Tugas Tambahan sama sekali

**Rekomendasi:** `migrate:fresh --seed` (Opsi B) karena data development seluruhnya disposable dan perubahan schema sangat fundamental. Data User dan OPD dipertahankan melalui seeder.

---

## B. Arsitektur Existing

### B.1 Database Schema (13 tabel)

| Tabel | Kolom Kunci | Peran |
|---|---|---|
| `opd` | id, nama_opd, kode_opd (UNIQUE) | Flat — tidak ada parent_id |
| `jabatan` | id, nama_jabatan, kode_jabatan (UQ), jenis_jabatan, kelas_jabatan, jenjang (nullable), **kebutuhan** (integer), **opd_id** (FK), **induk_jabatan_id** (FK self-ref), jptp_opd_unique (generated, MySQL only) | **Pusat arsitektur existing**: hierarki + kebutuhan + OPD |
| `pegawai` | id, nama, nip (UQ 18), jenis_kepegawaian, tanggal_lahir, golongan_pangkat, pendidikan, kualifikasi_pendidikan, jenjang (nullable), **opd_id** (FK→opd CASCADE), **jabatan_id** (FK→jabatan SET NULL) | Pegawai dengan penempatan langsung |
| `master_jabatan` | id, nama_jabatan, jenis_jabatan(20), parent_id (FK self-ref SET NULL) | Referensi master, 2 level (parent-child) |
| `users` | id, name, nip (UQ nullable), email (UQ nullable), password, role (bkd/admin_opd/user), is_active | Auth; tidak ada opd_id lagi (dihapus di migrasi #11) |
| `audit_trail` | id, user_id, action, table_name, row_id, old_values (JSON), new_values (JSON) | Audit log |
| Lainnya | cache, cache_locks, jobs, job_batches, failed_jobs, sessions, password_reset_tokens | Laravel default |

### B.2 Foreign Key & Constraint

```
opd ← jabatan.opd_id (CASCADE)
opd ← pegawai.opd_id (CASCADE)
jabatan ← jabatan.induk_jabatan_id (SET NULL) [self-ref]
jabatan ← pegawai.jabatan_id (SET NULL)
master_jabatan ← master_jabatan.parent_id (SET NULL) [self-ref]
users ← audit_trail.user_id (SET NULL)

UNIQUE: opd.kode_opd, jabatan.kode_jabatan, pegawai.nip, users.email, users.nip
UNIQUE (MySQL only): jabatan.jptp_opd_unique (1 JPTP per OPD)
```

### B.3 Models & Relasi

```
User → belongsTo Pegawai (via nip)
Opd → hasMany Jabatan, hasMany Pegawai
Jabatan → belongsTo Opd, belongsTo Jabatan(induk), hasMany Jabatan(anak), hasMany Pegawai
MasterJabatan → belongsTo MasterJabatan(parent), hasMany MasterJabatan(children)
Pegawai → belongsTo Opd, belongsTo Jabatan
AuditTrail → belongsTo User
```

### B.4 Services

| Service | Method Utama | Status vs CLAUDE.md |
|---|---|---|
| `FlattenedTreeService` | `buildFlatTree()` — tree dari hierarki Jabatan | ❌ Membangun SOTK dari Jabatan, bukan UNOR |
| `ProjectionService` | `hitungProyeksiPensiunPerJabatan()` — pensiun per jabatan_id | ⚠️ Benar (pensiun only), tapi grouping per jabatan_id saja, bukan UNOR+Jabatan |
| `BupCalculator` | `hitungBup()`, `hitungTanggalPensiun()` | ✅ Sesuai; deteksi Guru via string matching |
| `KodeJabatanGenerator` | `generate()` — kode format KODEOPD-JNS-001 | ⚠️ Masih digunakan, perlu evaluasi di arsitektur baru |
| `NipParser` | `extractTanggalLahir()` | ✅ Sesuai |

### B.5 Controllers

| Controller | Fungsi | Gap |
|---|---|---|
| `DashboardController` | Statistik: PNS, PPPK, per jenis/jenjang | ⚠️ Hardcoded kategori Nakes, query berat |
| `OpdController` | CRUD OPD (flat) | ❌ Tidak support hierarki UNOR |
| `JabatanController` | CRUD Jabatan + hierarki via induk_jabatan_id | ❌ Arsitektur lama |
| `MasterJabatanController` | CRUD Master Jabatan (parent-child 2 level) | ⚠️ Perlu tambah field jenjang, sub_jabatan |
| `PegawaiController` | CRUD Pegawai + constraint struktural=1 pegawai | ❌ Constraint 1 struktural = 1 pegawai |
| `UserController` | CRUD User | ✅ Sesuai |
| `BezettingController` | Menampilkan halaman "Kebutuhan" (dengan proyeksi) | ❌ Nama terbalik |
| `KebutuhanController` | Menampilkan halaman "Bezetting" (tanpa proyeksi) | ❌ Nama terbalik |

### B.6 Views & Navigasi

Sidebar menu existing:
1. Dashboard
2. OPD
3. User (BKD only)
4. Master Jabatan (BKD only)
5. Jabatan
6. Pegawai
7. Bezetting (sebenarnya halaman bezetting tanpa proyeksi)
8. Kebutuhan (sebenarnya halaman kebutuhan dengan proyeksi)

**Tidak ada:** SOTK view, UNOR tree view, Tugas Tambahan view, Penempatan view.
**Tidak ada Livewire components.** Semua interaktivitas via Alpine.js CDN + Blade.

---

## C. Gap Analysis

### C.1 GAP KRITIS — Hierarki SOTK dari Jabatan, bukan UNOR

| Aspek | Detail |
|---|---|
| **Kondisi Existing** | `FlattenedTreeService` membangun tree dari `Jabatan` via `induk_jabatan_id`. Tree level: 0 (virtual root) → 1 (JPTP/Kepala OPD) → 2 (Sekretariat/Bidang) → 3 (Sub Bagian) → 4 (Pelaksana/Fungsional). Max level hardcoded = 4. |
| **Kondisi Target** | UNOR membentuk pohon organisasi. Jabatan ditempatkan pada UNOR via tabel SOTK (junction). Tree level dari UNOR hierarchy, bukan jabatan. |
| **Tabel terdampak** | `jabatan` (drop `induk_jabatan_id`), `opd` → `unor` (tambah `parent_id`), tabel baru `sotk` (junction UNOR-Jabatan) |
| **File terdampak** | `FlattenedTreeService`, `JabatanController`, `Jabatan` model, views: jabatan/index, bezetting/index, kebutuhan/index, opd/show, pegawai/index, dashboard |
| **Perubahan** | Bangun ulang `FlattenedTreeService` berbasis UNOR hierarchy + SOTK junction. Drop constraint level max 4. |
| **Dependency** | Phase 1 (Database + Master UNOR) |
| **Risiko** | Tinggi — perubahan fundamental pada cara tree dibangun |

### C.2 GAP KRITIS — OPD Flat, Bukan UNOR Hierarkis

| Aspek | Detail |
|---|---|
| **Kondisi Existing** | Tabel `opd` hanya memiliki `nama_opd` dan `kode_opd`. Tidak ada `parent_id`. OPD tidak bisa membentuk hierarki. |
| **Kondisi Target** | `unor` memiliki `parent_id` (self-referencing) untuk membentuk pohon organisasi. |
| **Tabel terdampak** | `opd` → rename ke `unor`, tambah `parent_id`, `singkatan`, mungkin `jenis_unor` |
| **File terdampak** | `Opd` model, `OpdController`, `OpdPolicy`, views opd/*, sidebar navigation, semua referensi "OPD" di UI |
| **Perubahan** | Migration: rename `opd` → `unor`, tambah `parent_id` (FK self-ref), `singkatan`. Update semua kode dan UI. |
| **Dependency** | Phase 1 |
| **Risiko** | Sedang — rename tabel + rename label UI di banyak tempat |

### C.3 GAP KRITIS — Kebutuhan di Kolom Jabatan, Bukan Entitas Terpisah

| Aspek | Detail |
|---|---|
| **Kondisi Existing** | `jabatan.kebutuhan` (integer, nullable). Struktural auto=1. Fungsional/Pelaksana diisi manual. |
| **Kondisi Target** | Tabel `kebutuhan` terpisah dengan dimensi: `unor_id`, `jabatan_id`, `tahun` (jika relevan), `jumlah`. |
| **Tabel terdampak** | `jabatan` (drop `kebutuhan`), tabel baru `kebutuhan_pegawai` |
| **File terdampak** | `Jabatan` model, `JabatanController` (store/update), `FlattenedTreeService`, `DashboardController`, `KebutuhanController`, `BezettingController`, views, exports |
| **Perubahan** | Buat tabel `kebutuhan_pegawai`, pindahkan logic perhitungan dari jabatan, hapus kolom `jabatan.kebutuhan` |
| **Dependency** | Phase 1 (UNOR) + Phase 2 (SOTK) |
| **Risiko** | Sedang — perubahan query di banyak tempat |

### C.4 GAP — Tidak Ada Tabel SOTK

| Aspek | Detail |
|---|---|
| **Kondisi Existing** | SOTK implisit = Jabatan dengan `induk_jabatan_id` + `opd_id`. Tidak ada junction table. |
| **Kondisi Target** | Tabel `sotk` sebagai junction: `unor_id` + `jabatan_id`. Satu UNOR dapat memiliki banyak Jabatan. Satu Jabatan dapat muncul di banyak UNOR. |
| **Tabel terdampak** | Tabel baru `sotk`, `jabatan` (drop `induk_jabatan_id`, drop `opd_id`) |
| **File terdampak** | `Jabatan` model, `JabatanController`, `FlattenedTreeService`, views |
| **Perubahan** | Buat tabel junction `sotk`, migrasi data Jabatan existing ke SOTK |
| **Dependency** | Phase 1 |
| **Risiko** | Tinggi — perubahan fundamental, seluruh query tree berubah |

### C.5 GAP — Tidak Ada Tabel Penempatan Pegawai

| Aspek | Detail |
|---|---|
| **Kondisi Existing** | `pegawai.opd_id` + `pegawai.jabatan_id` — penempatan langsung di tabel pegawai. Tidak ada riwayat. |
| **Kondisi Target** | Tabel `penempatan_pegawai`: `pegawai_id`, `unor_id`, `jabatan_id`, `tanggal_mulai`, `tanggal_selesai`, `is_active`. Satu penempatan aktif per pegawai. Beberapa pegawai bisa satu UNOR+Jabatan. |
| **Tabel terdampak** | Tabel baru `penempatan_pegawai`, `pegawai` (drop `opd_id`, drop `jabatan_id`) |
| **File terdampak** | `Pegawai` model, `PegawaiController`, semua query Bezetting/Proyeksi, Dashboard |
| **Perubahan** | Buat tabel `penempatan_pegawai`, pindahkan data penempatan, update query Bezetting |
| **Dependency** | Phase 1 (UNOR) + Phase 2 (SOTK) |
| **Risiko** | Sedang — data development disposable |

### C.6 GAP — Constraint 1 Struktural = 1 Pegawai

| Aspek | Detail |
|---|---|
| **Kondisi Existing** | `PegawaiController@store` baris 63-64: jika jabatan struktural dan `pegawai_count >= 1`, tolak. `@update` baris 108-111: sama, dengan pengecualian jika jabatan tidak berubah. |
| **Kondisi Target** | Tidak ada constraint ini. Beberapa pegawai boleh pada UNOR+Jabatan yang sama, termasuk struktural. (Namun secara bisnis, jabatan struktural biasanya hanya 1 — ini keputusan bisnis.) |
| **Tabel terdampuk** | `PegawaiController` |
| **Perubahan** | Hapus validasi. Jika bisnis tetap butuh constraint JPTP=1, implementasi di level berbeda (per UNOR, bukan per jabatan). |
| **Dependency** | Phase 3 |
| **Risiko** | Rendah |

### C.7 GAP — Tidak Ada Master Tugas Tambahan & Tugas Tambahan Pegawai

| Aspek | Detail |
|---|---|
| **Kondisi Existing** | Tidak ada tabel/model/UI untuk tugas tambahan. |
| **Kondisi Target** | `master_tugas_tambahan` (id, nama_tugas) + `tugas_tambahan_pegawai` (pegawai_id, tugas_tambahan_id, unor_id). |
| **Tabel terdampak** | Tabel baru: `master_tugas_tambahan`, `tugas_tambahan_pegawai` |
| **File terdampak** | Model baru, controller baru, views baru, sidebar navigation |
| **Perubahan** | Buat tabel + CRUD + integrasi ke tampilan Pegawai |
| **Dependency** | Phase 7 |
| **Risiko** | Rendah — fitur baru, tidak mengubah data existing |

### C.8 GAP RINGAN — Proyeksi Per Jabatan, Bukan Per UNOR+Jabatan

| Aspek | Detail |
|---|---|
| **Kondisi Existing** | `ProjectionService::hitungProyeksiPensiunPerJabatan()` mengelompokkan per `jabatan_id` saja. Formula: COUNT pegawai pensiun per tahun. Benar secara formula. |
| **Kondisi Target** | Proyeksi per `UNOR + Jabatan`. Karena satu Jabatan bisa muncul di beberapa UNOR, grouping harus UNOR+Jabatan. |
| **Perubahan** | Update `ProjectionService` untuk grouping via `penempatan_pegawai` (UNOR+Jabatan). |
| **Dependency** | Phase 3 (Penempatan) |
| **Risiko** | Rendah |

### C.9 GAP RINGAN — Deteksi Guru Berbasis String

| Aspek | Detail |
|---|---|
| **Kondisi Existing** | `BupCalculator::isGuru()` mendeteksi via `str_contains(nama_jabatan, 'guru')` (case-insensitive). |
| **Kondisi Target** | Deteksi via atribut Master Jabatan (jenis_jabatan + sub_jabatan atau field khusus). |
| **Perubahan** | Gunakan data terstruktur dari Master Jabatan, bukan string matching. |
| **Dependency** | Phase 1 (Master Jabatan enhancement) |
| **Risiko** | Rendah |

### C.10 GAP — Tidak Ada Sub-Jabatan di Master Jabatan

| Aspek | Detail |
|---|---|
| **Kondisi Existing** | `master_jabatan` menggunakan `parent_id` untuk hierarki 2 level (parent-sub). Tidak ada field `jenjang` atau `sub_jabatan` terstruktur. |
| **Kondisi Target** | `master_jabatan` mendukung: nama_jabatan, jenis_jabatan, jenjang (nullable), sub_jabatan (nullable). Sub-jabatan adalah **atribut**, bukan parent/child. |
| **Perubahan** | Tambah kolom `jenjang` dan `sub_jabatan` ke `master_jabatan`. Evaluasi apakah `parent_id` masih diperlukan. |
| **Dependency** | Phase 1 |
| **Risiko** | Rendah — data development disposable |

### C.11 GAP — Penamaan Controller Terbalik

| Aspek | Detail |
|---|---|
| **Kondisi Existing** | `BezettingController` → route `/admin/kebutuhan` (dengan proyeksi). `KebutuhanController` → route `/admin/bezetting` (tanpa proyeksi). |
| **Kondisi Target** | Nama controller sesuai fungsi: `KebutuhanController` → halaman Kebutuhan, `BezettingController` → halaman Bezetting. |
| **Perubahan** | Rename/refactor controller dan route mapping. |
| **Dependency** | Phase 8 |
| **Risiko** | Rendah |

### C.12 GAP — Tidak Ada Livewire Components

| Aspek | Detail |
|---|---|
| **Kondisi Existing** | Semua interaktivitas menggunakan Alpine.js inline di Blade. Direktori `app/Livewire/` kosong. |
| **Kondisi Target** | Tech stack menyebutkan Livewire/Alpine.js. Livewire dapat digunakan untuk komponen interaktif (tree SOTK, form dinamis). |
| **Perubahan** | Buat Livewire components secara bertahap untuk menggantikan Alpine.js inline yang kompleks. |
| **Dependency** | Phase 8 |
| **Risiko** | Rendah — perbaikan, bukan perubahan fungsional |

### C.13 — Summary Klasifikasi

**SUDAH SESUAI (tidak diubah):**
- `BupCalculator` — aturan BUP benar
- `NipParser` — ekstraksi NIP benar
- `ProjectionService` — formula proyeksi benar (COUNT pensiun)
- `UserController` — manajemen user sesuai
- `AuditTrail` — mekanisme audit log sesuai
- Auth (login/logout/role middleware)

**PERLU MODIFIKASI:**
- Tabel `opd` → `unor` + tambah `parent_id`
- Tabel `jabatan` → drop `induk_jabatan_id`, `opd_id`, `kebutuhan`
- Tabel `pegawai` → drop `opd_id`, `jabatan_id`
- Tabel `master_jabatan` → tambah `jenjang`, `sub_jabatan`
- `FlattenedTreeService` → rewrite berbasis UNOR+SOTK
- `ProjectionService` → grouping UNOR+Jabatan
- `JabatanController` → sederhanakan, tanpa hierarki
- `PegawaiController` → hapus constraint 1 struktural=1 pegawai
- `DashboardController` → query via penempatan
- Semua views terkait tree/SOTK

**PERLU DITAMBAHKAN:**
- Tabel `sotk` (junction UNOR-Jabatan)
- Tabel `kebutuhan_pegawai`
- Tabel `penempatan_pegawai`
- Tabel `master_tugas_tambahan`
- Tabel `tugas_tambahan_pegawai`
- Model-model baru
- `SotkController` atau logic SOTK
- `TugasTambahanController`
- `PenempatanController` atau logic penempatan
- Views: SOTK tree, Tugas Tambahan, penempatan
- Livewire components
- Test untuk semua entitas baru

**OBSOLETE (kandidat cleanup):**
- `jabatan.induk_jabatan_id` — setelah SOTK jalan
- `jabatan.kebutuhan` — setelah tabel kebutuhan ada
- `jabatan.opd_id` — setelah SOTK jalan
- `pegawai.opd_id` — setelah penempatan_pegawai jalan
- `pegawai.jabatan_id` — setelah penempatan_pegawai jalan
- `KodeJabatanGenerator` — evaluasi setelah arsitektur baru (mungkin tetap perlu untuk kode jabatan di SOTK)
- `master_jabatan.parent_id` — evaluasi setelah field `sub_jabatan` diterapkan
- `layouts/navigation.blade.php` — legacy, tidak digunakan

---

## D. Database Impact

### D.1 Tabel yang Dipertahankan

| Tabel | Keterangan |
|---|---|
| `users` | Dipertahankan penuh. Tidak ada perubahan. |
| `audit_trail` | Dipertahankan penuh. |
| `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | Laravel default. |
| `password_reset_tokens` | Laravel default. |

### D.2 Tabel yang Dimodifikasi

| Tabel Existing | Perubahan |
|---|---|
| `opd` | **Rename ke `unor`**. Tambah: `parent_id` (bigint, nullable, FK→unor.id SET NULL), `singkatan` (varchar 10, nullable). Evaluasi: `kode_opd` tetap sebagai kode unik. |
| `jabatan` | **Drop**: `induk_jabatan_id`, `opd_id`, `kebutuhan`, `kelas_jabatan`, `jptp_opd_unique`. **Tambah**: evaluasi field dari Master Jabatan yang perlu di-cache. **Pertahankan**: `nama_jabatan`, `kode_jabatan`, `jenis_jabatan`, `jenjang`. |
| `pegawai` | **Drop**: `opd_id`, `jabatan_id`. Alternatif: keep dulu, tambah flag `is_active`, baru hapus setelah penempatan stabil. |
| `master_jabatan` | **Tambah**: `jenjang` (varchar, nullable), `sub_jabatan` (varchar, nullable). **Evaluasi**: apakah `parent_id` masih diperlukan setelah ada `sub_jabatan`. |

### D.3 Tabel Baru

| Tabel | Kolom | Tujuan |
|---|---|---|
| `sotk` | id, `unor_id` (FK→unor CASCADE), `jabatan_id` (FK→jabatan CASCADE), created_at, updated_at | Junction UNOR-Jabatan. Unique(`unor_id`, `jabatan_id`). |
| `kebutuhan_pegawai` | id, `unor_id` (FK→unor CASCADE), `jabatan_id` (FK→jabatan CASCADE), `tahun` (year, nullable), `jumlah` (integer, min:0), created_at, updated_at | Kebutuhan pegawai per UNOR+Jabatan. Unique(`unor_id`, `jabatan_id`, `tahun`). |
| `penempatan_pegawai` | id, `pegawai_id` (FK→pegawai CASCADE), `unor_id` (FK→unor), `jabatan_id` (FK→jabatan), `tanggal_mulai` (date), `tanggal_selesai` (date, nullable), `is_active` (boolean, default true), created_at, updated_at | Penempatan pegawai + riwayat. Index untuk query Bezetting. |
| `master_tugas_tambahan` | id, `nama_tugas` (varchar), created_at, updated_at | Referensi tugas tambahan. |
| `tugas_tambahan_pegawai` | id, `pegawai_id` (FK→pegawai CASCADE), `tugas_tambahan_id` (FK→master_tugas_tambahan), `unor_id` (FK→unor), `tanggal_mulai` (date), `tanggal_selesai` (date, nullable), `is_active` (boolean), created_at, updated_at | Tugas tambahan pegawai. Unique(`pegawai_id`, `tugas_tambahan_id`, `is_active`) conditional. |

### D.4 Kolom yang Akan Dihapus (setelah migrasi data)

| Tabel | Kolom |
|---|---|
| `jabatan` | `induk_jabatan_id`, `opd_id`, `kebutuhan`, `kelas_jabatan`, `jptp_opd_unique` |
| `pegawai` | `opd_id`, `jabatan_id` (dipindahkan ke `penempatan_pegawai`) |

### D.5 Foreign Key Baru

```
unor.parent_id → unor.id (SET NULL)
sotk.unor_id → unor.id (CASCADE)
sotk.jabatan_id → jabatan.id (CASCADE)
kebutuhan_pegawai.unor_id → unor.id (CASCADE)
kebutuhan_pegawai.jabatan_id → jabatan.id (CASCADE)
penempatan_pegawai.pegawai_id → pegawai.id (CASCADE)
penempatan_pegawai.unor_id → unor.id (RESTRICT)
penempatan_pegawai.jabatan_id → jabatan.id (RESTRICT)
tugas_tambahan_pegawai.pegawai_id → pegawai.id (CASCADE)
tugas_tambahan_pegawai.tugas_tambahan_id → master_tugas_tambahan.id (RESTRICT)
tugas_tambahan_pegawai.unor_id → unor.id (RESTRICT)
```

### D.6 Unique Constraints Baru

| Tabel | Constraint |
|---|---|
| `sotk` | UNIQUE(`unor_id`, `jabatan_id`) |
| `kebutuhan_pegawai` | UNIQUE(`unor_id`, `jabatan_id`, `tahun`) |
| `penempatan_pegawai` | Conditional: hanya satu `is_active=true` per `pegawai_id` |
| `unor` | UNIQUE(`kode_unor`), constraint circular reference prevention |

### D.7 Index Baru

| Tabel | Index | Tujuan |
|---|---|---|
| `penempatan_pegawai` | (`unor_id`, `jabatan_id`, `is_active`) | Query Bezetting |
| `penempatan_pegawai` | (`pegawai_id`, `is_active`) | Cek penempatan aktif |
| `kebutuhan_pegawai` | (`unor_id`, `jabatan_id`) | Join dengan Bezetting |
| `unor` | (`parent_id`) | Tree traversal |
| `tugas_tambahan_pegawai` | (`pegawai_id`, `is_active`) | Filter aktif |

---

## E. Data Reset Recommendation

### Rekomendasi: **Opsi B — Fresh Database + Restore/Seed**

**Alasan:**
1. Perubahan schema sangat fundamental — rename tabel, drop kolom, tambah tabel baru, relasi berubah total.
2. Semua data development (Jabatan, Pegawai, Kebutuhan, SOTK) dinyatakan **disposable** di PROMPT_REFACTOR_SIPEKA.md.
3. Selective reset akan membutuhkan backfill script yang kompleks dengan nilai sangat rendah karena data development akan diganti total.
4. `migrate:fresh --seed` memberikan schema bersih, konsisten, dan mudah diverifikasi.

### Strategi Pengamanan Data User & OPD

1. **User:** Data user (Super Admin) sudah ada di `UserSeeder`. Karena hanya ada 1 user development, langsung di-seed ulang.
2. **OPD:** Data 5 OPD development sudah ada di `OpdSeeder`. Setelah rename ke UNOR, seeder disesuaikan.
3. Jika ada User/OPD production yang perlu dipertahankan:
   - Export via `mysqldump` atau Excel untuk backup
   - Import ulang setelah migrate:fresh
   - Atau buat seeder kustom dari data backup

### Urutan Eksekusi yang Direkomendasikan

```bash
# 1. Backup (jika diperlukan)
php artisan db:seed --class=UserSeeder  # catat user yang ada

# 2. Fresh migration dengan schema baru
php artisan migrate:fresh

# 3. Seed data master
php artisan db:seed --class=DatabaseSeeder

# 4. Verifikasi
php artisan test
```

### Keuntungan Opsi B
- Schema bersih tanpa artifact migrasi lama
- Tidak perlu backfill script
- Mudah direproduksi di environment lain
- Konsisten dari awal

### Risiko Opsi B
- User dan OPD harus di-seed ulang (mitigasi: backup terlebih dahulu)
- Foreign key cascade memastikan tidak ada orphan data

---

## F. Application Impact

### F.1 Model (6 existing → ~12 setelah refactor)

| Model | Aksi |
|---|---|
| `Opd` | **Rename ke `Unor`**, tambah relasi `parent()` dan `children()` |
| `Jabatan` | Drop relasi `induk()`, `anak()`, `opd()`. Drop field `kebutuhan`, `induk_jabatan_id`, `opd_id`. Tambah relasi ke `Sotk`. |
| `Pegawai` | Drop relasi `opd()`, `jabatan()`. Tambah relasi `penempatanAktif()` ke `PenempatanPegawai`. |
| `MasterJabatan` | Tambah field `jenjang`, `sub_jabatan`. Evaluasi relasi `parent()`/`children()`. |
| `User` | Tidak berubah. |
| `AuditTrail` | Tidak berubah. |
| **BARU** `Unor` | (rename dari Opd) + parent_id + singkatan |
| **BARU** `Sotk` | Junction UNOR-Jabatan |
| **BARU** `KebutuhanPegawai` | Kebutuhan per UNOR+Jabatan+tahun |
| **BARU** `PenempatanPegawai` | Penempatan + riwayat |
| **BARU** `MasterTugasTambahan` | Referensi tugas tambahan |
| **BARU** `TugasTambahanPegawai` | Assignment tugas tambahan |

### F.2 Controllers

| Controller | Aksi |
|---|---|
| `OpdController` | **Rename ke `UnorController`**, tambah logic parent-child |
| `JabatanController` | Sederhanakan — hapus validasi induk, cross-OPD, JPTP constraint. Fokus ke CRUD jabatan reference. |
| `PegawaiController` | Hapus constraint 1 struktural=1 pegawai. Tambah logic penempatan. |
| `BezettingController` | Rename/fix mapping ke route yang benar. Update query. |
| `KebutuhanController` | Rename/fix mapping ke route yang benar. Update query. |
| `DashboardController` | Update query via penempatan. |
| **BARU** `SotkController` | Kelola SOTK: assign jabatan ke UNOR |
| **BARU** `KebutuhanPegawaiController` | Kelola kebutuhan per UNOR+Jabatan |
| **BARU** `TugasTambahanController` | CRUD master tugas tambahan + assignment |
| **BARU** `PenempatanController` | Kelola penempatan pegawai |

### F.3 Services

| Service | Aksi |
|---|---|
| `FlattenedTreeService` | **Rewrite total** — tree dari UNOR hierarchy + SOTK junction. |
| `ProjectionService` | Update — grouping via `penempatan_pegawai` (UNOR+Jabatan). |
| `BupCalculator` | Update — deteksi Guru via atribut Master Jabatan, bukan string matching. |
| `KodeJabatanGenerator` | Evaluasi — mungkin tetap dipertahankan. |
| `NipParser` | Tidak berubah. |

### F.4 Views

| View | Aksi |
|---|---|
| `admin/opd/*` | Rename ke `admin/unor/*`, tambah parent selection |
| `admin/jabatan/*` | Sederhanakan form (tanpa induk, tanpa unit organisasi) |
| `admin/pegawai/*` | Tambah penempatan (UNOR+Jabatan), tugas tambahan |
| `admin/kebutuhan/index` | Tampilkan data dari `kebutuhan_pegawai` + Bezetting + Proyeksi |
| `admin/bezetting/index` | Tampilkan Bezetting dari `penempatan_pegawai` |
| **BARU** `admin/sotk/*` | Tree UNOR + jabatan tersedia |
| **BARU** `admin/tugas-tambahan/*` | CRUD master + assignment |
| `layouts/admin.blade.php` | Update sidebar: OPD→UNOR, tambah SOTK, Tugas Tambahan |

### F.5 Routes

- Rename `admin.opd.*` → `admin.unor.*`
- Tambah `admin.sotk.*`
- Tambah `admin.tugas-tambahan.*` 
- Tambah `admin.penempatan.*` atau integrasi ke pegawai
- Fix mapping kebutuhan/bezetting

### F.6 Seeders

| Seeder | Aksi |
|---|---|
| `OpdSeeder` | Rename ke `UnorSeeder`, tambah hierarki (parent_id) |
| `UserSeeder` | Sesuaikan — mungkin tambah user biasa |
| `MasterJabatanSeeder` | Tambah field `jenjang`, `sub_jabatan`. Evaluasi format data. |
| `JabatanSeeder` | Sesuaikan dengan schema baru (tanpa induk_jabatan_id, opd_id) |
| **BARU** `SotkSeeder` | Assign jabatan ke UNOR |
| **BARU** `KebutuhanSeeder` | Data kebutuhan per UNOR+Jabatan |
| **BARU** `PenempatanSeeder` | Penempatan pegawai |
| **BARU** `TugasTambahanSeeder` | Master tugas tambahan + assignment |

### F.7 Tests

| Test | Aksi |
|---|---|
| `FlattenedTreeServiceTest` | **Rewrite** — test berbasis UNOR+SOTK |
| `KodeJabatanGeneratorTest` | Update jika format berubah |
| `BezettingControllerTest` | Update query assertions |
| `KebutuhanControllerTest` | Update query assertions |
| **BARU** | Test untuk Unor (hierarki, circular ref prevention) |
| **BARU** | Test untuk Sotk (assign, unique constraint) |
| **BARU** | Test untuk Kebutuhan (CRUD, constraint) |
| **BARU** | Test untuk Penempatan (multi pegawai, riwayat) |
| **BARU** | Test untuk Tugas Tambahan (tidak mengubah jabatan) |
| **BARU** | Test untuk BupCalculator standalone |
| **BARU** | Test untuk Guru + sub-jabatan |
| **BARU** | Test untuk Dokter + sub-jabatan |

---

## G. Implementation Phases

### Phase 1: Database Schema + Master Data
**Tujuan:** Siapkan fondasi database arsitektur baru.

| Item | Detail |
|---|---|
| **Migration** | Buat migration baru untuk: rename `opd`→`unor` + tambah `parent_id`,`singkatan`; tambah `jenjang`,`sub_jabatan` ke `master_jabatan`; buat tabel `sotk`, `kebutuhan_pegawai`, `penempatan_pegawai`, `master_tugas_tambahan`, `tugas_tambahan_pegawai`; drop kolom obsolete dari `jabatan` dan `pegawai` |
| **Model** | Rename `Opd`→`Unor`, update relasi; update `Jabatan`, `Pegawai`, `MasterJabatan`; buat 5 model baru |
| **Seeder** | `UnorSeeder` (data OPD existing + hierarki), `MasterJabatanSeeder` (dengan jenjang & sub_jabatan), `SotkSeeder`, `KebutuhanSeeder`, `PenempatanSeeder`, `TugasTambahanSeeder` |
| **Test** | Unit test untuk model baru, constraint DB |
| **Dependency** | Tidak ada (fondasi) |
| **Risiko** | Sedang — banyak migration baru, FK constraint |
| **Acceptance** | `php artisan migrate:fresh --seed` sukses, semua FK valid |

### Phase 2: SOTK (UNOR Tree + Jabatan Placement)
**Tujuan:** UNOR membentuk hierarki, jabatan ditempatkan via SOTK.

| Item | Detail |
|---|---|
| **Backend** | `UnorController` (CRUD + parent management + circular ref prevention); `SotkController` (assign/unassign jabatan ke UNOR); `FlattenedTreeService` rewrite (UNOR hierarchy + SOTK) |
| **Frontend** | `admin/unor/*` views (dengan parent selection); `admin/sotk/index` (tree UNOR + jabatan, expandable); sidebar update |
| **Test** | SOTK: create UNOR, child UNOR, circular ref prevention, assign jabatan, unique constraint |
| **Dependency** | Phase 1 |
| **Risiko** | Tinggi — `FlattenedTreeService` rewrite, tree building berubah fundamental |
| **Acceptance** | SOTK menampilkan tree UNOR dengan jabatan, tidak ada duplikasi jabatan per UNOR |

### Phase 3: Pegawai + Penempatan
**Tujuan:** Pegawai ditempatkan via penempatan (UNOR+Jabatan), multi pegawai boleh pada UNOR+Jabatan sama.

| Item | Detail |
|---|---|
| **Backend** | `PegawaiController` (hapus constraint struktural=1); `PenempatanController` (CRUD penempatan, constraint 1 aktif per pegawai); update query di `Pegawai` model |
| **Frontend** | `admin/pegawai/*` — tambah form penempatan; tampilkan riwayat penempatan |
| **Test** | Multi pegawai pada UNOR+Jabatan sama; constraint 1 penempatan aktif; riwayat |
| **Dependency** | Phase 2 |
| **Risiko** | Sedang |
| **Acceptance** | Beberapa pegawai bisa ditempatkan pada UNOR+Jabatan yang sama |

### Phase 4: Kebutuhan Pegawai
**Tujuan:** Kebutuhan sebagai entitas terpisah per UNOR+Jabatan.

| Item | Detail |
|---|---|
| **Backend** | `KebutuhanPegawaiController` (CRUD); update query kebutuhan di tree |
| **Frontend** | Form input kebutuhan per UNOR+Jabatan; tampilan tree dengan kolom Kebutuhan |
| **Test** | Kebutuhan=3, satu record; kebutuhan=0 valid; unique constraint |
| **Dependency** | Phase 2 (SOTK), Phase 3 (Penempatan) |
| **Risiko** | Rendah |
| **Acceptance** | Satu record kebutuhan bernilai N, tidak membuat N node |

### Phase 5: Bezetting + Selisih
**Tujuan:** Bezetting = COUNT pegawai aktif via penempatan per UNOR+Jabatan.

| Item | Detail |
|---|---|
| **Backend** | Query Bezetting dari `penempatan_pegawai` WHERE is_active=true GROUP BY unor_id, jabatan_id; hitung Selisih = Bezetting - Kebutuhan |
| **Frontend** | Halaman Bezetting terpisah dari Kebutuhan; tabel: UNOR, Jabatan, Kebutuhan, Bezetting, Selisih |
| **Test** | Kebutuhan=3, pegawai=2 → Bezetting=2, Selisih=-1 |
| **Dependency** | Phase 4 |
| **Risiko** | Rendah |
| **Acceptance** | Selisih negatif=kekurangan, positif=kelebihan, 0=sesuai |

### Phase 6: Proyeksi 5 Tahun
**Tujuan:** Proyeksi kebutuhan akibat pensiun per UNOR+Jabatan+Tahun.

| Item | Detail |
|---|---|
| **Backend** | `ProjectionService` — grouping via penempatan (UNOR+Jabatan); query: `penempatan_pegawai` JOIN `pegawai` GROUP BY unor_id, jabatan_id, YEAR(tanggal_pensiun) |
| **Frontend** | Halaman Kebutuhan dengan kolom proyeksi 5 tahun; export Excel |
| **Test** | Skenario: 2026=1, 2027=0, 2028=2, 2029=1, 2030=0 → hasil persis sama; bukan kumulatif; bukan jumlah tersisa |
| **Dependency** | Phase 5 |
| **Risiko** | Rendah — formula sudah benar, hanya grouping berubah |
| **Acceptance** | Proyeksi = COUNT pensiun per tahun, 1:1 replacement |

### Phase 7: Tugas Tambahan
**Tujuan:** Kepala Sekolah, Kepala Puskesmas sebagai tugas tambahan tanpa mengubah jabatan utama.

| Item | Detail |
|---|---|
| **Backend** | `MasterTugasTambahan` (CRUD); `TugasTambahanPegawai` (assign/unassign); constraint: tidak mengubah jabatan/penempatan utama |
| **Frontend** | CRUD master tugas tambahan; di form pegawai: assign tugas tambahan + UNOR; tampilkan di detail pegawai |
| **Test** | Guru Matematika + Kepala Sekolah → jabatan tetap Guru; Dokter + Kepala Puskesmas → jabatan tetap Dokter |
| **Dependency** | Phase 3 |
| **Risiko** | Rendah — fitur baru |
| **Acceptance** | Tugas tambahan tidak mengubah jabatan ASN maupun penempatan utama |

### Phase 8: UI Polish + Export + Cleanup
**Tujuan:** Perbaikan UI, penamaan, export, dan pembersihan.

| Item | Detail |
|---|---|
| **Backend** | Fix penamaan controller (BezettingController↔KebutuhanController); hapus kode obsolete; `KodeJabatanGenerator` evaluasi |
| **Frontend** | Update sidebar labels; konsistensi istilah "Bezetting"; perbaikan UI tree SOTK; dashboard update query; Livewire components opsional |
| **Export** | Update `BezettingExport` dan `KebutuhanExport` ke schema baru |
| **Cleanup** | Hapus `layouts/navigation.blade.php` (legacy); hapus view tidak terpakai; hapus `induk_jabatan_id` references |
| **Test** | Full regression test |
| **Dependency** | Phase 1-7 |
| **Risiko** | Rendah |
| **Acceptance** | Semua test pass; UI konsisten; tidak ada referensi arsitektur lama |

---

## H. Test Plan

### H.1 Unit Tests

| Test | Skenario | Expected |
|---|---|---|
| **Unor hierarchy** | Buat UNOR, child UNOR | Tree benar, parent_id sesuai |
| **Unor circular** | Coba buat circular reference | Ditolak |
| **SOTK unique** | Assign jabatan sama ke UNOR sama 2x | Ditolak (unique constraint) |
| **SOTK multi** | Assign jabatan sama ke UNOR berbeda | Diterima |
| **Kebutuhan single** | Input kebutuhan=3 untuk UNOR+Jabatan | 1 record, jumlah=3 |
| **Kebutuhan duplicate** | Input kebutuhan 2x untuk UNOR+Jabatan+tahun sama | Ditolak |
| **Kebutuhan nol** | Input kebutuhan=0 | Diterima |
| **Kebutuhan negatif** | Input kebutuhan=-1 | Ditolak |
| **Penempatan multi** | 3 pegawai pada UNOR+Jabatan sama | Diterima |
| **Penempatan aktif** | 2 penempatan aktif untuk 1 pegawai | Ditolak (hanya 1 aktif) |
| **Penempatan riwayat** | Nonaktifkan penempatan lama, buat baru | Riwayat tersimpan |
| **Bezetting** | Kebutuhan=3, pegawai aktif=2 | Bezetting=2, Selisih=-1 |
| **Proyeksi** | 1 pensiun 2026, 2 pensiun 2028 | 2026=1, 2027=0, 2028=2, 2029=0, 2030=0 |
| **Proyeksi non-kumulatif** | Pensiun 2027=1 | 2027=1, 2028=0 (bukan jumlah tersisa) |
| **Guru sub-jabatan** | Guru + Matematika + Ahli Madya | Display: "Guru Matematika Ahli Madya" |
| **Dokter sub-jabatan** | Dokter + Spesialis Anak + Ahli Madya | Display: "Dokter Spesialis Anak Ahli Madya" |
| **Tugas tambahan** | Guru + Kepala Sekolah | Jabatan tetap Guru, penempatan tetap |
| **BUP Guru** | Guru, jenjang Ahli Madya | BUP = 60 |
| **BUP Ahli Utama** | Fungsional, jenjang Ahli Utama | BUP = 65 |
| **BUP default** | Pelaksana | BUP = 58 |

### H.2 Feature Tests

| Test | Skenario |
|---|---|
| **SOTK page** | Akses halaman SOTK, lihat tree UNOR+jabatan |
| **Kebutuhan page** | Akses halaman Kebutuhan, lihat tabel: UNOR, Jabatan, Kebutuhan, Bezetting, Selisih, Proyeksi 5 thn |
| **Bezetting page** | Akses halaman Bezetting, lihat Bezetting per UNOR+Jabatan |
| **Filter OPD** | Filter Bezetting/Kebutuhan per UNOR |
| **Export Excel** | Export Kebutuhan + Bezetting |
| **Pegawai create** | Tambah pegawai dengan penempatan |
| **Pegawai multi** | Tambah 3 pegawai ke UNOR+Jabatan sama |
| **Tugas tambahan** | Assign Kepala Sekolah ke Guru, verifikasi jabatan tidak berubah |

---

## I. Risk Analysis

| # | Risiko | Severity | Mitigasi |
|---|---|---|---|
| 1 | **Kehilangan User** — User development terhapus saat migrate:fresh | LOW | Data user sudah di-cover `UserSeeder`. Sebelum fresh, backup data users via export. |
| 2 | **Kehilangan OPD** — OPD development terhapus | LOW | Data OPD sudah di-cover `OpdSeeder` (→`UnorSeeder`). Backup jika ada OPD tambahan. |
| 3 | **Mapping OPD → UNOR** — OPD existing tidak punya parent_id | LOW | Data development, seeder baru yang menentukan hierarki. |
| 4 | **FK constraint saat reset** — Tabel dependent dihapus tidak sesuai urutan | MEDIUM | `migrate:fresh` menghapus semua tabel, tidak ada issue FK. Seed berurutan sesuai dependency. |
| 5 | **Duplicate Master Jabatan** — Entry ganda setelah perubahan field | MEDIUM | Unique constraint pada kombinasi (nama_jabatan, jenis_jabatan, jenjang, sub_jabatan). |
| 6 | **Duplicate Jabatan pada SOTK** — Assign jabatan sama 2x ke UNOR | LOW | Unique constraint `sotk(unor_id, jabatan_id)`. |
| 7 | **Constraint 1 jabatan = 1 pegawai** — Masih ada di kode lain | MEDIUM | Grep seluruh kode untuk constraint lama. Hapus dari `PegawaiController`. |
| 8 | **Bezetting salah** — Query dari tabel/relasi salah | HIGH | Test ketat: Bezetting = COUNT penempatan aktif. Verifikasi dengan data seeded yang diketahui jumlahnya. |
| 9 | **Selisih terbalik** — Selisih = Kebutuhan - Bezetting (salah) | MEDIUM | CLAUDE.md jelas: Selisih = Bezetting - Kebutuhan. Test: 2-3=-1. |
| 10 | **BUP salah** — Deteksi Guru string-based | MEDIUM | Pindahkan ke deteksi via `master_jabatan.sub_jabatan` atau field jenis khusus. Test BupCalculator standalone. |
| 11 | **Proyeksi salah** — Menghitung jumlah tersisa, bukan pensiun | LOW | Formula existing sudah benar (hanya pensiun). Verifikasi tetap benar setelah grouping berubah. |
| 12 | **Sub-jabatan tercampur** — Guru Matematika vs Guru Bahasa Indonesia tidak terbedakan | MEDIUM | `sub_jabatan` sebagai field terpisah di Master Jabatan + Jabatan. Test: kebutuhan, Bezetting, proyeksi per sub-jabatan. |
| 13 | **Tugas tambahan ganti jabatan** — Kepala Sekolah jadi jabatan utama | LOW | Fitur baru — constraint di model sudah mencegah. Test: assign tugas tambahan, verifikasi jabatan tetap. |
| 14 | **UI masih asumsi lama** — Label "Unit Organisasi" dari induk jabatan | MEDIUM | Audit setiap Blade view. "Unit Organisasi" = UNOR, bukan induk jabatan. |
| 15 | **FlattenedTreeService rewrite** — Tree building salah | HIGH | Test paling ketat. Bandingkan output tree dengan perhitungan manual. Max depth dari UNOR hierarchy, bukan hardcoded 4. |
| 16 | **PenempatanPegawai constraint** — Beberapa pegawai tidak bisa pada UNOR+Jabatan sama | HIGH | Pastikan tidak ada unique(`unor_id`, `jabatan_id`) di `penempatan_pegawai`. Unique hanya pada (`pegawai_id`, `is_active`) conditional. |
| 17 | **Circular reference UNOR** — UNOR A parent UNOR B parent UNOR A | MEDIUM | Validasi server-side sebelum save (seperti yang sudah dilakukan JabatanController untuk circular reference). |

---

## J. Recommended Execution Plan

### Urutan Implementasi

```
Phase 1: Database + Master Data       (estimasi: 1-2 hari)
    ↓
Phase 2: SOTK (UNOR Tree + Jabatan)   (estimasi: 2-3 hari)
    ↓
Phase 3: Pegawai + Penempatan         (estimasi: 2-3 hari)
    ↓
Phase 4: Kebutuhan Pegawai            (estimasi: 1-2 hari)
    ↓
Phase 5: Bezetting + Selisih          (estimasi: 1-2 hari)
    ↓
Phase 6: Proyeksi 5 Tahun             (estimasi: 1 hari)
    ↓
Phase 7: Tugas Tambahan               (estimasi: 1-2 hari)
    ↓
Phase 8: UI Polish + Cleanup          (estimasi: 1-2 hari)
```

### Prinsip Pelaksanaan

1. **Satu phase = satu PR/branch** yang bisa di-review dan di-rollback secara independen.
2. **Test ditulis sebelum/selama implementasi**, bukan setelah.
3. **Setiap phase harus membuat semua test existing tetap pass** (atau update test jika memang test menguji asumsi lama).
4. **`migrate:fresh --seed` harus sukses di setiap phase**.
5. **Jangan hapus data/tabel sebelum phase selesai dan test pass.**
6. **Jalankan full test suite setelah setiap phase selesai.**

### Checkpoint per Phase

| Phase | Checkpoint |
|---|---|
| 1 | `migrate:fresh --seed` sukses, semua model memiliki relasi benar |
| 2 | SOTK tree ditampilkan dengan benar, UNOR hierarkis |
| 3 | Pegawai bisa ditempatkan, multi pegawai per UNOR+Jabatan |
| 4 | Kebutuhan diinput sebagai nilai, bukan node |
| 5 | Bezetting = COUNT penempatan aktif, Selisih benar |
| 6 | Proyeksi = COUNT pensiun per UNOR+Jabatan+Tahun |
| 7 | Tugas tambahan terpisah dari jabatan utama |
| 8 | Semua test pass, tidak ada referensi arsitektur lama |

---

## STOP - MENUNGGU PERSETUJUAN

Laporan audit, gap analysis, dan rencana implementasi telah selesai.

**Belum ada perubahan kode, database, migration, atau file apa pun yang dilakukan.**

Menunggu persetujuan eksplisit sebelum memulai Phase 1 atau fase mana pun.

