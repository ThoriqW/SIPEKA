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

    /**
     * Label jalur UNOR, mis. "Kecamatan Palu Barat » Kelurahan Birobuli » Sekretariat".
     *
     * Dipakai setiap kali nama UNOR ditampilkan di luar pohonnya sendiri —
     * tanpa jalur, beberapa UNOR berbeda bisa berlabel sama persis (mis.
     * "Sekretariat" milik kecamatan dan milik kelurahan).
     *
     * @param  \Illuminate\Support\Collection  $allUnorById  Seluruh UNOR di-keyBy(id), dioper agar tidak query berulang.
     * @param  int|null  $stopAtId  Bila diisi, jalur berhenti DI ATAS UNOR tersebut dan namanya
     *                              tidak ikut disertakan — dipakai untuk jalur relatif terhadap
     *                              Unor Induk yang sudah dipilih di dropdown sebelahnya.
     */
    public function pathLabel($allUnorById, ?int $stopAtId = null): string
    {
        // UNOR itu sendiri adalah induknya — tidak ada jalur yang perlu ditampilkan.
        if ($stopAtId !== null && (int) $this->id === $stopAtId) {
            return $this->nama_unor;
        }

        $parts = [$this->nama_unor];
        $cursor = $this;

        // Batas iterasi sebagai pengaman terhadap data siklik.
        for ($i = 0; $i < 100; $i++) {
            $parent = $allUnorById->get($cursor->parent_id);

            if (!$parent) {
                break;
            }

            if ($stopAtId !== null) {
                if ((int) $parent->id === $stopAtId) {
                    break;
                }
            } elseif (!$parent->parent_id) {
                break; // root (Pemkot) tidak disertakan
            }

            array_unshift($parts, $parent->nama_unor);
            $cursor = $parent;
        }

        return implode(' » ', $parts);
    }

    /**
     * Scope daftar Perangkat Daerah — anak langsung UNOR root (Pemkot).
     *
     * Dipakai sebagai pilihan filter di menu Kebutuhan dan Bezetting. Satu
     * definisi untuk keduanya supaya daftar yang tampil tidak mungkin
     * menyimpang antarhalaman.
     */
    public function scopePerangkatDaerah($query)
    {
        return $query->whereNotNull('parent_id')
            ->whereHas('parent', fn($q) => $q->whereNull('parent_id'))
            ->orderBy('nama_unor');
    }
}
