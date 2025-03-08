<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_fiscais_eletronica', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();

            // Campos de identificação da nota
            $table->string('chave_acesso')->unique();
            $table->string('numero');
            $table->string('serie');
            $table->string('natureza_operacao');
            $table->datetime('data_emissao');
            $table->datetime('data_entrada')->nullable();

            // Dados do Emitente
            $table->string('cnpj_emitente');
            $table->string('ie_emitente')->nullable();
            $table->string('nome_emitente');
            $table->string('logradouro_emitente')->nullable();
            $table->string('numero_emitente')->nullable();
            $table->string('complemento_emitente')->nullable();
            $table->string('bairro_emitente')->nullable();
            $table->string('municipio_emitente')->nullable();
            $table->string('uf_emitente', 2)->nullable();
            $table->string('cep_emitente', 8)->nullable();

            // Dados do Destinatário
            $table->string('cnpj_destinatario');
            $table->string('ie_destinatario')->nullable();
            $table->string('nome_destinatario');
            $table->string('logradouro_destinatario')->nullable();
            $table->string('numero_destinatario')->nullable();
            $table->string('complemento_destinatario')->nullable();
            $table->string('bairro_destinatario')->nullable();
            $table->string('municipio_destinatario')->nullable();
            $table->string('uf_destinatario', 2)->nullable();
            $table->string('cep_destinatario', 8)->nullable();
            $table->string('telefone_destinatario')->nullable();
            $table->string('email_destinatario')->nullable();

            // Valores dos Produtos
            $table->decimal('valor_produtos', 10, 2)->default(0);
            $table->decimal('valor_frete', 10, 2)->default(0);
            $table->decimal('valor_seguro', 10, 2)->default(0);
            $table->decimal('valor_desconto', 10, 2)->default(0);
            $table->decimal('valor_outras_despesas', 10, 2)->default(0);
            $table->decimal('valor_total', 10, 2)->default(0);

            // Valores de ICMS
            $table->decimal('valor_base_icms', 10, 2)->default(0);
            $table->decimal('valor_icms', 10, 2)->default(0);
            $table->decimal('valor_icms_desonerado', 10, 2)->default(0);
            $table->decimal('valor_fcp', 10, 2)->default(0);
            $table->decimal('valor_base_icms_st', 10, 2)->default(0);
            $table->decimal('valor_icms_st', 10, 2)->default(0);
            $table->decimal('valor_fcp_st', 10, 2)->default(0);
            $table->decimal('valor_fcp_st_ret', 10, 2)->default(0);

            // Valores de Difal
            $table->decimal('valor_fundo_combate_uf_dest', 10, 2)->default(0);
            $table->decimal('valor_icms_uf_dest', 10, 2)->default(0);
            $table->decimal('valor_icms_uf_remet', 10, 2)->default(0);

            // Outros Impostos
            $table->decimal('valor_imposto_importacao', 10, 2)->default(0);
            $table->decimal('valor_ipi', 10, 2)->default(0);
            $table->decimal('valor_ipi_devolucao', 10, 2)->default(0);
            $table->decimal('valor_pis', 10, 2)->default(0);
            $table->decimal('valor_cofins', 10, 2)->default(0);
            $table->decimal('valor_aproximado_tributos', 10, 2)->default(0);

            // Campos de Controle
            $table->string('status_nota')->nullable();
            $table->string('status_manifestacao')->nullable();
            $table->string('origem')->nullable();
            $table->json('cfops')->nullable();
            $table->json('aut_xml')->nullable();
            $table->json('carta_correcao')->nullable();
            $table->json('pagamento')->nullable();
            $table->json('cobranca')->nullable();

            // Armazenamento do XML
            $table->longText('xml_content')->nullable();

            // Índices
            $table->index('cnpj_emitente');
            $table->index('cnpj_destinatario');
            $table->index('data_emissao');
            $table->index('status_nota');
            $table->index('status_manifestacao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_fiscais_eletronica');
    }
};