@props(['post'])
<article class="post-card"><a class="card-image" href="{{ route('posts.show',$post->slug) }}" tabindex="-1" aria-hidden="true">@if($post->image)<img src="{{ Storage::disk('public')->url($post->image) }}" alt="" loading="lazy">@else<x-art/>@endif @if($post->is_subscriber)<span class="image-badge">Members only ↗</span>@endif</a>
<div class="card-meta"><a href="{{ route('home',['category'=>$post->category]) }}">{{ $post->category }}</a><time datetime="{{ $post->post_date->toDateString() }}">{{ $post->post_date->format('d/m/Y') }}</time></div>
<h3><a href="{{ route('posts.show',$post->slug) }}">{{ $post->title }}</a></h3><p>{{ $post->seo_summary }}</p><a class="text-link" href="{{ route('posts.show',$post->slug) }}">Read the story <span>↗</span></a></article>
