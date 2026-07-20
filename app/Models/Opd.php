<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opd extends Model
{
    protected $table = 'opd';

    protected $fillable = [
        'nama_opd',
        'kode_opd',
    ];

    public function jabatan(): HasMany
    {
        return $this->hasMany(Jabatan::class);
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(Pegawai::class);
    }
}
