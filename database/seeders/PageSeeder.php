<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        Page::firstOrCreate(['slug' => 'about'], [
            'title' => 'About Artificially Curious',
            'description' => 'An independent journal exploring artificial intelligence through human curiosity.',
            'body' => '<p>A space for ideas, experiments and honest perspectives on artificial intelligence.</p>',
        ]);
    }
}
