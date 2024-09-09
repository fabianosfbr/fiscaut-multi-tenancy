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
        Schema::create('price_plans', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->integer('type')->nullable();
            $table->boolean('status')->default(0);
            $table->decimal('price', 10, 2);
            $table->longText('faq')->nullable();
            $table->integer('documents_permission_feature')->nullable();
            $table->integer('users_permission_feature')->nullable();
            $table->integer('storage_permission_feature')->nullable();
            $table->boolean('has_trial')->default(false);
            $table->unsignedInteger('trial_days')->nullable();
            $table->string('package_badge')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_plans');
    }
};
