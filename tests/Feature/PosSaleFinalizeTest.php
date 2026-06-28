<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class PosSaleFinalizeTest extends BusinessTestCase
{
    public function test_finalisasi_pos_membentuk_jurnal_seimbang_dan_mengurangi_stok(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 5);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])
            ->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 20000])
            ->assertOk();
        $this->postJson(route('kasir.finalize'))->assertOk();

        // Sale tersimpan & lunas
        $sale = DB::table('pos_sales')->where('branch_id', $branch)->first();
        $this->assertNotNull($sale);
        $this->assertSame('PAID', $sale->status);
        $this->assertEqualsWithDelta(20000, (float) $sale->total, 0.01);

        // Stok berkurang 2 (5 -> 3)
        $this->assertEqualsWithDelta(3, $this->stockQty($product, $loc), 0.01);

        // Akuntansi: jurnal seimbang; revenue 20000, COGS 12000
        $this->assertJournalsBalanced();
        $acc = $this->accountTotals();
        $this->assertEqualsWithDelta(20000, $acc['4100']['credit'], 0.01); // Penjualan
        $this->assertEqualsWithDelta(20000, $acc['1100']['debit'], 0.01);  // Kas
        $this->assertEqualsWithDelta(12000, $acc['5100']['debit'], 0.01);  // COGS
        $this->assertEqualsWithDelta(12000, $acc['1300']['credit'], 0.01); // Persediaan
    }
}
