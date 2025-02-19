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
            $table->string('chave', 255);
            $table->string('emitente_razao_social', 255)->nullable();
            $table->string('emitente_ie', 255)->nullable();
            $table->string('emitente_cnpj', 255)->nullable();
            $table->string('enderEmit_UF', 5)->nullable();
            $table->dateTime('data_emissao')->nullable();
            $table->dateTime('data_manifesto')->nullable();
            $table->dateTime('data_entrada')->nullable();
            $table->boolean('is_resumo')->nullable()->default(0);
            $table->integer('status_manifestacao')->nullable()->default(0);
            $table->string('nProt', 255)->nullable();
            $table->string('nNF', 255)->nullable();
            $table->string('origem', 255)->nullable();
            $table->integer('status_nota')->nullable();
            $table->decimal('vNfe', 14, 4)->nullable();
            $table->integer('tpNf')->nullable();
            $table->integer('sitNfe')->nullable();
            $table->string('destinatario_ie', 20)->nullable();
            $table->string('destinatario_cnpj', 255)->nullable();
            $table->string('destinatario_razao_social', 255)->nullable();
            $table->string('enderDest_UF', 5)->nullable();
            $table->string('transportador_cnpj', 255)->nullable();
            $table->string('transportador_razao_social', 255)->nullable();
            $table->json('aut_xml')->nullable();
            $table->json('cfops')->nullable();
            $table->string('digVal', 255)->nullable();
            $table->longText('infAdFisco')->nullable();
            $table->longText('infCpl')->nullable();
            $table->json('cobranca')->nullable();
            $table->json('pagamento')->nullable();
            $table->decimal('vBC', 14, 4)->default(0.0000);
            $table->decimal('vICMS', 14, 4)->default(0.0000);
            $table->decimal('vICMSDeson', 14, 4)->default(0.0000);
            $table->decimal('vFCPUFDest', 14, 4)->default(0.0000);
            $table->decimal('vICMSUFDest', 14, 4)->default(0.0000);
            $table->decimal('vICMSUFRemet', 14, 4)->default(0.0000);
            $table->decimal('vFCP', 14, 4)->default(0.0000);
            $table->decimal('vBCST', 14, 4)->default(0.0000);
            $table->decimal('vST', 14, 4)->default(0.0000);
            $table->decimal('vFCPST', 14, 4)->default(0.0000);
            $table->decimal('vFCPSTRet', 14, 4)->default(0.0000);
            $table->decimal('vProd', 14, 4)->default(0.0000);
            $table->decimal('vFrete', 14, 4)->default(0.0000);
            $table->decimal('vSeg', 14, 4)->default(0.0000);
            $table->decimal('vDesc', 14, 4)->default(0.0000);
            $table->decimal('vII', 14, 4)->default(0.0000);
            $table->decimal('vIPI', 14, 4)->default(0.0000);
            $table->decimal('vIPIDevol', 14, 4)->default(0.0000);
            $table->decimal('vPIS', 14, 4)->default(0.0000);
            $table->decimal('vCOFINS', 14, 4)->default(0.0000);
            $table->decimal('vOutro', 14, 4)->default(0.0000);
            $table->decimal('vTotTrib', 14, 4)->default(0.0000);

            $table->string('nat_op', 255)->nullable();
            $table->json('carta_correcao')->nullable();
            $table->integer('num_produtos')->nullable();
            $table->binary('xml')->nullable();
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
