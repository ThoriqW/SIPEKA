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

    /**
     * Tugas tambahan pegawai di UNOR ini.
     */
    public function tugasTambahanPegawai(): HasMany
    {
        return $this->hasMany(TugasTambahanPegawai::class);
    }
}
