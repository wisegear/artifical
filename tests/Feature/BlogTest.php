<?php

use App\Models\Post;
use App\Models\User;
use App\Services\BlogImages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function articleInput(array $overrides = []): array
{
    return array_merge(['title' => 'Thinking with machines', 'post_date' => '13/09/2026', 'seo_summary' => 'A human perspective on AI.', 'tags' => 'AI, research, AI', 'body' => '<p>A useful introduction.</p>', 'action' => 'draft'], $overrides);
}

it('shows only published posts whose date has arrived and supports filters', function () {
    $this->travelTo(now()->setDate(2026, 9, 13));
    $live = Post::factory()->create(['title' => 'Visible research', 'is_published' => true, 'tags' => ['models']]);
    $draft = Post::factory()->create(['title' => 'Private draft']);
    $future = Post::factory()->create(['title' => 'Future article', 'is_published' => true, 'post_date' => today()->addDay()]);
    $this->get('/')->assertSee('Visible research')->assertDontSee('Private draft')->assertDontSee('Future article');
    $this->get('/?tag=models&q=Visible')->assertSee('Visible research');
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
    $upload = UploadedFile::fake()->image('cover.jpg', 2000, 1000);
    $original = file_get_contents($upload->getPathname());
    $this->post('/admin/posts', articleInput(['image' => $upload]))->assertSessionHasNoErrors()->assertRedirect();
    $post = Post::firstOrFail();
    expect($post->is_published)->toBeFalse();
    expect($post->tags)->toBe(['AI', 'research']);
    Storage::disk('public')->assertExists($post->image);
    $size = getimagesize(Storage::disk('public')->path($post->image));
    expect($size[0])->toBe(2000);
    expect($size[1])->toBe(1000);
    expect($size['mime'])->toBe('image/jpeg');
    expect(Storage::disk('public')->get($post->image))->toBe($original);
    $stem = pathinfo($post->image, PATHINFO_FILENAME);
    foreach (['thumbnail' => [400, 200], 'small' => [800, 400], 'large' => [1600, 800]] as $variant => $dimensions) {
        $path = 'posts/'.$variant.'-'.$stem.'.webp';
        Storage::disk('public')->assertExists($path);
        $size = getimagesize(Storage::disk('public')->path($path));
        expect([$size[0], $size[1]])->toBe($dimensions);
        expect($size['mime'])->toBe('image/webp');
    }
    $thumbnailBytes = Storage::disk('public')->size('posts/thumbnail-'.$stem.'.webp');
    $smallBytes = Storage::disk('public')->size('posts/small-'.$stem.'.webp');
    $largeBytes = Storage::disk('public')->size('posts/large-'.$stem.'.webp');
    expect($thumbnailBytes)->toBeLessThan($smallBytes);
    expect($smallBytes)->toBeLessThan($largeBytes);
    expect($largeBytes)->toBeLessThan(strlen($original));
    expect(Storage::disk('public')->allFiles('posts'))->toHaveCount(4);
    $this->put(route('admin.posts.update', $post), articleInput(['action' => 'publish', 'is_featured' => 1, 'is_subscriber' => 1]))->assertSessionHasNoErrors();
    expect($post->fresh())->is_published->toBeTrue()->is_featured->toBeTrue()->is_subscriber->toBeTrue();
    $this->get('/')->assertSee('IN THE SPOTLIGHT')->assertSee('small-'.$stem.'.webp')->assertSee('thumbnail-'.$stem.'.webp');
    $this->get(route('posts.show', $post->slug))->assertSee('large-'.$stem.'.webp');
    $this->get(route('admin.posts.edit', $post))->assertSee('thumbnail-'.$stem.'.webp');
    $oldImage = $post->image;
    $this->put(route('admin.posts.update', $post), articleInput(['image' => UploadedFile::fake()->image('next.png')]))->assertSessionHasNoErrors();
    expect($post->fresh()->is_published)->toBeFalse();
    Storage::disk('public')->assertMissing($oldImage);
    foreach (['thumbnail', 'small', 'large'] as $variant) {
        Storage::disk('public')->assertMissing('posts/'.$variant.'-'.$stem.'.webp');
    }
    expect(Storage::disk('public')->allFiles('posts'))->toHaveCount(4);
    $newImage = $post->fresh()->image;
    $this->delete(route('admin.posts.destroy', $post))->assertRedirect(route('admin.posts.index'));
    $this->assertModelMissing($post);
    Storage::disk('public')->assertMissing($newImage);
    expect(Storage::disk('public')->allFiles('posts'))->toBe([]);
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
    expect($response->json('location'))->toContain('/large-')->toEndWith('.webp');
    expect(Storage::disk('public')->allFiles('posts'))->toHaveCount(4);
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

it('preserves small originals without enlarging their variants', function () {
    Storage::fake('public');
    $upload = UploadedFile::fake()->image('small.png', 120, 240);
    $original = file_get_contents($upload->getPathname());
    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->post('/admin/posts', articleInput(['image' => $upload]))->assertSessionHasNoErrors();
    $post = Post::firstOrFail();
    expect(Storage::disk('public')->get($post->image))->toBe($original);
    foreach (['thumbnail', 'small', 'large'] as $variant) {
        $path = 'posts/'.$variant.'-'.pathinfo($post->image, PATHINFO_FILENAME).'.webp';
        $size = getimagesize(Storage::disk('public')->path($path));
        expect([$size[0], $size[1]])->toBe([120, 240]);
    }
});

it('keeps older covers visible and generates their variants when the post is saved', function () {
    Storage::fake('public');
    $upload = UploadedFile::fake()->image('legacy.jpg', 1800, 1200);
    Storage::disk('public')->put('posts/legacy.jpg', file_get_contents($upload->getPathname()));
    $post = Post::factory()->create(['image' => 'posts/legacy.jpg', 'is_published' => true]);
    $this->get('/')->assertSee('/storage/posts/legacy.jpg');
    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->put(route('admin.posts.update', $post), articleInput())->assertSessionHasNoErrors();
    expect($post->fresh()->image)->toBe('posts/legacy.jpg');
    Storage::disk('public')->assertExists(['posts/legacy.jpg', 'posts/thumbnail-legacy.webp', 'posts/small-legacy.webp', 'posts/large-legacy.webp']);
    $size = getimagesize(Storage::disk('public')->path('posts/thumbnail-legacy.webp'));
    expect([$size[0], $size[1]])->toBe([400, 267]);
});

it('cleans up partial uploads and retains the previous cover when a variant cannot be written', function () {
    Storage::fake('public');
    $disk = Storage::disk('public');
    $images = new BlogImages;
    $oldPath = $images->store(UploadedFile::fake()->image('old.jpg'));
    $oldFiles = $disk->allFiles('posts');
    $post = Post::factory()->create(['image' => $oldPath]);
    $failingDisk = Mockery::mock($disk)->makePartial();
    $failingDisk->shouldReceive('put')->withArgs(fn (string $path, string $contents): bool => str_contains($path, '/small-'))->once()->andReturnFalse();
    Storage::shouldReceive('disk')->with('public')->andReturn($failingDisk);

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->put(route('admin.posts.update', $post), articleInput(['image' => UploadedFile::fake()->image('replacement.jpg', 1000, 500)]))->assertServerError();

    expect($post->fresh()->image)->toBe($oldPath);
    expect($disk->allFiles('posts'))->toBe($oldFiles);
});

it('browses published stories by tags and preserves the tag when searching', function () {
    Post::factory()->create(['title' => 'Robots at work', 'is_published' => true, 'tags' => ['Robotics']]);
    Post::factory()->create(['title' => 'Painting with machines', 'is_published' => true, 'tags' => ['Creativity']]);
    Post::factory()->create(['tags' => ['Private topic']]);
    Post::factory()->create(['post_date' => today()->addDay(), 'is_published' => true, 'tags' => ['Future topic']]);

    $this->get('/')->assertSee('Robotics')->assertSee('Creativity')->assertDontSee('Private topic')->assertDontSee('Future topic');
    $this->get('/?tag=Robotics&q=Robots')->assertSee('Robots at work')->assertDontSee('Painting with machines')
        ->assertSee('name="tag" value="Robotics"', false);
    $this->get('/?tag=Missing')->assertSee('No stories found.')->assertDontSee('Robots at work');
    $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/admin/posts/create')
        ->assertSee('name="tags"', false)->assertDontSee('name="category"', false);
});

it('preserves category labels as tags before removing the database column', function () {
    $first = Post::factory()->create(['tags' => ['AI']]);
    $duplicate = Post::factory()->create(['tags' => ['research']]);
    $untagged = Post::factory()->create(['tags' => null]);
    $migration = require database_path('migrations/2026_09_14_182029_remove_category_from_posts_table.php');
    $migration->down();
    DB::table('posts')->where('id', $first->id)->update(['category' => 'Creativity']);
    DB::table('posts')->where('id', $duplicate->id)->update(['category' => 'Research']);
    DB::table('posts')->where('id', $untagged->id)->update(['category' => 'News']);

    $migration->up();

    expect(Schema::hasColumn('posts', 'category'))->toBeFalse();
    expect($first->fresh()->tags)->toBe(['AI', 'Creativity']);
    expect($duplicate->fresh()->tags)->toBe(['research']);
    expect($untagged->fresh()->tags)->toBe(['News']);
});
