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
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('organization_id')->constrained('organizations');
            $table->string('title');
            $table->string('path');
            $table->string('extension');
            $table->integer('doc_type')->nullable();
            $table->decimal('doc_value', 10, 2)->nullable();
            $table->boolean('processed')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->string('periodo_exercicio');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_uploads');
    }
};
