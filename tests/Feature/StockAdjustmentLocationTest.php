<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class StockAdjustmentLocationTest extends BusinessTestCase
{
    /**
     * Penyesuaian stok harus mengoreksi lokasi AVAILABLE (yang dijual POS),
     * bukan lokasi STORE. Sebelumnya opname menulis ke STORE sehingga stok jual
     * tidak pernah terkoreksi dan muncul saldo bayangan di gudang.
     */
    public function test_penyesuaian_stok_mengoreksi_lokasi_available_bukan_store(): void
    {
        $branch = $this->makeBranch();
        $avail  = $this->makeAvailableLocation($branch);
        $store  = (int) DB::table('stock_locations')->insertGetId([
            'branch_id' => $branch, 'code' => 'STR'.substr(uniqid(), -5), 'name' => 'Gudang', 'type' => 'STORE',
        ]);
        $owner   = $this->makeUser(1, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);

        $this->setStock($product, $avail, 619);
        $this->setStock($product, $store, 0);

        // Opname: stok fisik di rak ternyata 600 (susut 19).
        $this->actingAs($owner)
            ->post(route('stock.adjust.store'), ['product_id' => $product, 'new_qty' => 600])
            ->assertRedirect();

        $this->assertEqualsWithDelta(600, $this->stockQty($product, $avail), 0.01,
            'Stok AVAILABLE harus dikoreksi jadi 600.');
        $this->assertEqualsWithDelta(0, $this->stockQty($product, $store), 0.01,
            'Stok STORE tidak boleh disentuh / dibuatkan saldo bayangan.');

        // Susut 19 unit x HPP 6000 = 114.000 -> DR HPP (5100), CR Persediaan (1300).
        $this->assertJournalsBalanced();
        $acc = $this->accountTotals();
        $this->assertEqualsWithDelta(114000, $acc['5100']['debit'], 0.01);
        $this->assertEqualsWithDelta(114000, $acc['1300']['credit'], 0.01);
    }
}
