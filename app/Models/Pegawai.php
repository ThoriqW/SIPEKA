<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pegawai extends Model
{
    protected $table = 'pegawai';

    protected $fillable = [
        'nama',
        'gelar_depan',
        'gelar_belakang',
        'nip',
        'jenis_kepegawaian',
        'tanggal_lahir',
        'golongan_pangkat',
        'pendidikan',
        'kualifikasi_pendidikan',
        'jabatan_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    /**
     * Nama lengkap berikut gelar, mis. "Drs. Abdul Muis, M.Si.".
     *
     * Satu-satunya tempat nama pegawai dirakit — dipakai setiap kali nama
     * ditampilkan, supaya koma dan spasinya tidak berbeda antarhalaman.
     *
     * Data lama menyimpan gelarnya menyatu di dalam `nama` dan kolom gelarnya
     * kosong, sehingga rakitan ini mengembalikan `nama` apa adanya. Tidak ada
     * gelar yang tergandakan untuk kedua jenis data.
     */
    public function namaLengkap(): string
    {
        $nama = trim((string) $this->nama);
        $gelarDepan = trim((string) $this->gelar_depan);
        $gelarBelakang = trim((string) $this->gelar_belakang);

        return ($gelarDepan !== '' ? $gelarDepan . ' ' : '')
            . $nama
            . ($gelarBelakang !== '' ? ', ' . $gelarBelakang : '');
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    /**
     * Riwayat penempatan pegawai.
     */
    public function penempatan(): HasMany
    {
        return $this->hasMany(PenempatanPegawai::class);
    }

    /**
     * Penempatan aktif saat ini (hanya satu).
     */
    public function penempatanAktif(): HasOne
    {
        return $this->hasOne(PenempatanPegawai::class)->where('is_active', true);
    }

    /**
     * Tugas tambahan pegawai.
     */
    public function tugasTambahan(): HasMany
    {
        return $this->hasMany(TugasTambahanPegawai::class);
    }
}
