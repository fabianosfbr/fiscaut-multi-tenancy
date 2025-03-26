<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_referencias', function (Blueprint $table) {
            $table->id();
            
            // Documento que faz referência - usando morphs explícito com nomes curtos
            $table->string('documento_origem_type');
            $table->uuid('documento_origem_id');
            $table->string('chave_acesso_origem');
            $table->index(['documento_origem_type', 'documento_origem_id'], 'idx_doc_origem');
            
            // Documento referenciado - usando morphs explícito com nomes curtos
            $table->string('documento_referenciado_type')->nullable();
            $table->uuid('documento_referenciado_id')->nullable();
            $table->string('chave_acesso_referenciada');
            $table->index(['documento_referenciado_type', 'documento_referenciado_id'], 'idx_doc_ref');
            
            // Metadados
            $table->string('tipo_referencia')->default('NFE');
            $table->timestamps();
            
            // Índices e restrições
            $table->unique(
                ['documento_origem_type', 'documento_origem_id', 'chave_acesso_referenciada'],
                'idx_unique_referencia'
            );
            $table->index('chave_acesso_origem', 'idx_chave_origem');
            $table->index('chave_acesso_referenciada', 'idx_chave_ref');
        });
        
        
    }

    public function down(): void
    {
        
        Schema::dropIfExists('documento_referencias');
    }
}; 