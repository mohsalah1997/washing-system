<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // الرصيد الافتتاحي: موجب = له رصيد عندنا، سالب = عليه مبلغ
            $table->decimal('initial_balance', 12, 2)->default(0)->after('initial_reading');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('initial_balance');
        });
    }
};
