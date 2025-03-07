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
        Schema::create('conhecimentos_transportes_eletronico', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('chave_acesso', 44)->unique();
            $table->string('numero', 20);
            $table->string('serie', 3);
            $table->datetime('data_emissao');
            $table->datetime('data_entrada')->nullable();
            
            // Emitente
            $table->string('cnpj_emitente', 14);
            $table->string('nome_emitente');
            $table->string('ie_emitente')->nullable();
            
            // Destinatário
            $table->string('cnpj_destinatario', 14);
            $table->string('nome_destinatario');
            $table->string('ie_destinatario')->nullable();

            // Remetente
            $table->string('cnpj_remetente', 14);
            $table->string('nome_remetente');
            $table->string('ie_remetente')->nullable();

            // Valores
            $table->decimal('valor_total', 15, 2);
            $table->decimal('valor_receber', 15, 2);
            $table->decimal('valor_servico', 15, 2);
            $table->decimal('valor_icms', 15, 2)->nullable();
            $table->decimal('base_calculo_icms', 15, 2)->nullable();
            $table->decimal('aliquota_icms', 5, 2)->nullable();

            // Informações do Transporte
            $table->string('modal')->comment('01-Rodoviário, 02-Aéreo, 03-Aquaviário, 04-Ferroviário, 05-Dutoviário, 06-Multimodal');
            $table->string('tipo_servico')->comment('0-Normal, 1-Subcontratação, 2-Redespacho, 3-Redespacho Intermediário, 4-Serviço Vinculado a Multimodal');
            $table->integer('quantidade_carga')->nullable();
            $table->decimal('peso_bruto', 15, 3)->nullable();
            $table->decimal('peso_base_calculo', 15, 3)->nullable();
            $table->decimal('peso_aferido', 15, 3)->nullable();
            $table->string('unidade_medida')->nullable();

            // Status e Controle
            $table->string('status_cte');
            $table->string('status_manifestacao')->default('PENDENTE');
            $table->string('origem')->default('IMPORTADO');
            $table->longText('xml_content');

            $table->index('cnpj_emitente');
            $table->index('cnpj_destinatario');
            $table->index('cnpj_remetente');
            $table->index('data_emissao');
            $table->index('status_cte');
            $table->index('status_manifestacao');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conhecimentos_transportes_eletronico');
    }
};
