<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pos_sales', 'discount')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                $table->decimal('discount', 14, 2)->default(0)->after('total');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pos_sales', 'discount')) {
            Schema::table('pos_sales', function (Blueprint $table) {
                $table->dropColumn('discount');
            });
        }
    }
};
