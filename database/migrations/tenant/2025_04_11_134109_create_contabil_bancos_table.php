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
        Schema::create('contabil_bancos', function (Blueprint $table) {
            $table->id();
            $table->uuid('organization_id')->index();
            $table->string('nome')->index();
            $table->string('cnpj')->nullable()->index();
            $table->string('agencia')->nullable();
            $table->string('conta')->nullable();
            $table->integer('conta_contabil')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contabil_bancos');
    }
};
