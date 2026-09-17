@extends('layouts.app')
@section('title', ($navigationItem->exists ? 'Edit' : 'Create').' navigation item — Artificially Curious')
@section('content')
<section class="admin">
    <a class="text-link" href="{{ route('admin.navigation-items.index') }}">← Back to navigation</a>
    <div class="section-heading"><div><div class="eyebrow">SITE NAVIGATION</div><h1>{{ $navigationItem->exists ? 'Edit' : 'Create' }} navigation item<span class="mint">.</span></h1></div></div>
    <form method="post" action="{{ $navigationItem->exists ? route('admin.navigation-items.update', $navigationItem) : route('admin.navigation-items.store') }}">
        @csrf
        @if($navigationItem->exists) @method('PUT') @endif
        <div class="panel editor-main">
            <label for="label">Navigation item name</label>
            <input id="label" name="label" value="{{ old('label', $navigationItem->label) }}" maxlength="80" required>
            <small>This is the dropdown name visitors will see in the header.</small>
            <fieldset class="post-selector">
                <legend>Select posts</legend>
                <small id="post-order-help">Select posts, then use Move up and Move down to set their menu order. Save to apply your changes.</small>
                @php
                    $postOrder = array_flip(old('posts', $selectedPosts));
                    $orderedPosts = $posts->sortBy(fn ($post) => $postOrder[$post->id] ?? PHP_INT_MAX);
                @endphp
                <div class="post-options" data-post-order aria-describedby="post-order-help">
                    @foreach($orderedPosts as $post)
                        <div class="post-option" data-post-option>
                            <label><input type="checkbox" name="posts[]" value="{{ $post->id }}" @checked(isset($postOrder[$post->id]))><span><strong>{{ $post->title }}</strong><small>{{ $post->is_published && $post->post_date->lte(today()) ? 'Published' : ($post->is_published ? 'Scheduled' : 'Draft') }} · {{ $post->post_date->format('d/m/Y') }}</small></span></label>
                            <div class="post-order-actions" hidden>
                                <button type="button" data-move="up" aria-label="Move {{ $post->title }} up">↑ Move up</button>
                                <button type="button" data-move="down" aria-label="Move {{ $post->title }} down">↓ Move down</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </fieldset>
            @if($posts->isEmpty())<p class="muted">Create a post before adding a navigation item.</p>@endif
        </div>
        <button class="button" type="submit" @disabled($posts->isEmpty())>{{ $navigationItem->exists ? 'Save navigation item' : 'Create navigation item' }} ↗</button>
    </form>
</section>
@endsection
