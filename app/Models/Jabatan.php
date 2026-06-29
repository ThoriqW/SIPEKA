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
        'kebutuhan',
        'opd_id',
        'induk_jabatan_id',
    ];

    protected function casts(): array
    {
        return [
            'kebutuhan' => 'integer',
            'kelas_jabatan' => 'integer',
        ];
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function induk(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'induk_jabatan_id');
    }

    public function anak(): HasMany
    {
        return $this->hasMany(Jabatan::class, 'induk_jabatan_id');
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(Pegawai::class);
    }
}
