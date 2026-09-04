<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Блог как в ocStore / OpenCart 3 (без префикса oc_):
 * article* → articles / article_descriptions
 * blog_category* → blog_categories / blog_category_descriptions
 * review_article → article_reviews
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('image', 255)->nullable();
            $table->date('date_available')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('reviews_enabled')->default(true);
            $table->boolean('status')->default(true);
            $table->boolean('noindex')->default(false);
            $table->unsignedInteger('viewed')->default(0);
            $table->timestamps();
        });

        Schema::create('article_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->longText('description')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_h1', 255)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->string('meta_keyword', 255)->nullable();
            $table->text('tag')->nullable();
            $table->timestamps();

            $table->unique(['article_id', 'language_id']);
            $table->unique(['language_id', 'slug']);
        });

        Schema::create('article_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->string('image', 255);
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('image', 255)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('blog_categories')->nullOnDelete();
            $table->boolean('top')->default(false);
            $table->unsignedInteger('column')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->boolean('noindex')->default(false);
            $table->timestamps();
        });

        Schema::create('blog_category_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_category_id')->constrained('blog_categories')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->text('description')->nullable();
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_h1', 255)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->string('meta_keyword', 255)->nullable();
            $table->timestamps();

            $table->unique(['blog_category_id', 'language_id']);
            $table->unique(['language_id', 'slug']);
        });

        Schema::create('blog_category_paths', function (Blueprint $table) {
            $table->foreignId('blog_category_id')->constrained('blog_categories')->cascadeOnDelete();
            $table->foreignId('path_id')->constrained('blog_categories')->cascadeOnDelete();
            $table->unsignedInteger('level');
            $table->primary(['blog_category_id', 'path_id']);
        });

        Schema::create('article_blog_category', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('blog_category_id')->constrained('blog_categories')->cascadeOnDelete();
            $table->boolean('main')->default(false);
            $table->primary(['article_id', 'blog_category_id']);
        });

        Schema::create('article_related', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->unsignedBigInteger('related_id');
            $table->primary(['article_id', 'related_id']);
            $table->foreign('related_id')->references('id')->on('articles')->cascadeOnDelete();
        });

        Schema::create('article_product', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->primary(['article_id', 'product_id']);
        });

        Schema::create('article_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->default(0);
            $table->string('author', 64);
            $table->text('text');
            $table->unsignedTinyInteger('rating')->default(5);
            $table->boolean('status')->default(false);
            $table->timestamps();
        });

        Schema::create('blog_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->text('value')->nullable();
        });

        $defaults = [
            'name' => 'Блог',
            'html_h1' => 'Блог',
            'meta_title' => 'Блог',
            'meta_description' => '',
            'meta_keyword' => '',
            'article_limit' => '20',
            'article_description_length' => '200',
            'blog_menu' => '1',
            'review_status' => '1',
            'review_guest' => '1',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('blog_settings')->insert(['key' => $key, 'value' => $value]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_settings');
        Schema::dropIfExists('article_reviews');
        Schema::dropIfExists('article_product');
        Schema::dropIfExists('article_related');
        Schema::dropIfExists('article_blog_category');
        Schema::dropIfExists('blog_category_paths');
        Schema::dropIfExists('blog_category_descriptions');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('article_images');
        Schema::dropIfExists('article_descriptions');
        Schema::dropIfExists('articles');
    }
};
