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
            $table->string('tipo', 3)->nullable()->after('serie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notas_fiscais_eletronica', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
