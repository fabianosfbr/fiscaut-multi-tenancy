<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_panel_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('panel');
            $table->timestamps();

            $table->unique(['user_id', 'panel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_panel_permissions');
    }
}; 