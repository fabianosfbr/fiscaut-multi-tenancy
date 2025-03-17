<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_nfe', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations');
            $table->string('chave_nfe', 44);
            $table->string('tipo_evento', 6);
            $table->integer('numero_sequencial');
            $table->dateTime('data_evento');
            $table->string('protocolo')->nullable();
            $table->string('status_sefaz', 3)->nullable();
            $table->string('motivo')->nullable();
            $table->text('xml_evento')->nullable();
            $table->timestamps();

            // Índices
            $table->index('chave_nfe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_nfe');
    }
}; 