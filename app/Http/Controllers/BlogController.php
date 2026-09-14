<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'tag' => ['nullable', 'string', 'max:100']]);
        $query = Post::published();
        if ($search = $filters['q'] ?? null) {
            $query->where(fn ($q) => $q->whereLike('title', '%'.$search.'%')->orWhereLike('seo_summary', '%'.$search.'%'));
        }
        if ($tag = $filters['tag'] ?? null) {
            $query->whereJsonContains('tags', $tag);
        }

        return view('blog.index', ['posts' => $query->orderByDesc('post_date')->paginate(9)->withQueryString(), 'featured' => empty(array_filter($filters)) ? Post::published()->where('is_featured', true)->latest('post_date')->first() : null, 'popularTags' => Post::published()->pluck('tags')->flatten()->filter()->countBy()->sortDesc()->keys()->take(8)]);
    }

    public function show(Request $request, Post $post): View
    {
        abort_unless($post->is_published && $post->post_date->lte(today()), 404);
        $locked = $post->is_subscriber && ! $request->user();

        return view('blog.show', compact('post', 'locked'));
    }
}
