<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kategori;

class KategoriSeeder extends Seeder
{
    public function run()
    {
        $kategoris = [
            ['nama' => 'Politik', 'deskripsi' => 'Isu terkait politik dan pemerintahan'],
            ['nama' => 'Ekonomi', 'deskripsi' => 'Isu terkait ekonomi dan keuangan'],
            ['nama' => 'Sosial', 'deskripsi' => 'Isu terkait masyarakat dan budaya'],
            ['nama' => 'Kesehatan', 'deskripsi' => 'Isu terkait kesehatan masyarakat'],
            ['nama' => 'Pendidikan', 'deskripsi' => 'Isu terkait pendidikan dan akademik'],
        ];

        foreach ($kategoris as $kategori) {
            Kategori::create($kategori);
        }
    }
}