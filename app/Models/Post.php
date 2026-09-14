<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'slug', 'post_date', 'seo_summary', 'tags', 'body', 'image', 'is_published', 'is_featured', 'is_subscriber'])]
class Post extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['post_date' => 'date', 'tags' => 'array', 'is_published' => 'boolean', 'is_featured' => 'boolean', 'is_subscriber' => 'boolean'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereDate('post_date', '<=', today());
    }
}
