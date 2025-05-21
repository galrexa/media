<?php
// app/Models/Kategori.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategoris';

    // Menonaktifkan timestamps
    public $timestamps = false;

    protected $fillable = [
        'nama',
        'deskripsi',
    ];

    public function isus()
    {
        return $this->belongsToMany(Isu::class, 'isu_kategori', 'kategori_id', 'isu_id')
                    ->withTimestamps();
    }
}
