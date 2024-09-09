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
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('package_name')->nullable();
            $table->string('package_price')->nullable();
            $table->string('package_gateway')->nullable();
            $table->string('package_id')->nullable();
            $table->string('user_id')->nullable();
            $table->string('tenant_id')->nullable();
            $table->string('status')->nullable();
            $table->string('payment_status')->nullable();
            $table->longText('transaction_id')->nullable();
            $table->longText('attachments')->nullable();
            $table->json('custom_fields')->nullable();
            $table->string('track')->nullable();
            $table->bigInteger('renew_status')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('expire_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
