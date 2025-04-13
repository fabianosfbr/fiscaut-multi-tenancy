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
        Schema::create('contabil_layout_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('layout_id');
            $table->integer('position');
            $table->string('rule_type')->nullable();
            $table->string('name')->nullable();
            $table->string('data_source_type')->nullable();
            $table->integer('data_source_historico')->nullable();
            $table->text('data_source')->nullable();
            $table->text('data_source_constant')->nullable();
            $table->text('data_source_query')->nullable();
            $table->string('data_format')->nullable();
            $table->string('format_string')->nullable();
            $table->string('condition_type')->default('none')->nullable();
            $table->text('condition')->nullable();
            $table->string('condition_data_source_type')->nullable();
            $table->text('condition_data_source')->nullable();
            $table->text('condition_data_source_constant')->nullable();
            $table->text('condition_data_source_query')->nullable();
            $table->string('condition_operator')->nullable();
            $table->string('condition_value')->nullable();
            $table->string('default_value')->nullable();
            $table->json('data_source_historical_columns')->nullable();
            $table->boolean('has_condition')->default(0);
            $table->string('data_source_table')->nullable();
            $table->string('data_source_attribute')->nullable();
            $table->string('data_source_condition')->nullable();
            $table->string('data_source_value_type')->nullable();
            $table->string('data_source_search_value')->nullable();
            $table->string('data_source_search_constant')->nullable();
            $table->json('data_source_parametros_gerais_target_columns')->nullable();
            $table->string('condition_data_source_table')->nullable();
            $table->string('condition_data_source_attribute')->nullable();
            $table->string('condition_data_source_condition')->nullable();
            $table->string('condition_data_source_value_type')->nullable();
            $table->string('condition_data_source_search_value')->nullable();
            $table->string('condition_data_source_search_constant')->nullable();
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
        Schema::dropIfExists('contabil_layout_rules');
    }
};
