<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('is_admin', true)->first();

        if (! $author) {
            $this->command?->warn('Create an admin account before adding the example draft.');

            return;
        }

        Post::firstOrCreate(['slug' => 'welcome-to-artificially-curious'], [
            'user_id' => $author->id,
            'title' => 'A space for human curiosity',
            'post_date' => today(),
            'seo_summary' => 'Welcome to Artificially Curious: a journal of questions, experiments and perspectives on artificial intelligence.',
            'category' => 'From the editor',
            'tags' => ['AI', 'Curiosity'],
            'body' => '<p>Every useful exploration starts with a question.</p><h2>What are you curious about?</h2><p>This is a space to look beyond headlines, try things out and share what we learn along the way.</p>',
            'is_published' => false,
        ]);
    }
}
