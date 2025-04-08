<?php
// app/Models/Trending.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trending extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'media_sosial_id',
        'tanggal',
        'judul',
        'url',
    ];

    /**
     * Atribut yang harus dikonversi.
     *
     * @var array
     */
    protected $casts = [
        'tanggal' => 'datetime',
    ];

    // Relasi ke MediaSosial
    public function mediaSosial()
    {
        return $this->belongsTo(MediaSosial::class);
    }
}