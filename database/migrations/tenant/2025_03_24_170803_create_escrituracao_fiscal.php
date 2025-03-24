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
        Schema::create('escrituracao_fiscal', function (Blueprint $table) {
            $table->id();
            $table->uuidMorphs('escrituravel');
            $table->foreignUuid('organization_id')->constrained('organizations')->onDelete('cascade');            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escrituracao_fiscal');
    }
};
