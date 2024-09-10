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
        Schema::create('entradas_acumulador_equivalentes', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id');
            $table->integer('etiqueta_entrada');
            $table->json('valores')->nullable();
            $table->json('cfops')->nullable();
            $table->string('tipo')->nullable()->default('nfe-entrada-terceiro');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entradas_acumulador_equivalentes');
    }
};
