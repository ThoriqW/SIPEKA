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
        'opd_id',
    ];

    protected function casts(): array
    {
        return [
            'kelas_jabatan' => 'integer',
        ];
    }

    /**
     * Backward compatibility: opd_id sekarang references unor.
     * Akan digantikan oleh SOTK di Phase 2.
     */
    public function opd(): BelongsTo
    {
        return $this->belongsTo(Unor::class, 'opd_id');
    }

    /**
     * Alias untuk opd() — lebih deskriptif.
     */
    public function unor(): BelongsTo
    {
        return $this->belongsTo(Unor::class, 'opd_id');
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
