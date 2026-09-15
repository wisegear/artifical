<?php

use App\Models\NavigationItem;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows admins to create a dropdown from selected posts', function () {
    $published = Post::factory()->create(['title' => 'Practical AI', 'is_published' => true]);
    $draft = Post::factory()->create(['title' => 'Coming Soon']);
    Post::factory()->create(['title' => 'Another Published Story', 'is_published' => true]);
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get(route('admin.posts.index'))->assertSee('Navigation');
    $this->post(route('admin.navigation-items.store'), [
        'label' => 'Start Here',
        'posts' => [$published->id, $draft->id],
    ])->assertSessionHasNoErrors()->assertRedirect()->assertSessionHas('status', 'Navigation item created.');

    $navigationItem = NavigationItem::firstOrFail();
    expect($navigationItem->label)->toBe('Start Here');
    $this->assertDatabaseHas('navigation_item_post', ['navigation_item_id' => $navigationItem->id, 'post_id' => $published->id, 'position' => 0]);
    $this->assertDatabaseHas('navigation_item_post', ['navigation_item_id' => $navigationItem->id, 'post_id' => $draft->id, 'position' => 1]);
    $response = $this->get('/');
    $navigation = str($response->getContent())->between('<nav id="main-navigation"', '</nav>')->toString();
    expect($navigation)
        ->toContain('Start Here')
        ->toContain($published->title)
        ->not->toContain($draft->title)
        ->not->toContain('Another Published Story');
});

it('allows admins to rename update and delete navigation items', function () {
    $firstPost = Post::factory()->create(['is_published' => true]);
    $secondPost = Post::factory()->create(['is_published' => true]);
    $navigationItem = NavigationItem::factory()->create(['label' => 'Old name']);
    $navigationItem->posts()->attach($firstPost, ['position' => 0]);
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->put(route('admin.navigation-items.update', $navigationItem), [
        'label' => 'Selected reading',
        'posts' => [$secondPost->id],
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.navigation-items.edit', $navigationItem));

    expect($navigationItem->fresh()->label)->toBe('Selected reading');
    expect($navigationItem->posts()->pluck('posts.id')->all())->toBe([$secondPost->id]);
    $this->delete(route('admin.navigation-items.destroy', $navigationItem))
        ->assertRedirect(route('admin.navigation-items.index'))
        ->assertSessionHas('status', 'Navigation item deleted.');
    $this->assertModelMissing($navigationItem);
    $this->assertDatabaseCount('navigation_item_post', 0);
});

it('prevents guests and readers from managing navigation items', function () {
    $navigationItem = NavigationItem::factory()->create();
    $payload = ['label' => 'Private change', 'posts' => [Post::factory()->create()->id]];

    $this->get(route('admin.navigation-items.index'))->assertRedirect(route('login'));
    $this->post(route('admin.navigation-items.store'), $payload)->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create());
    $this->get(route('admin.navigation-items.edit', $navigationItem))->assertForbidden();
    $this->put(route('admin.navigation-items.update', $navigationItem), $payload)->assertForbidden();
    $this->delete(route('admin.navigation-items.destroy', $navigationItem))->assertForbidden();
    expect($navigationItem->fresh()->label)->not->toBe('Private change');
});

it('requires a name and at least one valid distinct post', function () {
    $post = Post::factory()->create();
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->post(route('admin.navigation-items.store'), [
        'label' => '',
        'posts' => [$post->id, $post->id, 999999],
    ])->assertInvalid(['label', 'posts.1', 'posts.2']);
    $this->assertDatabaseCount('navigation_items', 0);
});
