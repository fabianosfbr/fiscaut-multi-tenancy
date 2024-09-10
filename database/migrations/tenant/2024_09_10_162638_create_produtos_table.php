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
        Schema::create('produtos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('nfe_id')->references('id')->on('notas_fiscais_eletronica');
            $table->string('num_nfe', 255)->nullable();
            $table->string('codigo_produto', 255);
            $table->string('descricao_produto', 255);
            $table->string('cst_icms', 255)->nullable();
            $table->string('ncm', 255);
            $table->integer('cfop');
            $table->string('unidade', 255);
            $table->decimal('quantidade', 14, 4);
            $table->decimal('valor_unit', 14, 4);
            $table->decimal('valor_total', 14, 4);
            $table->decimal('valor_desc', 14, 4)->nullable();
            $table->decimal('base_icms', 14, 4)->nullable();
            $table->decimal('valor_icms', 14, 4)->nullable();
            $table->decimal('aliq_icms', 14, 4)->nullable();
            $table->decimal('base_ipi', 14, 4)->nullable();
            $table->decimal('valor_ipi', 14, 4)->nullable();
            $table->decimal('aliq_ipi', 14, 4)->nullable();
            $table->string('cst_ipi', 255)->nullable();
            $table->decimal('base_pis', 14, 4)->nullable();
            $table->decimal('valor_pis', 14, 4)->nullable();
            $table->decimal('aliq_pis', 14, 4)->nullable();
            $table->string('cst_pis', 255)->nullable();
            $table->decimal('base_cofins', 14, 4)->nullable();
            $table->decimal('valor_cofins', 14, 4)->nullable();
            $table->decimal('aliq_cofins', 14, 4)->nullable();
            $table->string('cst_cofins', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
