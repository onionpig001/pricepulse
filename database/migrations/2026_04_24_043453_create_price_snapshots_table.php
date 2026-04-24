<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->longText('html_raw')->nullable();
            $table->json('parsed_tiers');
            $table->json('diff_vs_previous')->nullable();
            $table->string('source_url');
            $table->string('http_status')->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();
            $table->index(['product_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_snapshots');
    }
};
