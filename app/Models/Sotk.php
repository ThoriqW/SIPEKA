<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sotk extends Model
{
    protected $table = 'sotk';

    protected $fillable = [
        'unor_id',
        'jabatan_id',
    ];

    public function unor(): BelongsTo
    {
        return $this->belongsTo(Unor::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }
}
