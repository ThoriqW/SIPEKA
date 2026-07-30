<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Backward compatibility alias untuk Unor.
 * Tabel opd sudah di-rename ke unor.
 * Gunakan Unor::class untuk kode baru.
 */
class Opd extends Model
{
    protected $table = 'unor';

    protected $fillable = [
        'nama_unor',
        'kode_unor',
    ];
}
