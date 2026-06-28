<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class PosOversellTest extends BusinessTestCase
{
    /**
     * Stok ada di lokasi STORE (5) tetapi lokasi penjualan AVAILABLE kosong (0).
     * Cek katalog menjumlah semua lokasi sehingga lolos, namun penjualan hanya
     * mengurangi AVAILABLE. Tanpa guard, stok AVAILABLE jadi negatif.
     */
    public function test_finalisasi_ditolak_dan_stok_tidak_negatif_saat_lokasi_jual_tak_cukup(): void
    {
        $branch = $this->makeBranch();
        $avail = $this->makeAvailableLocation($branch);
        $store = (int) DB::table('stock_locations')->insertGetId([
            'branch_id' => $branch, 'code' => 'STR'.substr(uniqid(), -5), 'name' => 'Store', 'type' => 'STORE',
        ]);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $store, 5);
        $this->setStock($product, $avail, 0);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 20000])->assertOk();

        $this->postJson(route('kasir.finalize'))->assertStatus(422);

        $this->assertSame(0, DB::table('pos_sales')->where('branch_id', $branch)->count(),
            'Penjualan tidak boleh terbentuk bila lokasi jual tak cukup.');
        $this->assertGreaterThanOrEqual(0, $this->stockQty($product, $avail),
            'Stok AVAILABLE tidak boleh negatif.');
    }
}
