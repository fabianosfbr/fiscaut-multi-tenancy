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
        Schema::create('eventos_cte', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id');
            $table->string('chave_cte', 44);
            $table->string('tipo_evento', 10);
            $table->integer('numero_sequencial')->default(1);
            $table->dateTime('data_evento');
            $table->string('protocolo')->nullable();
            $table->string('status_sefaz')->nullable();
            $table->string('motivo')->nullable();
            $table->longText('xml_evento');
            $table->longText('xml_resumo')->nullable();
            $table->timestamps();
            
            // Índices para melhorar a performance
            $table->index('organization_id');
            $table->index('chave_cte');
            $table->index('tipo_evento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventos_cte');
    }
};
