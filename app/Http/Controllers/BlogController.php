<?php

namespace App\Http\Controllers;

use App\Models\AuthorProfile;
use App\Models\Post;
use App\Services\PostContents;
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

    public function show(Request $request, Post $post, PostContents $contents): View
    {
        abort_unless($post->is_published && $post->post_date->lte(today()), 404);

        return $this->renderPost($request, $post, $contents);
    }

    public function preview(Request $request, Post $post, PostContents $contents): View
    {
        return $this->renderPost($request, $post, $contents, preview: true);
    }

    private function renderPost(Request $request, Post $post, PostContents $contents, bool $preview = false): View
    {
        $locked = ! $preview && $post->is_subscriber && ! $request->user();
        $postContents = $locked ? ['html' => '', 'headings' => []] : $contents->build($post->body);

        $author = AuthorProfile::find(1);
        $postUrl = route('posts.show', $post);
        $shareLinks = [
            'x' => 'https://twitter.com/intent/tweet?'.http_build_query(['text' => $post->title, 'url' => $postUrl], encoding_type: PHP_QUERY_RFC3986),
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?'.http_build_query(['u' => $postUrl], encoding_type: PHP_QUERY_RFC3986),
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?'.http_build_query(['url' => $postUrl], encoding_type: PHP_QUERY_RFC3986),
        ];

        return view('blog.show', compact('post', 'locked', 'postContents', 'author', 'shareLinks', 'preview'));
    }
}
