<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class CashReconciliationTest extends BusinessTestCase
{
    public function test_rekonsiliasi_kas_cocok_setelah_penjualan_tunai(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $owner = $this->makeUser(1, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 5);

        // Penjualan tunai 20000 (GL Kas +20000, kas operasional +20000)
        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 20000])->assertOk();
        $this->postJson(route('kasir.finalize'))->assertOk();

        $resp = $this->actingAs($owner)->get(route('reports.cash_reconciliation'));
        $resp->assertOk();

        $rows = collect($resp->viewData('rows'));
        $row = $rows->firstWhere('branch_id', $branch);
        $this->assertNotNull($row, 'Baris cabang harus ada di laporan.');
        $this->assertEqualsWithDelta(20000, (float) $row->gl_kas, 0.01);
        $this->assertEqualsWithDelta(20000, (float) $row->operational, 0.01);
        $this->assertEqualsWithDelta(0, (float) $row->selisih, 0.01);
    }
}
