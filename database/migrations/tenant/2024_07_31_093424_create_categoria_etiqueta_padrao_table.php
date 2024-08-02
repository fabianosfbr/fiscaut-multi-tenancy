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
        Schema::create('categoria_etiqueta_padrao', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('order');
            $table->string('name', 100);
            $table->string('color', 100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categoria_etiqueta_padrao');
    }
};
