<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pos_refunds', 'amount')) {
            Schema::table('pos_refunds', function (Blueprint $table) {
                $table->decimal('amount', 18, 2)->default(0)->after('sale_id');
            });
        }

        $this->backfill();
    }

    /**
     * Isi nilai retur untuk data lama. Sebelumnya nilai retur dihitung saat proses
     * lalu dibuang, sehingga laporan tidak punya sumber angka retur.
     *
     * - Retur yang punya rincian baris  -> jumlahkan (qty diretur x harga baris),
     *   lalu prorata diskon nota (harga baris disimpan bruto, pendapatan diakui netto).
     * - Retur tanpa rincian baris (dibuat sebelum fitur retur parsial ada) -> dianggap
     *   retur penuh sebesar total notanya.
     */
    private function backfill(): void
    {
        $refunds = DB::table('pos_refunds')->where('amount', '<=', 0)->get();

        foreach ($refunds as $refund) {
            $sale = DB::table('pos_sales')->where('id', $refund->sale_id)->first();
            if (!$sale) continue;

            $net    = (float) $sale->total;
            $gross  = $net + (float) ($sale->discount ?? 0);
            $ratio  = $gross > 0 ? $net / $gross : 1.0;

            $bruto = (float) DB::table('pos_refund_lines as rl')
                ->join('pos_sale_lines as sl', 'sl.id', '=', 'rl.pos_sale_line_id')
                ->where('rl.pos_refund_id', $refund->id)
                ->sum(DB::raw('rl.qty * sl.price'));

            // Tanpa rincian baris -> retur penuh senilai total nota.
            $amount = $bruto > 0 ? round($bruto * $ratio, 2) : $net;

            DB::table('pos_refunds')->where('id', $refund->id)->update(['amount' => $amount]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pos_refunds', 'amount')) {
            Schema::table('pos_refunds', function (Blueprint $table) {
                $table->dropColumn('amount');
            });
        }
    }
};
