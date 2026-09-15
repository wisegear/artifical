@extends('layouts.app')
@section('title', 'Navigation — Artificially Curious')
@section('content')
<section class="admin">
    <a class="text-link" href="{{ route('admin.posts.index') }}">← Back to admin</a>
    <div class="section-heading">
        <div><div class="eyebrow">SITE NAVIGATION</div><h1>Navigation items<span class="mint">.</span></h1></div>
        <a class="button" href="{{ route('admin.navigation-items.create') }}">New navigation item +</a>
    </div>
    <div class="table-wrap"><table><thead><tr><th>Navigation item</th><th>Posts</th><th>Actions</th></tr></thead><tbody>
    @forelse($navigationItems as $navigationItem)<tr><td><a class="post-title" href="{{ route('admin.navigation-items.edit', $navigationItem) }}">{{ $navigationItem->label }}</a></td><td>{{ $navigationItem->posts_count }}</td><td><div class="actions"><a href="{{ route('admin.navigation-items.edit', $navigationItem) }}">Edit ↗</a><form method="post" action="{{ route('admin.navigation-items.destroy', $navigationItem) }}" data-confirm="Delete this navigation item?">@csrf @method('DELETE')<button class="danger">Delete</button></form></div></td></tr>
    @empty<tr><td colspan="3"><div class="empty-state"><h3>No custom navigation yet.</h3><p>Create an item and select the posts that should appear beneath it.</p></div></td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
