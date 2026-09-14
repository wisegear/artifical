@extends('layouts.app')
@section('title', 'Edit About page — Artificially Curious')
@section('content')
<section class="admin">
    <a class="text-link" href="{{ route('admin.posts.index') }}">← Back to admin</a>
    <div class="section-heading">
        <div><div class="eyebrow">ABOUT THE JOURNAL</div><h1>Edit About page<span class="mint">.</span></h1></div>
        <a class="text-link" href="{{ route('about') }}" target="_blank" rel="noopener">View About page ↗</a>
    </div>
    <form method="post" action="{{ route('admin.about.update') }}" data-editor-form>
        @csrf
        @method('PUT')
        <div class="panel editor-main">
            <label for="title">Page title</label>
            <input id="title" name="title" value="{{ old('title', $page->title) }}" maxlength="255" required>
            <label for="description">Introduction</label>
            <textarea id="description" name="description" rows="3" maxlength="255" required>{{ old('description', $page->description) }}</textarea>
            <small>Shown below the title and used as the search engine description.</small>
            <label for="body">About text</label>
            <textarea id="body" name="body" data-editor data-upload-url="{{ route('admin.images.store') }}">{{ old('body', $page->body) }}</textarea>
            <small id="editor-status" role="status">Rich text editor loading…</small>
        </div>
        <p class="muted">Saving updates the public About page immediately.</p>
        <button class="button" type="submit">Save About page ↗</button>
    </form>
</section>
@endsection
