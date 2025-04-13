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
        Schema::create('contabil_importar_lancamentos_contabeis', function (Blueprint $table) {            
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->date('data')->nullable();
            $table->decimal('valor', 16, 2)->nullable();
            $table->integer('debito')->nullable();
            $table->integer('credito')->nullable();
            $table->boolean('is_exist')->default(false);
            $table->longText('historico')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contabil_importar_lancamentos_contabeis');
    }
};
