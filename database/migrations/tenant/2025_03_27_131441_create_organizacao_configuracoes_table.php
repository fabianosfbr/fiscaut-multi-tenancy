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
        Schema::create('organizacao_configuracoes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('tipo'); // geral, entrada, saida - limitado a 30 caracteres
            $table->string('subtipo')->nullable(); // impostos, cfops, acumuladores, etc
            $table->string('categoria')->nullable(); // nfe, cte, etc (para subtipos específicos)
            $table->boolean('ativo')->default(true);
            $table->json('configuracoes'); // armazena configurações específicas em JSON
            $table->timestamps();
            
            // Índice com nome curto
            $table->index('organization_id', 'org_id_idx');
            $table->index('tipo', 'tipo_idx');
            $table->index('subtipo', 'subtipo_idx');
            $table->index('categoria', 'categoria_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizacao_configuracoes');
    }
};
