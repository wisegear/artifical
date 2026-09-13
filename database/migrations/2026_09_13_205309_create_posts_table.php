<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->date('post_date');
            $table->string('seo_summary', 255);
            $table->string('category', 80);
            $table->json('tags')->nullable();
            $table->text('body');
            $table->string('image')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_subscriber')->default(false);
            $table->timestamps();
            $table->index(['is_published', 'post_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
