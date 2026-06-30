<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class PosCardPaymentTest extends BusinessTestCase
{
    public function test_pembayaran_kartu_masuk_bank_bukan_kas_dan_rekonsiliasi_nol(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $owner = $this->makeUser(1, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 5);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CARD', 'amount' => 20000])->assertOk();
        $this->postJson(route('kasir.finalize'))->assertOk();

        $acc = $this->accountTotals();
        // Kartu -> Bank (1110), bukan Kas (1100)
        $this->assertEqualsWithDelta(20000, $acc['1110']['debit'], 0.01);
        $this->assertEqualsWithDelta(0, $acc['1100']['debit'] ?? 0, 0.01, 'Kas tidak boleh terpengaruh penjualan kartu.');

        // Rekonsiliasi kas: penjualan kartu tak memengaruhi kas → selisih 0
        $resp = $this->actingAs($owner)->get(route('reports.cash_reconciliation'));
        $row = collect($resp->viewData('rows'))->firstWhere('branch_id', $branch);
        $this->assertEqualsWithDelta(0, (float) $row->gl_kas, 0.01);
        $this->assertEqualsWithDelta(0, (float) $row->selisih, 0.01);
    }
}
