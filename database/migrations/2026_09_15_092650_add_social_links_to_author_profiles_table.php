<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('author_profiles', function (Blueprint $table): void {
            $table->string('x_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('linkedin_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('author_profiles', function (Blueprint $table): void {
            $table->dropColumn(['x_url', 'facebook_url', 'linkedin_url']);
        });
    }
};
