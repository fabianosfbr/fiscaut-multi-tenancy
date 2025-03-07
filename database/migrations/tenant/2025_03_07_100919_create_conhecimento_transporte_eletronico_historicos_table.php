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
        Schema::create('conhecimento_transporte_eletronico_historicos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cte_id')->constrained('conhecimentos_transportes_eletronico')->cascadeOnDelete();
            $table->datetime('data_alteracao');
            $table->json('campos_alterados');
            $table->uuid('usuario_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conhecimento_transporte_eletronico_historicos');
    }
};
