<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KebutuhanPegawai extends Model
{
    protected $table = 'kebutuhan_pegawai';

    protected $fillable = [
        'unor_id',
        'jabatan_id',
        'tahun',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tahun' => 'integer',
        ];
    }

    public function unor(): BelongsTo
    {
        return $this->belongsTo(Unor::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }
}
