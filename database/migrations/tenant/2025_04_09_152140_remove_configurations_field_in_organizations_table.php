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
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('isNfeClassificarNaEntrada');
            $table->dropColumn('isNfeManifestarAutomatica');
            $table->dropColumn('isNfeClassificarSomenteManifestacao');
            $table->dropColumn('isNfeMostrarEtiquetaComNomeAbreviado');
            $table->dropColumn('isNfeTomaCreditoIcms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('isNfeClassificarNaEntrada')->default(false);
            $table->boolean('isNfeManifestarAutomatica')->default(false);
            $table->boolean('isNfeClassificarSomenteManifestacao')->default(false);
            $table->boolean('isNfeMostrarEtiquetaComNomeAbreviado')->default(false);
            $table->boolean('isNfeTomaCreditoIcms')->default(false);
        });
    }
};
