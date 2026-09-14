<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Artificially Curious — Exploring the intelligence age')</title>
<meta name="description" content="@yield('description', 'Ideas, experiments and perspectives from the evolving world of artificial intelligence.')">
@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body><a href="#main" class="skip-link">Skip to content</a>
<header class="site-header"><a class="brand" href="{{ route('home') }}"><span class="brand-icon">✳</span><span>artificially<span class="brand-light">curious</span><span class="mint">.</span></span></a>
<button class="menu-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="main-navigation" hidden><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path class="menu-icon-open" d="M4 6h16M4 12h16M4 18h16"/><path class="menu-icon-close" d="m6 6 12 12M6 18 18 6"/></svg></button>
<nav id="main-navigation" aria-label="Main navigation"><a href="{{ route('home') }}">The journal</a><a href="{{ route('about') }}">About</a>@auth @can('admin')<a href="{{ route('admin.posts.index') }}">Admin</a>@endcan<form action="{{ route('logout') }}" method="post">@csrf<button class="nav-link">Log out</button></form>@else<a href="{{ route('login') }}">Log in</a><a class="button small" href="{{ route('register') }}">Stay curious <span>↗</span></a>@endauth</nav></header>
<main id="main" class="shell">@if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="errors" role="alert"><strong>Please check the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')</main><footer class="site-footer"><a class="brand" href="{{ route('home') }}">artificially curious<span class="mint">.</span></a><span>Human curiosity. Artificial intelligence.</span><span>© {{ date('Y') }}</span></footer>@stack('scripts')</body></html>
