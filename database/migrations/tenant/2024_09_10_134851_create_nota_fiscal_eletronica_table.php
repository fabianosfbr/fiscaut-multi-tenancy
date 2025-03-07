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
        Schema::create('notas_fiscais_eletronica', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('chave_acesso')->unique()->index();
            $table->string('numero');
            $table->string('serie');
            $table->datetime('data_emissao');
            $table->datetime('data_entrada')->nullable();
            $table->string('cnpj_emitente')->index();
            $table->string('nome_emitente');
            $table->string('cnpj_destinatario')->index();
            $table->string('nome_destinatario');
            $table->decimal('valor_total', 15, 2);
            $table->decimal('valor_produtos', 15, 2);
            $table->decimal('valor_base_icms', 15, 2)->default(0);
            $table->decimal('valor_icms', 15, 2)->default(0);
            $table->decimal('valor_icms_desonerado', 15, 2)->default(0);
            $table->decimal('valor_fcp', 15, 2)->default(0);
            $table->decimal('valor_base_icms_st', 15, 2)->default(0);
            $table->decimal('valor_icms_st', 15, 2)->default(0);
            $table->decimal('valor_fcp_st', 15, 2)->default(0);
            $table->decimal('valor_base_ipi', 15, 2)->default(0);
            $table->decimal('valor_ipi', 15, 2)->default(0);
            $table->decimal('valor_base_pis', 15, 2)->default(0);
            $table->decimal('valor_pis', 15, 2)->default(0);
            $table->decimal('valor_base_cofins', 15, 2)->default(0);
            $table->decimal('valor_cofins', 15, 2)->default(0);
            $table->decimal('valor_aproximado_tributos', 15, 2)->default(0);
            $table->string('status_nota');
            $table->string('status_manifestacao')->default('PENDENTE');
            $table->string('origem')->default('IMPORTADO');
            $table->longText('xml_content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas_fiscais_eletronica');
    }
};
