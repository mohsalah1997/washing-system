<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('meter_readings')->update([
            'is_approved' => true,
        ]);
    }

    public function down(): void
    {
        DB::table('meter_readings')->update([
            'is_approved' => false,
        ]);
    }
};

