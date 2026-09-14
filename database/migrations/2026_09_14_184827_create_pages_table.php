<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('description');
            $table->text('body');
            $table->timestamps();
        });

        DB::table('pages')->insert([
            'slug' => 'about',
            'title' => 'A little artificial. A lot of curiosity.',
            'description' => 'An independent journal exploring artificial intelligence through human curiosity.',
            'body' => '<h2>Making sense of a world thinking differently.</h2><p>Artificially Curious is a space for ideas, experiments and honest perspectives on artificial intelligence.</p><p>Here, curiosity comes first. What can these tools help us do? Where do they fall short? And what does it all mean for the way we create, work and learn?</p><h2>For humans who ask what’s next.</h2><p>You don’t need to be an expert to join the conversation. Just bring an open mind, a few questions and a willingness to explore.</p>',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
