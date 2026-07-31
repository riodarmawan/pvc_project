<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class PosRefundAccountingTest extends BusinessTestCase
{
    /**
     * Penjualan TRANSFER dibukukan ke Bank (1110), jadi returnya harus mengurangi Bank,
     * bukan Kas (1100). Sebelumnya journalPosRefund selalu kredit Kas sehingga kas fisik
     * berkurang di GL padahal uangnya tidak pernah ada di laci.
     */
    public function test_retur_transfer_mengembalikan_ke_bank_bukan_kas(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 5);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'TRANSFER', 'amount' => 20000])->assertOk();
        $this->postJson(route('kasir.finalize'))->assertOk();

        $sale = DB::table('pos_sales')->where('branch_id', $branch)->first();
        $this->postJson(route('kasir.history.refund', $sale->id), ['reason' => 'Barang rusak'])->assertOk();

        $this->assertJournalsBalanced();
        $acc = $this->accountTotals();

        // Bank naik 20rb saat jual, turun 20rb saat retur -> net nol.
        $this->assertEqualsWithDelta(20000, $acc['1110']['debit'], 0.01);
        $this->assertEqualsWithDelta(20000, $acc['1110']['credit'], 0.01);
        // Kas tidak boleh tersentuh sama sekali di transaksi non-tunai ini.
        $this->assertEqualsWithDelta(0, $acc['1100']['debit'] ?? 0, 0.01);
        $this->assertEqualsWithDelta(0, $acc['1100']['credit'] ?? 0, 0.01);
    }

    /**
     * Nota berdiskon: pendapatan diakui netto, jadi nilai retur juga harus netto.
     * Bruto 20.000, diskon 2.000 -> netto 18.000 (9.000/unit).
     * Retur 1 unit harus bernilai 9.000, bukan 10.000 (harga bruto baris).
     */
    public function test_retur_parsial_nota_berdiskon_dihitung_netto(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 5);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 18000])->assertOk();
        $this->postJson(route('kasir.finalize'), ['discount' => 2000])->assertOk();

        $sale = DB::table('pos_sales')->where('branch_id', $branch)->first();
        $line = DB::table('pos_sale_lines')->where('pos_sale_id', $sale->id)->first();

        $this->postJson(route('kasir.history.refund', $sale->id), [
            'reason' => 'Satu rusak',
            'items'  => [['pos_sale_line_id' => $line->id, 'qty' => 1]],
        ])->assertOk();

        $this->assertJournalsBalanced();
        $acc = $this->accountTotals();
        $this->assertEqualsWithDelta(9000, $acc['4900']['debit'], 0.01,
            'Retur 1 unit dari nota diskon = 9.000 (netto), bukan 10.000 bruto.');
        // Kas: masuk 18.000 saat jual, keluar 9.000 saat retur.
        $this->assertEqualsWithDelta(18000, $acc['1100']['debit'], 0.01);
        $this->assertEqualsWithDelta(9000, $acc['1100']['credit'], 0.01);
    }

    /**
     * Retur penuh nota berdiskon tidak boleh mengembalikan lebih dari yang diterima:
     * netto 18.000 masuk, 18.000 keluar -> Kas net nol.
     */
    public function test_retur_penuh_nota_berdiskon_tidak_melebihi_yang_diterima(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 5);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 18000])->assertOk();
        $this->postJson(route('kasir.finalize'), ['discount' => 2000])->assertOk();

        $sale = DB::table('pos_sales')->where('branch_id', $branch)->first();
        $this->postJson(route('kasir.history.refund', $sale->id), ['reason' => 'Semua rusak'])->assertOk();

        $acc = $this->accountTotals();
        $this->assertEqualsWithDelta(18000, $acc['1100']['debit'], 0.01);
        $this->assertEqualsWithDelta(18000, $acc['1100']['credit'], 0.01,
            'Kas keluar harus sama dengan yang masuk (18.000), bukan bruto 20.000.');
        $this->assertEqualsWithDelta(18000, $acc['4900']['debit'], 0.01);
    }

    /** Split payment: retur dibagi proporsional ke Kas & Bank sesuai nota aslinya. */
    public function test_retur_split_payment_dibagi_proporsional_ke_kas_dan_bank(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 5);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 12000])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'TRANSFER', 'amount' => 8000])->assertOk();
        $this->postJson(route('kasir.finalize'))->assertOk();

        $sale = DB::table('pos_sales')->where('branch_id', $branch)->first();
        $this->postJson(route('kasir.history.refund', $sale->id), ['reason' => 'Retur penuh'])->assertOk();

        $this->assertJournalsBalanced();
        $acc = $this->accountTotals();
        // 60% kas (12rb) + 40% bank (8rb) — dikembalikan dengan proporsi yang sama.
        $this->assertEqualsWithDelta(12000, $acc['1100']['credit'], 0.01);
        $this->assertEqualsWithDelta(8000, $acc['1110']['credit'], 0.01);
    }
}
