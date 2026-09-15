@inject('images', 'App\Services\BlogImages')
@props(['post'])
<article class="post-card"><a class="card-image" href="{{ route('posts.show',$post->slug) }}" tabindex="-1" aria-hidden="true">@if($post->image)<img src="{{ $images->url($post->image, 'thumbnail') }}" alt="" loading="lazy">@else<x-art/>@endif @if($post->is_subscriber)<span class="image-badge">Members only ↗</span>@endif</a>
<div class="card-meta">@foreach($post->tags ?? [] as $tag)<a href="{{ route('home',['tag'=>$tag]) }}#journal">{{ $tag }}</a>@endforeach<time datetime="{{ $post->post_date->toDateString() }}">{{ $post->post_date->format('d/m/Y') }}</time><span>{{ $post->reading_time_minutes }} min read</span></div>
<h3><a href="{{ route('posts.show',$post->slug) }}">{{ $post->title }}</a></h3><p>{{ $post->seo_summary }}</p><a class="text-link" href="{{ route('posts.show',$post->slug) }}">Read the story <span>↗</span></a></article>
