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
        Schema::create('manifestacoes_fiscais', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations');
            $table->uuidMorphs('documento'); // Para relacionar com NFe ou CTe
            $table->string('chave_acesso', 44);
            $table->string('tipo_documento', 3); // NFe ou CTe
            $table->string('tipo_manifestacao', 6);
            $table->string('status', 20);
            $table->string('protocolo')->nullable();
            $table->text('justificativa')->nullable();
            $table->timestamp('data_manifestacao');
            $table->timestamp('data_resposta')->nullable();
            $table->text('erro')->nullable();
            $table->longText('xml_resposta')->nullable();
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manifestacoes_fiscais');
    }
};
