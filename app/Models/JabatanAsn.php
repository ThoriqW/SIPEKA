<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JabatanAsn extends Model
{
    protected $table = 'jabatan_asn';

    protected $fillable = [
        'nama_jabatan_asn',
        'jenis_jabatan',
        'jenjang',
        'parent_id',
        'kode_jabatan_asn',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(Pegawai::class);
    }

    /**
     * Scope filter by jenis_jabatan.
     */
    public function scopeByJenis($query, string $jenis)
    {
        return $query->where('jenis_jabatan', $jenis)
            ->orderBy('parent_id')
            ->orderBy('nama_jabatan_asn');
    }

    /**
     * Scope root entries only (parent_id = null).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}
