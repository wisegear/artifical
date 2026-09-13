<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function articleInput(array $overrides = []): array
{
    return array_merge(['title' => 'Thinking with machines', 'post_date' => '13/09/2026', 'seo_summary' => 'A human perspective on AI.', 'category' => 'AI & Society', 'tags' => 'AI, research, AI', 'body' => '<p>A useful introduction.</p>', 'action' => 'draft'], $overrides);
}

it('shows only published posts whose date has arrived and supports filters', function () {
    $this->travelTo(now()->setDate(2026, 9, 13));
    $live = Post::factory()->create(['title' => 'Visible research', 'is_published' => true, 'category' => 'Research', 'tags' => ['models']]);
    $draft = Post::factory()->create(['title' => 'Private draft']);
    $future = Post::factory()->create(['title' => 'Future article', 'is_published' => true, 'post_date' => today()->addDay()]);
    $this->get('/')->assertSee('Visible research')->assertDontSee('Private draft')->assertDontSee('Future article');
    $this->get('/?category=Research&tag=models&q=Visible')->assertSee('Visible research');
    $this->get('/?q=nonexistent')->assertSee('No stories found.')->assertDontSee('Visible research');
    $this->get(route('posts.show', $draft->slug))->assertNotFound();
    $this->get(route('posts.show', $future->slug))->assertNotFound();
    $this->get(route('posts.show', $live->slug))->assertSee('13/09/2026');
});

it('keeps subscriber content out of guest HTML and unlocks it for readers', function () {
    $post = Post::factory()->create(['is_published' => true, 'is_subscriber' => true, 'body' => '<p>'.str_repeat('Introduction. ', 100).'</p><p>SECRET MEMBER CONTENT</p>']);
    $this->get(route('posts.show', $post->slug))->assertSee('The rest is for our readers.')->assertDontSee('SECRET MEMBER CONTENT');
    $this->actingAs(User::factory()->create())->get(route('posts.show', $post->slug))->assertSee('SECRET MEMBER CONTENT')->assertDontSee('The rest is for our readers.');
});

it('does not grant admin access through registration', function () {
    $this->post('/register', ['name' => 'Reader', 'email' => 'reader@example.com', 'password' => 'long-reader-password', 'password_confirmation' => 'long-reader-password', 'is_admin' => true])->assertRedirect('/');
    $user = User::where('email', 'reader@example.com')->firstOrFail();
    expect($user->is_admin)->toBeFalse();
    $this->assertAuthenticatedAs($user);
    $this->get('/admin/posts')->assertForbidden();
    $this->post('/admin/posts', articleInput())->assertForbidden();
    $this->assertDatabaseCount('posts', 0);
});

it('protects every admin endpoint from readers', function () {
    $post = Post::factory()->create();
    $this->get('/admin/posts')->assertRedirect('/login');
    $this->actingAs(User::factory()->create());
    $this->get('/admin/posts/create')->assertForbidden();
    $this->get(route('admin.posts.edit', $post))->assertForbidden();
    $this->put(route('admin.posts.update', $post), articleInput())->assertForbidden();
    $this->delete(route('admin.posts.destroy', $post))->assertForbidden();
    $this->post(route('admin.images.store'))->assertForbidden();
    $this->assertModelExists($post);
});

it('creates drafts, publishes, features, unpublishes and deletes posts', function () {
    $this->travelTo(now()->setDate(2026, 9, 13));
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->get('/admin/posts/create')->assertSee('data-editor', false);
    $this->post('/admin/posts', articleInput(['image' => UploadedFile::fake()->image('cover.jpg', 2000, 1000)]))->assertSessionHasNoErrors()->assertRedirect();
    $post = Post::firstOrFail();
    expect($post->is_published)->toBeFalse();
    expect($post->tags)->toBe(['AI', 'research']);
    Storage::disk('public')->assertExists($post->image);
    $size = getimagesize(Storage::disk('public')->path($post->image));
    expect($size[0])->toBe(1600);
    expect($size[1])->toBe(800);
    expect($size['mime'])->toBe('image/webp');
    $this->put(route('admin.posts.update', $post), articleInput(['action' => 'publish', 'is_featured' => 1, 'is_subscriber' => 1]))->assertSessionHasNoErrors();
    expect($post->fresh())->is_published->toBeTrue()->is_featured->toBeTrue()->is_subscriber->toBeTrue();
    $this->get('/')->assertSee('IN THE SPOTLIGHT');
    $oldImage = $post->image;
    $this->put(route('admin.posts.update', $post), articleInput(['image' => UploadedFile::fake()->image('next.png')]))->assertSessionHasNoErrors();
    expect($post->fresh()->is_published)->toBeFalse();
    Storage::disk('public')->assertMissing($oldImage);
    $newImage = $post->fresh()->image;
    $this->delete(route('admin.posts.destroy', $post))->assertRedirect(route('admin.posts.index'));
    $this->assertModelMissing($post);
    Storage::disk('public')->assertMissing($newImage);
});

it('validates summaries dates and image types without saving invalid posts', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->post('/admin/posts', articleInput(['seo_summary' => str_repeat('a', 256), 'post_date' => '31/02/2026', 'image' => UploadedFile::fake()->create('bad.svg', 1, 'image/svg+xml')]))->assertInvalid(['seo_summary', 'post_date', 'image']);
    $this->assertDatabaseCount('posts', 0);
});

it('sanitizes rich text while preserving safe formatting', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->post('/admin/posts', articleInput(['body' => '<p><strong>Safe text</strong><script>alert(1)</script><img src="/storage/posts/example.webp" onerror="alert(2)"><a href="javascript:alert(3)">link</a></p>']))->assertSessionHasNoErrors();
    $body = Post::firstOrFail()->body;
    expect($body)->toContain('<strong>Safe text</strong>')->toContain('/storage/posts/example.webp')->not->toContain('<script')->not->toContain('onerror')->not->toContain('javascript:');
});

it('uploads TinyMCE images using the same image processor', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $response = $this->postJson(route('admin.images.store'), ['file' => UploadedFile::fake()->image('inline.png', 1800, 900)]);
    $response->assertSuccessful()->assertJsonStructure(['location']);
    expect($response->json('location'))->toEndWith('.webp');
    $this->postJson(route('admin.images.store'), ['file' => UploadedFile::fake()->create('bad.txt', 1)])->assertInvalid('file');
});

it('logs readers in and out and rejects wrong passwords', function () {
    $user = User::factory()->create(['password' => 'long-reader-password']);
    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertInvalid('email');
    $this->assertGuest();
    $this->post('/login', ['email' => $user->email, 'password' => 'long-reader-password'])->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});

it('rejects duplicate registrations and short passwords', function () {
    $user = User::factory()->create();
    $this->post('/register', ['name' => 'Reader', 'email' => $user->email, 'password' => 'short', 'password_confirmation' => 'short'])->assertInvalid(['email', 'password']);
    $this->assertDatabaseCount('users', 1);
});

it('grants admin access only to an explicitly selected registered account', function () {
    $user = User::factory()->create();
    $this->artisan('blog:admin', ['email' => 'missing@example.com'])->assertFailed();
    expect($user->fresh()->is_admin)->toBeFalse();
    $this->artisan('blog:admin', ['email' => $user->email])->assertSuccessful();
    $this->actingAs($user->fresh())->get('/admin/posts')->assertSee('Your stories');
});

it('limits repeated login attempts', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/login', ['email' => 'limited@example.com', 'password' => 'incorrect'])->assertInvalid('email');
    }
    $this->post('/login', ['email' => 'limited@example.com', 'password' => 'incorrect'])->assertTooManyRequests();
});
