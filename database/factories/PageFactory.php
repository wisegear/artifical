<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    public function definition(): array
    {
        return ['slug' => Str::uuid()->toString(), 'title' => fake()->sentence(), 'description' => fake()->sentence(), 'body' => '<p>'.fake()->paragraph().'</p>'];
    }
}
