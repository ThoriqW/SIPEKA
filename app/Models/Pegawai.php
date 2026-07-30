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
        'nip',
        'jenis_kepegawaian',
        'tanggal_lahir',
        'golongan_pangkat',
        'pendidikan',
        'kualifikasi_pendidikan',
        'jenjang',
        'jabatan_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
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
