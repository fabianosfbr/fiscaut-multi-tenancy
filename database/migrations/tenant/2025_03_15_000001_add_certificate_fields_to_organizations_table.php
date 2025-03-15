<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('path_certificado')->nullable()->after('atividade');
            $table->string('senha_certificado')->nullable()->after('path_certificado');
            $table->timestamp('validade_certificado')->nullable()->after('senha_certificado');
            $table->text('certificado_content')->nullable()->after('validade_certificado');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['path_certificado', 'senha_certificado', 'validade_certificado', 'certificado_content']);
        });
    }
}; 