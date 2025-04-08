<?php
// app/Models/ReferensiIsu.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferensiIsu extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'referensi_isus';

    /**
     * Atribut yang dapat diisi (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'isu_id',
        'judul',
        'url',
        'thumbnail',
    ];

    /**
     * Mendapatkan isu yang terkait dengan referensi ini.
     */
    public function isu()
    {
        return $this->belongsTo(Isu::class, 'isu_id');
    }
}