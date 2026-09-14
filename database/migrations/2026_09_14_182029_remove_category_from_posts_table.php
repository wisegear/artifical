<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('posts')->select('id', 'category', 'tags')->lazyById() as $post) {
            $tags = json_decode($post->tags ?? '[]', true, flags: JSON_THROW_ON_ERROR) ?? [];
            $category = trim($post->category);
            if ($category !== '' && ! in_array(mb_strtolower($category), array_map('mb_strtolower', $tags), true)) {
                $tags[] = $category;
                DB::table('posts')->where('id', $post->id)->update(['tags' => json_encode($tags, JSON_THROW_ON_ERROR)]);
            }
        }
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn('category');
        });
    }

    /**
     * Restore the column only; former categories remain in tags.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('category', 80)->default('');
        });
    }
};
