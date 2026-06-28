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
        Schema::table('leftover_pieces', function (Blueprint $table) {
            $table->decimal('cost_per_m', 12, 2)->nullable()->after('length_m');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leftover_pieces', function (Blueprint $table) {
            $table->dropColumn('cost_per_m');
        });
    }
};
