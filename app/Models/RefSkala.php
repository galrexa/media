<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefSkala extends Model
{
    use HasFactory;

    protected $table = 'ref_skala';
    
    protected $fillable = [
        'nama', 'kode', 'deskripsi', 'urutan', 'aktif'
    ];
}