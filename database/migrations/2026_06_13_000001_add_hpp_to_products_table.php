<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('hpp', 18, 2)->nullable()->after('uom_id');
        });

        // Migrate existing HPP data from notes column: hpp:XXXXX → hpp column
        $products = DB::table('products')->whereNotNull('notes')->get();
        foreach ($products as $product) {
            $notes = $product->notes ?? '';
            if (preg_match('/hpp\s*:\s*([0-9\.\,]+)/i', $notes, $m)) {
                $onlyDigits = preg_replace('/[^\d]/', '', $m[1]);
                $hppValue = (float) $onlyDigits;
                if ($hppValue > 0) {
                    DB::table('products')->where('id', $product->id)->update(['hpp' => $hppValue]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('hpp');
        });
    }
};
