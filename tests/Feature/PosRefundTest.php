<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class PosRefundTest extends BusinessTestCase
{
    public function test_retur_penjualan_mengembalikan_stok_dan_membalik_jurnal(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 5);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 20000])->assertOk();
        $this->postJson(route('kasir.finalize'))->assertOk();

        $sale = DB::table('pos_sales')->where('branch_id', $branch)->first();
        $this->assertEqualsWithDelta(3, $this->stockQty($product, $loc), 0.01);

        // Proses retur
        $this->postJson(route('kasir.history.refund', $sale->id), ['reason' => 'Barang rusak'])
            ->assertOk();

        $sale = DB::table('pos_sales')->where('id', $sale->id)->first();
        $this->assertSame('REFUND', $sale->status);
        $this->assertEqualsWithDelta(5, $this->stockQty($product, $loc), 0.01, 'Stok harus kembali.');
        $this->assertSame(1, DB::table('pos_refunds')->where('sale_id', $sale->id)->count());

        // Jurnal: penjualan & HPP terbalik bersih (net nol)
        $this->assertJournalsBalanced();
        $acc = $this->accountTotals();
        $this->assertEqualsWithDelta(20000, $acc['4900']['debit'], 0.01);  // Retur penjualan
        $this->assertEqualsWithDelta(20000, $acc['1100']['credit'], 0.01); // Kas keluar (refund)
        $this->assertEqualsWithDelta(20000, $acc['1100']['debit'], 0.01);  // Kas masuk (penjualan) -> net 0
        $this->assertEqualsWithDelta(12000, $acc['1300']['debit'], 0.01);  // Persediaan kembali
        $this->assertEqualsWithDelta(12000, $acc['5100']['credit'], 0.01); // HPP dibalik
    }
}
