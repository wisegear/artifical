<?php

use App\Models\Post;
use App\Models\User;

function slugPostInput(string $title): array
{
    return ['title' => $title, 'post_date' => '13/09/2026', 'seo_summary' => 'Research notes.', 'body' => '<p>Research notes.</p>', 'action' => 'draft'];
}

it('creates readable slugs and numbers duplicate titles including drafts', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    foreach (['collecting-data-for-ai', 'collecting-data-for-ai-2', 'collecting-data-for-ai-3'] as $slug) {
        $this->actingAs($admin)->post(route('admin.posts.store'), slugPostInput('Collecting Data for AI'))
            ->assertRedirect();
        $this->assertDatabaseHas('posts', ['title' => 'Collecting Data for AI', 'slug' => $slug]);
    }
});

it('skips occupied suffixes and reserved old links', function () {
    Post::factory()->create(['slug' => 'research', 'previous_slug' => 'research-2']);
    Post::factory()->create(['slug' => 'research-3']);
    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->post(route('admin.posts.store'), slugPostInput('Research'))->assertRedirect();

    $this->assertDatabaseHas('posts', ['title' => 'Research', 'slug' => 'research-4']);
});

it('keeps the URL when a title changes', function () {
    $post = Post::factory()->create(['slug' => 'original-title']);
    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->put(route('admin.posts.update', $post), slugPostInput('Changed title'))->assertRedirect();

    $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Changed title', 'slug' => 'original-title']);
});

it('uses a fallback when the title cannot form a slug', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->post(route('admin.posts.store'), slugPostInput('!!!'))->assertRedirect();

    $this->assertDatabaseHas('posts', ['title' => '!!!', 'slug' => 'post']);
});

it('converts existing random slugs and permanently redirects old links', function () {
    $migration = require glob(database_path('migrations/*add_previous_slug_to_posts_table.php'))[0];
    $migration->down();
    $first = Post::factory()->create(['title' => 'Collecting Data for AI', 'slug' => 'collecting-data-for-ai-bsiqlebo', 'is_published' => true]);
    $second = Post::factory()->create(['title' => 'Collecting Data for AI', 'slug' => 'collecting-data-for-ai-abcdefgh']);
    $migration->up();

    $this->assertDatabaseHas('posts', ['id' => $first->id, 'slug' => 'collecting-data-for-ai', 'previous_slug' => 'collecting-data-for-ai-bsiqlebo']);
    $this->assertDatabaseHas('posts', ['id' => $second->id, 'slug' => 'collecting-data-for-ai-2']);
    $this->get(route('posts.show', 'collecting-data-for-ai-bsiqlebo'))
        ->assertStatus(301)->assertRedirectToRoute('posts.show', 'collecting-data-for-ai');
    $this->get(route('posts.show', 'collecting-data-for-ai'))->assertSee('Collecting Data for AI');
    $this->get(route('posts.show', 'collecting-data-for-ai-abcdefgh'))->assertNotFound();

    $migration->down();
    $this->assertDatabaseHas('posts', ['id' => $first->id, 'slug' => 'collecting-data-for-ai-bsiqlebo']);
    $migration->up();
});
