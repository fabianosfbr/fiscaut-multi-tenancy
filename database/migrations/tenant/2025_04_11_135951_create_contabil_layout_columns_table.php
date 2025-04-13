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
        Schema::create('contabil_layout_columns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('layout_id');
            $table->string('excel_column_name');
            $table->string('target_column_name');
            $table->string('data_type')->default('text');
            $table->string('format')->nullable();
            $table->string('date_adjustment')->default('same');
            $table->boolean('is_sanitize')->default(0);
            $table->boolean('is_required')->default(1);
            $table->timestamps();

            $table->foreign('layout_id')
                ->references('id')
                ->on('contabil_layouts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contabil_layout_columns');
    }
};
