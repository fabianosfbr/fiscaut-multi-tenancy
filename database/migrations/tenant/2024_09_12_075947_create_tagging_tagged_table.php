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
        Schema::create('tagging_tagged', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('taggable_id');
            $table->string('taggable_type', 125);
            $table->string('tag_name', 125);
            $table->decimal('value', 14, 4)->nullable();
            $table->uuid('tag_id');
            $table->json('product')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagging_tagged');
    }
};
