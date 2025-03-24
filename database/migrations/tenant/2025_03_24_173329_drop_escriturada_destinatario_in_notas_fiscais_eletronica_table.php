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
        Schema::table('notas_fiscais_eletronica', function (Blueprint $table) {
            $table->dropColumn('escriturada_destinatario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notas_fiscais_eletronica', function (Blueprint $table) {
            $table->boolean('escriturada_destinatario')->default(false);
        });
    }
};
