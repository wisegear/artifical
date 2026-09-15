@inject('images', 'App\Services\AuthorImages')
@extends('layouts.app')
@section('title', 'Edit Author profile — Artificially Curious')
@section('content')
<section class="admin">
    <a class="text-link" href="{{ route('admin.posts.index') }}">← Back to admin</a>
    <div class="section-heading">
        <div><div class="eyebrow">ABOUT THE AUTHOR</div><h1>Edit Author profile<span class="mint">.</span></h1></div>
    </div>
    <form method="post" action="{{ route('admin.author.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="panel editor-main">
            @if($profile->image)<img class="author-preview" src="{{ $images->url($profile->image) }}" alt="Current author photo">@endif
            <label for="image">Author photo</label>
            <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
            <small>Square images work best. Uploading a new photo replaces the current one.</small>
            <label for="name">Author name</label>
            <input id="name" name="name" value="{{ old('name', $profile->name) }}" maxlength="255" required>
            <label for="bio">Author biography</label>
            <textarea id="bio" name="bio" rows="7" maxlength="2000" required>{{ old('bio', $profile->bio) }}</textarea>
            <small>Shown beneath every published post.</small>
            <label for="x_url">X profile URL</label>
            <input id="x_url" type="url" name="x_url" value="{{ old('x_url', $profile->x_url) }}" maxlength="255" placeholder="https://x.com/yourname">
            <label for="facebook_url">Facebook profile URL</label>
            <input id="facebook_url" type="url" name="facebook_url" value="{{ old('facebook_url', $profile->facebook_url) }}" maxlength="255" placeholder="https://facebook.com/yourname">
            <label for="linkedin_url">LinkedIn profile URL</label>
            <input id="linkedin_url" type="url" name="linkedin_url" value="{{ old('linkedin_url', $profile->linkedin_url) }}" maxlength="255" placeholder="https://linkedin.com/in/yourname">
            <small>Optional. Leave an account blank to hide it from the author box.</small>
        </div>
        <button class="button" type="submit">Save Author profile ↗</button>
    </form>
</section>
@endsection
