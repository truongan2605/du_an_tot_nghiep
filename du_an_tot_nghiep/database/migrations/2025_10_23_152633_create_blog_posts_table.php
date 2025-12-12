<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
            $t->string('title');
            $t->string('slug')->unique();
            $t->string('cover_image')->nullable();
            $t->string('excerpt', 500)->nullable();
            $t->longText('content')->nullable();
            $t->enum('status', ['draft','published'])->default('draft');
            $t->timestamp('published_at')->nullable();
            $t->unsignedBigInteger('views')->default(0);
            $t->string('meta_title')->nullable();
            $t->string('meta_description', 160)->nullable();
            $t->timestamps();
            $t->softDeletes();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
