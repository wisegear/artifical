<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['title', 'slug', 'post_date', 'seo_summary', 'tags', 'body', 'image', 'is_published', 'is_featured', 'is_subscriber'])]
class Post extends Model
{
    use HasFactory;

    public static function uniqueSlug(string $title): string
    {
        $baseSlug = Str::substr(Str::slug($title) ?: 'post', 0, 240);
        $slug = $baseSlug;
        $suffix = 2;

        while (static::where('slug', $slug)->orWhere('previous_slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }

    public function resolveRouteBinding(mixed $value, mixed $field = null): ?Model
    {
        $post = parent::resolveRouteBinding($value, $field);

        return $post ?? ($field === 'slug' ? static::where('previous_slug', $value)->first() : null);
    }

    protected function casts(): array
    {
        return ['post_date' => 'date', 'tags' => 'array', 'is_published' => 'boolean', 'is_featured' => 'boolean', 'is_subscriber' => 'boolean'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function navigationItems(): BelongsToMany
    {
        return $this->belongsToMany(NavigationItem::class)->withPivot('position');
    }

    protected function readingTimeMinutes(): Attribute
    {
        return Attribute::get(function (): int {
            $plainText = html_entity_decode(strip_tags($this->body ?? ''));
            $wordCount = preg_match_all('/[\p{L}\p{N}]+(?:[\x{2019}\x{0027}-][\p{L}\p{N}]+)*/u', $plainText);

            return max(1, (int) ceil($wordCount / 200));
        });
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereDate('post_date', '<=', today());
    }
}
