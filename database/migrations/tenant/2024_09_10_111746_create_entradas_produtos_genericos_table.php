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
        Schema::create('entradas_produtos_genericos', function (Blueprint $table) {
            $table->id();
            $table->integer('cod_produto');
            $table->string('descricao');
            $table->integer('ncm');
            $table->integer('grupo_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entradas_produtos_genericos');
    }
};
