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
        'is_selected',
        'display_order',
        'display_order_google',
        'display_order_x',
    ];

    /**
     * Atribut yang harus dikonversi.
     *
     * @var array
     */
    protected $casts = [
        'tanggal' => 'datetime',
        'is_selected' => 'boolean',
        'display_order' => 'integer',
        'display_order_google' => 'integer',
        'display_order_x' => 'integer',
    ];

    // Relasi ke MediaSosial
    public function mediaSosial()
    {
        return $this->belongsTo(MediaSosial::class);
    }
}