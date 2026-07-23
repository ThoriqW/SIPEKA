<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pegawai extends Model
{
    protected $table = 'pegawai';

    protected $fillable = [
        'nama',
        'nip',
        'jenis_kepegawaian',
        'tanggal_lahir',
        'golongan_pangkat',
        'pendidikan',
        'kualifikasi_pendidikan',
        'jenjang',
        'opd_id',
        'jabatan_id',               // @deprecated — digantikan posisi_organisasi_id
        'jabatan_asn_id',           // FK ke jabatan_asn
        'posisi_organisasi_id',     // FK ke node_organisasi (jenis=POSISI)
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    /** @deprecated — gunakan posisiOrganisasi() */
    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    /**
     * Jabatan ASN yang melekat pada pegawai (contoh: "Guru Ahli Muda").
     */
    public function jabatanAsn(): BelongsTo
    {
        return $this->belongsTo(JabatanAsn::class);
    }

    /**
     * Posisi Organisasi yang ditempati pegawai pada struktur organisasi.
     */
    public function posisiOrganisasi(): BelongsTo
    {
        return $this->belongsTo(NodeOrganisasi::class, 'posisi_organisasi_id');
    }
}
