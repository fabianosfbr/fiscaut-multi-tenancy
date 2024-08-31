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
        Schema::create('organizations', function (Blueprint $table) {

            $table->uuid('id')->primary();
            $table->string('razao_social');
            $table->string('cnpj')->unique()->nullable();
            $table->string('inscricao_estadual')->nullable();
            $table->string('inscricao_municipal')->nullable();
            $table->string('cod_municipio_ibge')->nullable();
            $table->string('regime')->nullable();
            $table->json('atividade')->nullable();
            $table->string('is_contribuinte_icms')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_enable_nfe_servico')->default(false);
            $table->boolean('is_enable_cte_servico')->default(false);
            $table->boolean('is_enable_nfse_servico')->default(false);
            $table->boolean('is_enable_cfe_servico')->default(false);
            $table->boolean('is_enable_sync_sieg')->default(false);
            $table->boolean('isNfeClassificarNaEntrada')->default(true);
            $table->boolean('isNfeManifestarAutomatica')->default(false);
            $table->boolean('isNfeClassificarSomenteManifestacao')->default(false);
            $table->boolean('isNfeMostrarEtiquetaComNomeAbreviado')->default(false);
            $table->boolean('isNfeTomaCreditoIcms')->default(false);
            $table->json('tagsCreditoIcms')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
