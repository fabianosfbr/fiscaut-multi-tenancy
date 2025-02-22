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
        Schema::create('acumuladores', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id')->constrained('organizations');
            $table->integer('codi_acu')->index();
            $table->string('nome_acu')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acumuladores');
    }
};
