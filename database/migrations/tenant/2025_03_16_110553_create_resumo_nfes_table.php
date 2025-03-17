<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumo_nfes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations');
            $table->string('chave', 44)->unique();
            $table->string('cnpj_emitente', 14);
            $table->string('nome_emitente');
            $table->string('ie_emitente')->nullable();
            $table->dateTime('data_emissao');
            $table->decimal('valor_total', 15, 2);
            $table->string('situacao', 3);
            $table->text('xml_resumo');
            $table->boolean('necessita_manifestacao')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resumo_nfes');
    }
}; 