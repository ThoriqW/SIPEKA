<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NodeOrganisasi extends Model
{
    protected $table = 'node_organisasi';

    protected $fillable = [
        'nama',
        'kode',
        'jenis',          // 'UNIT' | 'POSISI'
        'parent_id',
        'kelas_jabatan',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'kelas_jabatan' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /*
     * -------------------------------------------------------------------------
     * Relasi
     * -------------------------------------------------------------------------
     */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('nama');
    }

    public function pegawai(): HasMany
    {
        return $this->hasMany(Pegawai::class, 'posisi_organisasi_id');
    }

    /*
     * -------------------------------------------------------------------------
     * Scope
     * -------------------------------------------------------------------------
     */

    public function scopeByJenis($query, string $jenis)
    {
        return $query->where('jenis', $jenis);
    }

    public function scopeUnit($query)
    {
        return $query->where('jenis', 'UNIT');
    }

    public function scopePosisi($query)
    {
        return $query->where('jenis', 'POSISI');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /*
     * -------------------------------------------------------------------------
     * Helper
     * -------------------------------------------------------------------------
     */

    public function isUnit(): bool
    {
        return $this->jenis === 'UNIT';
    }

    public function isPosisi(): bool
    {
        return $this->jenis === 'POSISI';
    }

    /**
     * Cek apakah posisi ini sudah terisi (punya pegawai).
     */
    public function isTerisi(): bool
    {
        return $this->pegawai->isNotEmpty();
    }

    /**
     * Dapatkan semua ID turunan (anak, cucu, dst) dari node ini.
     * Menggunakan BFS untuk menghindari rekursi dalam.
     *
     * @return int[]
     */
    public function getDescendantIds(): array
    {
        $ids = [];
        $queue = $this->children()->pluck('id')->toArray();

        while (!empty($queue)) {
            $ids = array_merge($ids, $queue);
            $queue = self::whereIn('parent_id', $queue)->pluck('id')->toArray();
        }

        return $ids;
    }
}
