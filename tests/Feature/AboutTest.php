<?php

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the starter about page and a public navigation link', function () {
    $this->get(route('about'))->assertOk()->assertSee('A little artificial. A lot of curiosity.')->assertSee('Making sense of a world thinking differently.');
    $this->get('/')->assertSee('href="'.route('about').'"', false);
});

it('allows admins to edit the about page and publishes sanitized changes', function () {
    $other = Page::factory()->create();
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->get(route('admin.posts.index'))->assertSee('Edit About page');
    $this->get(route('admin.about.edit'))->assertOk()->assertSee('data-editor', false);
    $this->put(route('admin.about.update'), [
        'title' => 'Our story', 'description' => 'Why we explore AI.',
        'body' => '<p><strong>Curiosity matters.</strong><script>alert(1)</script><img src="/storage/example.webp" onerror="alert(2)"></p>',
        'slug' => 'changed',
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.about.edit'))->assertSessionHas('status', 'About page saved.');

    $page = Page::where('slug', 'about')->firstOrFail();
    expect($page->title)->toBe('Our story');
    expect($page->body)->toContain('<strong>Curiosity matters.</strong>')->not->toContain('<script')->not->toContain('onerror');
    expect($other->fresh()->title)->toBe($other->title);
    $this->assertDatabaseCount('pages', 2);
    $this->get(route('about'))->assertSee('Our story')->assertSee('Why we explore AI.')->assertSee('<strong>Curiosity matters.</strong>', false)->assertDontSee('alert(1)', false);
});

it('prevents guests and readers from editing the about page', function () {
    $original = Page::where('slug', 'about')->firstOrFail()->body;
    $this->get(route('admin.about.edit'))->assertRedirect(route('login'));
    $this->put(route('admin.about.update'), [])->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create());
    $this->get(route('admin.about.edit'))->assertForbidden();
    $this->put(route('admin.about.update'), ['title' => 'Changed', 'description' => 'Changed', 'body' => 'Changed'])->assertForbidden();
    expect(Page::where('slug', 'about')->firstOrFail()->body)->toBe($original);
});

it('rejects missing and oversized about content without changing the page', function () {
    $original = Page::where('slug', 'about')->firstOrFail()->getAttributes();
    $this->actingAs(User::factory()->create(['is_admin' => true]));
    $this->put(route('admin.about.update'), [])->assertInvalid(['title', 'description', 'body']);
    $this->put(route('admin.about.update'), ['title' => str_repeat('a', 256), 'description' => str_repeat('a', 256), 'body' => str_repeat('a', 200001)])->assertInvalid(['title', 'description', 'body']);
    expect(Page::where('slug', 'about')->firstOrFail()->getAttributes())->toBe($original);
});
