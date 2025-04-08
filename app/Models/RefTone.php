<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefTone extends Model
{
    use HasFactory;

    protected $table = 'ref_tone';
    
    protected $fillable = [
        'nama', 'kode', 'deskripsi', 'warna', 'urutan', 'aktif'
    ];
}