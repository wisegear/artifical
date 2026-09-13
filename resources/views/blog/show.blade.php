@extends('layouts.app')
@section('title', $post->title.' — Artificially Curious')
@section('description', $post->seo_summary)
@section('content')
<article class="article"><a class="text-link" href="{{ route('home') }}#journal">← Back to the journal</a><header class="article-header"><div class="eyebrow">{{ $post->category }} @if($post->is_subscriber) · MEMBERS ONLY @endif</div><h1>{{ $post->title }}</h1><p class="standfirst">{{ $post->seo_summary }}</p><div class="card-meta"><time datetime="{{ $post->post_date->toDateString() }}">{{ $post->post_date->format('d/m/Y') }}</time><span>Artificially Curious</span></div></header>
@if($post->image)<img class="article-cover" src="{{ Storage::disk('public')->url($post->image) }}" alt="{{ $post->title }}">@endif
<div class="article-body">@if($locked)<p>{{ Str::limit(html_entity_decode(strip_tags($post->body)), min(350, (int) floor(mb_strlen(html_entity_decode(strip_tags($post->body))) * .2))) }}</p><div class="paywall"><div class="eyebrow">KEEP YOUR CURIOSITY GOING</div><h2>The rest is for our readers.</h2><p>Join for free to unlock the full story and every members-only article.</p><a class="button" href="{{ route('register') }}">Create your account ↗</a><p>Already curious? <a href="{{ route('login') }}">Log in</a></p></div>@else{!! $post->body !!}@endif
<div class="tags">@foreach($post->tags??[] as $tag)<a href="{{ route('home',['tag'=>$tag]) }}#journal">#{{ $tag }}</a>@endforeach</div></div></article>
@endsection
