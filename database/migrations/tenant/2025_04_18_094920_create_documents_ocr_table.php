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
        Schema::create('documents_ocr', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('file');
            $table->string('beneficiario_razao_social')->nullable();
            $table->string('beneficiario_cnpj')->nullable();
            $table->decimal('valor', 10, 2)->nullable();
            $table->date('vencimento')->nullable();
            $table->string('pagador_razao_social')->nullable();
            $table->string('pagador_cnpj')->nullable();
            $table->string('linha_digitavel')->nullable();
            $table->longText('raw_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents_ocr');
    }
};
