<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('previous_slug')->nullable()->unique();
        });

        DB::table('posts')->orderBy('id')->chunkById(100, function ($posts): void {
            foreach ($posts as $post) {
                if (! preg_match('/^.+-[a-z0-9]{8}$/', $post->slug)) {
                    continue;
                }

                $baseSlug = Str::substr(Str::slug($post->title) ?: 'post', 0, 240);
                $slug = $baseSlug;
                $suffix = 2;
                while (DB::table('posts')->where('slug', $slug)->orWhere('previous_slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$suffix++;
                }

                DB::table('posts')->where('id', $post->id)->update([
                    'previous_slug' => $post->slug,
                    'slug' => $slug,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('posts')->whereNotNull('previous_slug')->update(['slug' => DB::raw('previous_slug')]);
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropUnique(['previous_slug']);
            $table->dropColumn('previous_slug');
        });
    }
};
