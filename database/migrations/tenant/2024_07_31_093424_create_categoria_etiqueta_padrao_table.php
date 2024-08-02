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
        Schema::create('categoria_etiqueta_padrao', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('organization_id');
            $table->integer('order');
            $table->string('name', 100);
            $table->string('color', 100);
            $table->integer('grupo')->nullable();
            $table->integer('conta_contabil')->nullable();
            $table->boolean('is_enable')->default(true);
            $table->boolean('is_difal')->default(false);
            $table->boolean('is_devolucao')->default(false);


            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categoria_etiqueta_padrao');
    }
};
