<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_items', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 80);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('navigation_item_post', function (Blueprint $table): void {
            $table->foreignId('navigation_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['navigation_item_id', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_item_post');
        Schema::dropIfExists('navigation_items');
    }
};
