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
        Schema::create('render_hook_urls', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('render_hook_id')->unsigned();
            $table->string('url_pattern');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('render_hook_id')->references('id')->on('render_hooks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('render_hook_urls');
    }
};
