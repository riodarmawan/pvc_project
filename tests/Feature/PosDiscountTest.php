<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class PosDiscountTest extends BusinessTestCase
{
    public function test_diskon_per_nota_mengurangi_pendapatan_dan_total(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 5);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        // Bruto 20000, diskon 2000 -> netto 18000
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 18000])->assertOk();
        $this->postJson(route('kasir.finalize'), ['discount' => 2000])->assertOk();

        $sale = DB::table('pos_sales')->where('branch_id', $branch)->first();
        $this->assertNotNull($sale);
        $this->assertEqualsWithDelta(18000, (float) $sale->total, 0.01, 'Total = netto setelah diskon.');
        $this->assertEqualsWithDelta(2000, (float) $sale->discount, 0.01);
        $this->assertSame('PAID', $sale->status);

        $this->assertJournalsBalanced();
        $acc = $this->accountTotals();
        $this->assertEqualsWithDelta(18000, $acc['4100']['credit'], 0.01); // pendapatan netto
        $this->assertEqualsWithDelta(18000, $acc['1100']['debit'], 0.01);  // kas
        $this->assertEqualsWithDelta(12000, $acc['5100']['debit'], 0.01);  // COGS tetap di HPP
    }
}
