<?php
// app/Models/Image.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'tanggal',
        'image_1',
        'image_2',
        'image_3',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal' => 'date',
    ];
}