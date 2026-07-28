<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterTugasTambahan extends Model
{
    protected $table = 'master_tugas_tambahan';

    protected $fillable = [
        'nama_tugas',
    ];

    public function tugasTambahanPegawai(): HasMany
    {
        return $this->hasMany(TugasTambahanPegawai::class, 'tugas_tambahan_id');
    }
}
