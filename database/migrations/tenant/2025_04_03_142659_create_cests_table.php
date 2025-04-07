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
        Schema::create('tabela_cest', function (Blueprint $table) {
            $table->id();
            $table->string('cest')->index();
            $table->string('ncm')->index();
            $table->text('descricao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tabela_cest');
    }
};
