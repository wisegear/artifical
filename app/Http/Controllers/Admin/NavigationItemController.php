<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NavigationItemRequest;
use App\Models\NavigationItem;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NavigationItemController extends Controller
{
    public function index(): View
    {
        return view('admin.navigation-index', [
            'navigationItems' => NavigationItem::withCount('posts')->orderBy('position')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new NavigationItem);
    }

    public function store(NavigationItemRequest $request): RedirectResponse
    {
        $navigationItem = DB::transaction(function () use ($request): NavigationItem {
            $navigationItem = NavigationItem::create([
                'label' => $request->validated('label'),
                'position' => ((int) NavigationItem::max('position')) + 1,
            ]);
            $this->syncPosts($navigationItem, $request->validated('posts'));

            return $navigationItem;
        });

        return redirect()->route('admin.navigation-items.edit', $navigationItem)->with('status', 'Navigation item created.');
    }

    public function edit(NavigationItem $navigationItem): View
    {
        return $this->form($navigationItem);
    }

    public function update(NavigationItemRequest $request, NavigationItem $navigationItem): RedirectResponse
    {
        DB::transaction(function () use ($request, $navigationItem): void {
            $navigationItem->update(['label' => $request->validated('label')]);
            $this->syncPosts($navigationItem, $request->validated('posts'));
        });

        return redirect()->route('admin.navigation-items.edit', $navigationItem)->with('status', 'Navigation item saved.');
    }

    public function destroy(NavigationItem $navigationItem): RedirectResponse
    {
        $navigationItem->delete();

        return redirect()->route('admin.navigation-items.index')->with('status', 'Navigation item deleted.');
    }

    private function form(NavigationItem $navigationItem): View
    {
        $navigationItem->load('posts');

        return view('admin.navigation-form', [
            'navigationItem' => $navigationItem,
            'posts' => Post::orderByDesc('post_date')->orderBy('title')->get(),
            'selectedPosts' => $navigationItem->posts->modelKeys(),
        ]);
    }

    /** @param list<int|string> $postIds */
    private function syncPosts(NavigationItem $navigationItem, array $postIds): void
    {
        $posts = collect($postIds)->values()->mapWithKeys(
            fn (int|string $postId, int $position): array => [(int) $postId => ['position' => $position]],
        );

        $navigationItem->posts()->sync($posts);
    }
}
