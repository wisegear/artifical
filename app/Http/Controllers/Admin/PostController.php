<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Models\Post;
use App\Services\BlogImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class PostController extends Controller
{
    public function index(): View
    {
        return view('admin.index', ['posts' => Post::latest()->paginate(15), 'total' => Post::count(), 'published' => Post::published()->count(), 'drafts' => Post::where('is_published', false)->count()]);
    }

    public function create(): View
    {
        return view('admin.form', ['post' => new Post]);
    }

    public function edit(Post $post): View
    {
        return view('admin.form', compact('post'));
    }

    public function store(PostRequest $request, BlogImages $images): RedirectResponse
    {
        $post = new Post;
        $post->user_id = $request->user()->id;
        $post->slug = Str::slug($request->validated('title')).'-'.Str::lower(Str::random(8));

        return $this->save($request, $post, $images);
    }

    public function update(PostRequest $request, Post $post, BlogImages $images): RedirectResponse
    {
        return $this->save($request, $post, $images);
    }

    private function save(PostRequest $request, Post $post, BlogImages $images): RedirectResponse
    {
        $data = $request->safe()->only(['title', 'seo_summary']);
        $data['post_date'] = Carbon::createFromFormat('d/m/Y', $request->validated('post_date'))->toDateString();
        $data['tags'] = collect(explode(',', $request->validated('tags') ?? ''))->map(fn (string $tag) => trim($tag))->filter()->unique()->values()->all();
        $sanitizer = new HtmlSanitizer((new HtmlSanitizerConfig)->allowSafeElements()->allowRelativeMedias()->allowRelativeLinks()->allowElement('img', ['src', 'alt', 'width', 'height'])->withMaxInputLength(200000));
        $data['body'] = $sanitizer->sanitize($request->validated('body'));
        $data['is_published'] = $request->validated('action') === 'publish';
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_subscriber'] = $request->boolean('is_subscriber');
        $oldImage = $post->image;
        $newImage = null;
        if ($request->hasFile('image')) {
            $newImage = $images->store($request->file('image'));
            $data['image'] = $newImage;
        } elseif ($oldImage) {
            $images->generateVariants($oldImage);
        }
        try {
            $post->fill($data)->save();
        } catch (\Throwable $exception) {
            if ($newImage) {
                $images->delete($newImage);
            } throw $exception;
        }
        if ($newImage && $oldImage) {
            $images->delete($oldImage);
        }

        return redirect()->route('admin.posts.edit', $post)->with('status', $post->is_published ? 'Post saved for publication.' : 'Draft saved.');
    }

    public function destroy(Post $post, BlogImages $images): RedirectResponse
    {
        $image = $post->image;
        $post->delete();
        if ($image) {
            $images->delete($image);
        }

        return redirect()->route('admin.posts.index')->with('status', 'Post deleted.');
    }
}
