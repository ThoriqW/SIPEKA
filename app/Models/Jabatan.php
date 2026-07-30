<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    protected $table = 'jabatan';

    protected $fillable = [
        'nama_jabatan',
        'kode_jabatan',
        'jenis_jabatan',
        'kelas_jabatan',
        'jenjang',
    ];

    protected function casts(): array
    {
        return [
            'kelas_jabatan' => 'integer',
        ];
    }

    /**
     * Dapatkan UNOR utama jabatan ini melalui tabel SOTK.
     * Mengembalikan UNOR pertama (non-root) dari SOTK entries.
     */
    public function unor(): ?Unor
    {
        return $this->sotkEntries->first()?->unor;
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(Pegawai::class);
    }

    /**
     * SOTK entries yang menempatkan jabatan ini di berbagai UNOR.
     */
    public function sotkEntries(): HasMany
    {
        return $this->hasMany(Sotk::class);
    }

    /**
     * Kebutuhan pegawai untuk jabatan ini.
     */
    public function kebutuhanPegawai(): HasMany
    {
        return $this->hasMany(KebutuhanPegawai::class);
    }
}
