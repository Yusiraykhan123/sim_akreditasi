<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TkrsElementSeeder extends Seeder
{
    public function run(): void
    {
        AccreditationElementsSeeder::seedCategories(['TKRS']);
    }
}
