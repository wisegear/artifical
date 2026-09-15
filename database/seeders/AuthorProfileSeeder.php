<?php

namespace Database\Seeders;

use App\Models\AuthorProfile;
use Illuminate\Database\Seeder;

class AuthorProfileSeeder extends Seeder
{
    public function run(): void
    {
        AuthorProfile::firstOrCreate(['id' => 1]);
    }
}
