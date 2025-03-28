<?php
// app/Models/Isu.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\RefSkala;
use App\Models\RefTone;
use App\Models\Kategori;

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
        'judul',
        'tanggal',
        'isu_strategis',
        'skala',
        'tone',
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

    public function kategoris()
    {
        return $this->belongsToMany(Kategori::class, 'isu_kategori', 'isu_id', 'kategori_id');
    }

        /**
     * Relasi ke tabel ref_skala.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function refSkala()
    {
        return $this->belongsTo(RefSkala::class, 'skala', 'kode');
    }

    /**
     * Relasi ke tabel ref_tone.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function refTone()
    {
        return $this->belongsTo(RefTone::class, 'tone', 'kode');
    }
}