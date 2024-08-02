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
        Schema::create('category_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->integer('order')->default(1);
            $table->string('name', 100);
            $table->string('color', 50);
            $table->boolean('is_difal')->default(false);
            $table->integer('grupo')->nullable();
            $table->integer('conta_contabil')->nullable();
            $table->boolean('is_enable')->default(true);
            $table->boolean('is_devolucao')->default(false);
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_tags');
    }
};
