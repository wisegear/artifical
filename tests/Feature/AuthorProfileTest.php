<?php

use App\Models\AuthorProfile;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('allows the admin to edit the single author profile and shows it beneath posts', function () {
    Storage::fake('public');
    $post = Post::factory()->create(['is_published' => true]);
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get(route('admin.posts.index'))->assertSee('Edit Author profile');
    $this->get(route('admin.author.edit'))->assertSee('Author biography');
    $this->put(route('admin.author.update'), [
        'name' => 'Alex Curious',
        'bio' => "I write about humans & machines.\nAlways asking why.",
        'image' => UploadedFile::fake()->image('portrait.jpg', 900, 1200),
        'x_url' => 'https://x.com/alexcurious',
        'facebook_url' => 'https://facebook.com/alexcurious',
        'linkedin_url' => 'https://linkedin.com/in/alexcurious',
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.author.edit'))->assertSessionHas('status', 'Author profile saved.');

    $profile = AuthorProfile::findOrFail(1);
    expect($profile->name)->toBe('Alex Curious');
    expect($profile->image)->toEndWith('.webp');
    expect($profile->x_url)->toBe('https://x.com/alexcurious');
    Storage::disk('public')->assertExists($profile->image);
    expect(getimagesize(Storage::disk('public')->path($profile->image)))->toMatchArray([600, 600]);
    $this->get(route('posts.show', $post->slug))
        ->assertSee('ABOUT THE AUTHOR')
        ->assertSee('Alex Curious')
        ->assertSee('I write about humans &amp; machines.', false)
        ->assertSee(Storage::disk('public')->url($profile->image))
        ->assertSee('href="https://x.com/alexcurious"', false)
        ->assertSee('href="https://facebook.com/alexcurious"', false)
        ->assertSee('href="https://linkedin.com/in/alexcurious"', false)
        ->assertSee('rel="me noopener noreferrer"', false);
});

it('replaces the previous author image when a new one is uploaded', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->put(route('admin.author.update'), [
        'name' => 'Alex Curious',
        'bio' => 'Writer and researcher.',
        'image' => UploadedFile::fake()->image('first.jpg'),
    ])->assertSessionHasNoErrors();
    $oldImage = AuthorProfile::findOrFail(1)->image;

    $this->put(route('admin.author.update'), [
        'name' => 'Alex Curious',
        'bio' => 'Writer and researcher.',
        'image' => UploadedFile::fake()->image('second.png'),
    ])->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing($oldImage);
    Storage::disk('public')->assertExists(AuthorProfile::findOrFail(1)->image);
    expect(Storage::disk('public')->allFiles('authors'))->toHaveCount(1);
});

it('prevents guests and readers from editing the author profile', function () {
    $this->get(route('admin.author.edit'))->assertRedirect(route('login'));
    $this->put(route('admin.author.update'), [])->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create());

    $this->get(route('admin.author.edit'))->assertForbidden();
    $this->put(route('admin.author.update'), ['name' => 'Changed', 'bio' => 'Changed'])->assertForbidden();
    expect(AuthorProfile::findOrFail(1)->name)->toBeNull();
});

it('rejects invalid author details and image uploads', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->put(route('admin.author.update'), [
        'name' => '',
        'bio' => '',
        'image' => UploadedFile::fake()->create('portrait.svg', 1, 'image/svg+xml'),
        'x_url' => 'javascript:alert(1)',
        'facebook_url' => 'not a URL',
        'linkedin_url' => 'ftp://example.com/profile',
    ])->assertInvalid(['name', 'bio', 'image', 'x_url', 'facebook_url', 'linkedin_url']);
    expect(AuthorProfile::findOrFail(1)->name)->toBeNull();
});

it('allows the admin to clear optional social accounts', function () {
    $profile = AuthorProfile::findOrFail(1);
    $profile->update([
        'name' => 'Alex Curious',
        'bio' => 'Writer and researcher.',
        'x_url' => 'https://x.com/alexcurious',
    ]);
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->put(route('admin.author.update'), [
        'name' => 'Alex Curious',
        'bio' => 'Writer and researcher.',
        'x_url' => '',
        'facebook_url' => '',
        'linkedin_url' => '',
    ])->assertSessionHasNoErrors();

    expect($profile->fresh()->x_url)->toBeNull();
});
