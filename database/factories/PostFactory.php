<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'title' => fake()->sentence(), 'slug' => Str::uuid()->toString(), 'post_date' => today(), 'seo_summary' => fake()->sentence(), 'tags' => ['AI'], 'body' => '<p>'.fake()->paragraph().'</p>', 'is_published' => false, 'is_featured' => false, 'is_subscriber' => false];
    }
}
