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
        Schema::create('sieg_importacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('data_inicial');
            $table->date('data_final');
            $table->string('tipo_documento', 50);
            $table->string('tipo_cnpj', 50);
            $table->integer('documentos_processados')->default(0);
            $table->integer('eventos_processados')->default(0);
            $table->integer('total_processados')->default(0);
            $table->integer('total_documentos')->default(0);
            $table->boolean('sucesso')->default(false);
            $table->text('mensagem')->nullable();
            $table->boolean('download_eventos')->default(false);
            $table->enum('status', ['pendente', 'processando', 'concluido', 'erro'])->default('pendente');
            $table->timestamps();
            
            // Índices para melhorar a pesquisa
            $table->index('organization_id');
            $table->index('created_at');
            $table->index(['organization_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sieg_importacoes');
    }
};
