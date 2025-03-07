<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('db_host')->nullable()->after('password');
            $table->string('db_name')->nullable()->after('db_host');
            $table->string('db_username')->nullable()->after('db_name');
            $table->string('db_password')->nullable()->after('db_username');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['db_host', 'db_database', 'db_username', 'db_password']);
        });
    }
};
