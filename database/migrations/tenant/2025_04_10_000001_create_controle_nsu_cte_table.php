<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controle_nsu_cte', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations');
            $table->bigInteger('ultimo_nsu')->default(0);
            $table->bigInteger('max_nsu')->default(0);
            $table->dateTime('ultima_consulta');
            $table->longText('xml_content')->nullable();
            $table->timestamps();
            
            // Índices para melhorar a performance
            $table->index('ultimo_nsu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controle_nsu_cte');
    }
}; 