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
        Schema::create('contabil_plano_de_contas', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id')->index();
            $table->string('codigo');
            $table->string('nome')->nullable();
            $table->string('classificacao');
            $table->string('tipo', 1);
            $table->boolean('is_ativo')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contabil_plano_de_contas');
    }
};
