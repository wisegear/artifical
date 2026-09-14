@extends('layouts.app')
@section('title', 'About — '.$page->title)
@section('description', $page->description)
@section('content')
<article class="article">
    <header class="article-header">
        <div class="eyebrow">ABOUT ARTIFICIALLY CURIOUS</div>
        <h1>{{ $page->title }}</h1>
        <p class="standfirst">{{ $page->description }}</p>
    </header>
    <div class="article-body">
        {!! $page->body !!}
        <a class="text-link" href="{{ route('home') }}#journal">Explore the journal ↗</a>
    </div>
</article>
@endsection
