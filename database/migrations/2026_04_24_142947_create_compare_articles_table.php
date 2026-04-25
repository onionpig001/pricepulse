<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compare_articles', function (Blueprint $table) {
            $table->id();
            $table->string('pair_slug')->unique();
            $table->foreignId('product_a_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_b_id')->constrained('products')->cascadeOnDelete();
            $table->string('title');
            $table->text('tldr')->nullable();
            $table->longText('body_md');
            $table->timestamp('last_regenerated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compare_articles');
    }
};
