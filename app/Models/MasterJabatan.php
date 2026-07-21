<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterJabatan extends Model
{
    protected $table = 'master_jabatan';

    protected $fillable = [
        'nama_jabatan',
        'jenis_jabatan',
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
     * Scope filter by jenis_jabatan.
     */
    public function scopeByJenis($query, string $jenis)
    {
        return $query->where('jenis_jabatan', $jenis)
            ->orderBy('parent_id')
            ->orderBy('nama_jabatan');
    }
}
