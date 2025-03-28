<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            RefSkalaSeeder::class,
            RefToneSeeder::class
        ]);
    }
}