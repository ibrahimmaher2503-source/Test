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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('barcode')->unique()->nullable();
            $table->enum('barcode_source', ['existing', 'generated']);
            $table->string('name_en');
            $table->string('name_ar');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit');
            $table->enum('status', ['active', 'pending_review', 'inactive'])->default('active');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
