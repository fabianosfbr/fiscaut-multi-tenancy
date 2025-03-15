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
        Schema::dropIfExists('digital_certificates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('digital_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('password');
            $table->timestamps();
        });
    }
};
