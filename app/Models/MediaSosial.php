<?php
// app/Models/MediaSosial.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaSosial extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'nama',
    ];

    /**
     * Mendapatkan semua trending yang terkait dengan media sosial ini.
     */
    public function trendings()
    {
        return $this->hasMany(Trending::class);
    }
}