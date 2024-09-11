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
        Schema::create('log_sefaz_resumo_nfes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('chave', 255)->index();
            $table->string('cnpj', 255);
            $table->string('razao_social', 255);
            $table->string('iscricao_estadual', 255)->nullable();
            $table->dateTime('dh_emissao');
            $table->integer('tipo_nfe');
            $table->decimal('valor_nfe', 10, 4);
            $table->boolean('is_ciente_operacao')->default(0);
            $table->boolean('downloaded')->default(0);
            $table->longText('xml');
            $table->dateTime('data_ciencia_manifesto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_sefaz_resumo_nfes');
    }
};
