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
        Schema::create('contabil_parametros_gerais', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id')->index();
            $table->integer('order')->nullable();
            $table->json('codigo');
            $table->json('descricao');
            $table->json('params')->nullable();
            $table->integer('conta_contabil');
            $table->integer('codigo_historico');
            $table->json('descricao_conta_contabil');
            $table->json('descricao_historico')->nullable();
            $table->boolean('is_inclusivo')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contabil_parametros_gerais');
    }
};
