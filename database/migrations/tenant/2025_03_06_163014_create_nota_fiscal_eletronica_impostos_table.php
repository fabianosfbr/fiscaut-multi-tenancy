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
        Schema::create('nota_fiscal_eletronica_impostos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('nfe_id')
                ->constrained('notas_fiscais_eletronica')
                ->cascadeOnDelete();

            // ICMS
            $table->decimal('base_calculo_icms', 15, 2)->default(0);
            $table->decimal('valor_icms', 15, 2)->default(0);
            $table->decimal('valor_icms_desonerado', 15, 2)->default(0);
            $table->decimal('valor_icms_fcp', 15, 2)->default(0);

            // ICMS ST
            $table->decimal('base_calculo_icms_st', 15, 2)->default(0);
            $table->decimal('valor_icms_st', 15, 2)->default(0);
            $table->decimal('valor_icms_st_fcp', 15, 2)->default(0);

            // IPI
            $table->decimal('base_calculo_ipi', 15, 2)->default(0);
            $table->decimal('valor_ipi', 15, 2)->default(0);

            // PIS
            $table->decimal('base_calculo_pis', 15, 2)->default(0);
            $table->decimal('valor_pis', 15, 2)->default(0);

            // COFINS
            $table->decimal('base_calculo_cofins', 15, 2)->default(0);
            $table->decimal('valor_cofins', 15, 2)->default(0);

            // Outros valores
            $table->decimal('valor_aproximado_tributos', 15, 2)->default(0);
            $table->decimal('valor_ii', 15, 2)->default(0); // Imposto de Importação
            $table->decimal('valor_issqn', 15, 2)->default(0);

            // Totalizadores
            $table->decimal('valor_total_tributos', 15, 2)
                ->storedAs('valor_icms + valor_icms_st + valor_ipi + valor_pis + valor_cofins + valor_icms_fcp + valor_icms_st_fcp + valor_ii + valor_issqn')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nota_fiscal_eletronica_impostos');
    }
};
