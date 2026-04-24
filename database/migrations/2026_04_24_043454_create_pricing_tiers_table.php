<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('snapshot_id')->nullable()->constrained('price_snapshots')->nullOnDelete();
            $table->string('name');
            $table->decimal('price_monthly_usd', 10, 2)->nullable();
            $table->decimal('price_annual_usd', 10, 2)->nullable();
            $table->string('billing_unit')->nullable();
            $table->boolean('is_free')->default(false);
            $table->boolean('is_custom_quote')->default(false);
            $table->json('limits')->nullable();
            $table->json('features')->nullable();
            $table->integer('tier_order')->default(0);
            $table->timestamp('captured_at');
            $table->timestamps();
            $table->index(['product_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_tiers');
    }
};
