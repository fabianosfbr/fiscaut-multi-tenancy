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
        Schema::create('contabil_clientes', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id')->index();            
            $table->string('nome');
            $table->string('cnpj')->index()->nullable();
            $table->json('conta_contabil');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contabil_clientes');
    }
};
