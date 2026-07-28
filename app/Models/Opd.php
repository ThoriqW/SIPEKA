<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Backward compatibility alias untuk Unor.
 * Tabel opd sudah di-rename ke unor.
 * Gunakan Unor::class untuk kode baru.
 */
class Opd extends Model
{
    protected $table = 'unor';

    protected $fillable = [
        'nama_unor',
        'kode_unor',
    ];

    public function jabatan(): HasMany
    {
        return $this->hasMany(Jabatan::class, 'opd_id');
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(Pegawai::class, 'opd_id');
    }
}
