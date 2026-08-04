<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TugasTambahanPegawai extends Model
{
    protected $table = 'tugas_tambahan_pegawai';

    protected $fillable = [
        'pegawai_id',
        'tugas_tambahan_id',
        'unor_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function tugasTambahan(): BelongsTo
    {
        return $this->belongsTo(MasterTugasTambahan::class, 'tugas_tambahan_id');
    }

    public function unor(): BelongsTo
    {
        return $this->belongsTo(Unor::class);
    }

    /**
     * Scope: hanya tugas tambahan yang benar-benar aktif —
     * is_active = true DAN (tanggal_selesai null ATAU belum lewat).
     */
    public function scopeAktif($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('tanggal_selesai')
                  ->orWhere('tanggal_selesai', '>=', now()->toDateString());
            });
    }
}
