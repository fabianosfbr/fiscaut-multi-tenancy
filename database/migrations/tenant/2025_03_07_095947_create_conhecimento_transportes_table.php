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
  
            // Identificação do CT-e
            $table->string('chave_acesso', 44)->unique();
            $table->string('numero', 20);
            $table->string('serie', 3);
            $table->datetime('data_emissao');
            
            // Dados da prestação
            $table->string('modal', 2)->comment('01-Rodoviário, 02-Aéreo, 03-Aquaviário, 04-Ferroviário, 05-Dutoviário, 06-Multimodal');
            $table->string('tpServ', 1)->comment('0-Normal, 1-Subcontratação, 2-Redespacho, 3-Redespacho Intermediário, 4-Serviço Vinculado a Multimodal');
            $table->string('cfop')->nullable();
            $table->string('natureza_operacao')->nullable();
            
            // Municípios e UF
            $table->string('cMunIni')->nullable()->comment('Código IBGE Município Início');
            $table->string('xMunIni')->nullable()->comment('Nome Município Início');
            $table->string('UFIni', 2)->nullable()->comment('UF Início');
            $table->string('cMunFim')->nullable()->comment('Código IBGE Município Fim');
            $table->string('xMunFim')->nullable()->comment('Nome Município Fim');
            $table->string('UFFim', 2)->nullable()->comment('UF Fim');
            
            // Emitente
            $table->string('cnpj_emitente', 14);
            $table->string('nome_emitente');
            $table->string('ie_emitente')->nullable();
            $table->string('xFant')->nullable()->comment('Nome Fantasia Emitente');
            $table->string('logradouro_emitente')->nullable();
            $table->string('numero_emitente')->nullable();
            $table->string('complemento_emitente')->nullable();
            $table->string('bairro_emitente')->nullable();
            $table->string('municipio_emitente')->nullable();
            $table->string('cod_municipio_emitente')->nullable();
            $table->string('uf_emitente', 2)->nullable();
            $table->string('cep_emitente', 8)->nullable();
            
            // Destinatário
            $table->string('cnpj_destinatario', 14);
            $table->string('nome_destinatario');
            $table->string('ie_destinatario')->nullable();
            $table->string('xFant_destinatario')->nullable()->comment('Nome Fantasia Destinatário');
            $table->string('logradouro_destinatario')->nullable();
            $table->string('numero_destinatario')->nullable();
            $table->string('complemento_destinatario')->nullable();
            $table->string('bairro_destinatario')->nullable();
            $table->string('municipio_destinatario')->nullable();
            $table->string('cod_municipio_destinatario')->nullable();
            $table->string('uf_destinatario', 2)->nullable();
            $table->string('cep_destinatario', 8)->nullable();

            // Remetente
            $table->string('cnpj_remetente', 14)->nullable();
            $table->string('nome_remetente')->nullable();
            $table->string('ie_remetente')->nullable();
            $table->string('xFant_remetente')->nullable()->comment('Nome Fantasia Remetente');
            $table->string('logradouro_remetente')->nullable();
            $table->string('numero_remetente')->nullable();
            $table->string('complemento_remetente')->nullable();
            $table->string('bairro_remetente')->nullable();
            $table->string('municipio_remetente')->nullable();
            $table->string('cod_municipio_remetente')->nullable();
            $table->string('uf_remetente', 2)->nullable();
            $table->string('cep_remetente', 8)->nullable();

            // Expedidor
            $table->string('cnpj_expedidor', 14)->nullable();
            $table->string('nome_expedidor')->nullable();
            $table->string('ie_expedidor')->nullable();
            $table->string('fone_expedidor')->nullable();
            $table->string('xFant_expedidor')->nullable()->comment('Nome Fantasia Expedidor');
            $table->string('logradouro_expedidor')->nullable();
            $table->string('numero_expedidor')->nullable();
            $table->string('complemento_expedidor')->nullable();
            $table->string('bairro_expedidor')->nullable();
            $table->string('municipio_expedidor')->nullable();
            $table->string('cod_municipio_expedidor')->nullable();
            $table->string('uf_expedidor', 2)->nullable();
            $table->string('cep_expedidor', 8)->nullable();

            // Recebedor
            $table->string('cnpj_recebedor', 14)->nullable();
            $table->string('nome_recebedor')->nullable();
            $table->string('ie_recebedor')->nullable();
            $table->string('fone_recebedor')->nullable();
            $table->string('xFant_recebedor')->nullable()->comment('Nome Fantasia Recebedor');
            $table->string('logradouro_recebedor')->nullable();
            $table->string('numero_recebedor')->nullable();
            $table->string('complemento_recebedor')->nullable();
            $table->string('bairro_recebedor')->nullable();
            $table->string('municipio_recebedor')->nullable();
            $table->string('cod_municipio_recebedor')->nullable();
            $table->string('uf_recebedor', 2)->nullable();
            $table->string('cep_recebedor', 8)->nullable();

            // Tomador
            $table->string('tipo_tomador')->nullable()->comment('REMETENTE, EXPEDIDOR, RECEBEDOR, DESTINATARIO, OUTROS');
            $table->string('cnpj_tomador', 14)->nullable();
            $table->string('nome_tomador')->nullable();
            // $table->string('ie_tomador')->nullable();
            // $table->string('fone_tomador')->nullable();
            // $table->string('logradouro_tomador')->nullable();
            // $table->string('numero_tomador')->nullable();
            // $table->string('complemento_tomador')->nullable();
            // $table->string('bairro_tomador')->nullable();
            $table->string('municipio_tomador')->nullable();
            $table->string('cod_municipio_tomador')->nullable();
            // $table->string('uf_tomador', 2)->nullable();
            // $table->string('cep_tomador', 8)->nullable();
            $table->string('xPais')->nullable();
            $table->string('cPais')->nullable();    


            // Valores
            $table->decimal('valor_total', 15, 2);
            $table->decimal('valor_receber', 15, 2);
            $table->decimal('valor_servico', 15, 2);
            
            // Dados fiscais
            $table->string('cst_icms')->nullable()->comment('Código da Situação Tributária do ICMS');
            $table->decimal('valor_icms', 15, 2)->nullable();
            $table->decimal('base_calculo_icms', 15, 2)->nullable();
            $table->decimal('aliquota_icms', 5, 2)->nullable();           
            $table->decimal('peso_bruto', 15, 3)->nullable();
            
            // Status e Controle
            $table->string('status_cte');
            $table->string('status_manifestacao')->default('PENDENTE');
            $table->string('origem')->default('IMPORTADO');
            $table->longText('xml_content');

            // Índices
            $table->index('cnpj_emitente');
            $table->index('cnpj_destinatario');
            $table->index('cnpj_remetente');
            $table->index('cnpj_tomador');
            $table->index('cnpj_expedidor');
            $table->index('cnpj_recebedor');
            $table->index(['UFIni', 'UFFim']);
            $table->index('data_emissao');
            $table->index('status_cte');
            $table->index('status_manifestacao');
            $table->index('tipo_tomador');

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
