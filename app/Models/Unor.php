<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unor extends Model
{
    protected $table = 'unor';

    protected $fillable = [
        'nama_unor',
        'kode_unor',
        'parent_id',
        'singkatan',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Backward compatibility: jabatan yang masih punya opd_id.
     * Akan dihapus di Phase 2 setelah SOTK mengambil alih.
     */
    public function jabatan(): HasMany
    {
        return $this->hasMany(Jabatan::class, 'opd_id');
    }

    /**
     * Backward compatibility: pegawai yang masih punya opd_id.
     * Akan dihapus di Phase 3 setelah penempatan_pegawai mengambil alih.
     */
    public function pegawai(): HasMany
    {
        return $this->hasMany(Pegawai::class, 'opd_id');
    }

    /**
     * SOTK entries untuk UNOR ini.
     */
    public function sotkEntries(): HasMany
    {
        return $this->hasMany(Sotk::class);
    }

    /**
     * Kebutuhan pegawai untuk UNOR ini.
     */
    public function kebutuhanPegawai(): HasMany
    {
        return $this->hasMany(KebutuhanPegawai::class);
    }

    /**
     * Penempatan pegawai di UNOR ini.
     */
    public function penempatanPegawai(): HasMany
    {
        return $this->hasMany(PenempatanPegawai::class);
    }
}
