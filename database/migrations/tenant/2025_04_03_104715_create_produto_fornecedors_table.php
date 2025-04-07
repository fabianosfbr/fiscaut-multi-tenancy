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
        Schema::create('produto_fornecedores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('cnpj')->index();
            $table->string('num_nfe')->index();
            $table->string('serie_nfe')->nullable();
            $table->string('external_id');
            $table->string('codigo_produto');
            $table->string('descricao_produto');
            $table->string('unidade_comercializada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produto_fornecedores');
    }
};
