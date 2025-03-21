<?php
// app/Models/Isu.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Isu extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'isus';

    /**
     * Atribut yang dapat diisi (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'tanggal',
        'isu_strategis',
        'kategori',
        'skala',
        'tone',
        'judul',
        //'main_image',
        //'thumbnail_image',
        //'banner_image',
        'rangkuman',
        'narasi_positif',
        'narasi_negatif',
    ];

    /**
     * Atribut yang harus dikonversi.
     *
     * @var array
     */
    protected $casts = [
        'tanggal' => 'datetime',
        'isu_strategis' => 'boolean',
    ];

    /**
     * Mendapatkan semua referensi yang terkait dengan isu ini.
     */
    public function referensi()
    {
        return $this->hasMany(ReferensiIsu::class, 'isu_id');
    }
}