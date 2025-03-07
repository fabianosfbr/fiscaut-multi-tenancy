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
        Schema::create('nota_fiscal_eletronica_itens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('nfe_id')
                ->constrained('notas_fiscais_eletronica')
                ->cascadeOnDelete();

            // Informações do produto
            $table->integer('numero_item');
            $table->string('codigo');
            $table->string('codigo_barras')->nullable();
            $table->text('descricao');
            $table->string('ncm');
            $table->string('cest')->nullable();
            $table->string('cfop');
            $table->string('unidade');
            $table->decimal('quantidade', 15, 4);
            $table->decimal('valor_unitario', 15, 4);
            $table->decimal('valor_total', 15, 2);
            $table->decimal('valor_desconto', 15, 2)->default(0);
            $table->decimal('valor_frete', 15, 2)->default(0);
            $table->decimal('valor_seguro', 15, 2)->default(0);
            $table->decimal('valor_outras_despesas', 15, 2)->default(0);

            // Informações do ICMS
            $table->string('origem')->nullable();
            $table->string('cst_icms')->nullable();
            $table->decimal('base_calculo_icms', 15, 2)->default(0);
            $table->decimal('aliquota_icms', 15, 2)->default(0);
            $table->decimal('valor_icms', 15, 2)->default(0);
            $table->decimal('base_calculo_icms_st', 15, 2)->default(0);
            $table->decimal('aliquota_icms_st', 15, 2)->default(0);
            $table->decimal('valor_icms_st', 15, 2)->default(0);

            // Informações do IPI
            $table->string('cst_ipi')->nullable();
            $table->decimal('base_calculo_ipi', 15, 2)->default(0);
            $table->decimal('aliquota_ipi', 15, 2)->default(0);
            $table->decimal('valor_ipi', 15, 2)->default(0);

            // Informações do PIS
            $table->string('cst_pis')->nullable();
            $table->decimal('base_calculo_pis', 15, 2)->default(0);
            $table->decimal('aliquota_pis', 15, 2)->default(0);
            $table->decimal('valor_pis', 15, 2)->default(0);

            // Informações do COFINS
            $table->string('cst_cofins')->nullable();
            $table->decimal('base_calculo_cofins', 15, 2)->default(0);
            $table->decimal('aliquota_cofins', 15, 2)->default(0);
            $table->decimal('valor_cofins', 15, 2)->default(0);

            // Informações adicionais
            $table->text('informacoes_adicionais')->nullable();

            $table->timestamps();

            // Índices
            $table->index('ncm');
            $table->index('cfop');
            $table->index('codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nota_fiscal_eletronica_itens');
    }
};
