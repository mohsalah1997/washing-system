<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->string('source', 50)->default('admin_panel')->after('note');
            $table->uuid('client_uuid')->nullable()->after('source');
            $table->unique('client_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('meter_readings', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->dropColumn(['source', 'client_uuid']);
        });
    }
};
