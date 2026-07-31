<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class PosOversellTest extends BusinessTestCase
{
    /**
     * Defense-in-depth: cart-add lolos karena AVAILABLE cukup saat itu (2), tapi stok
     * AVAILABLE berkurang (race dari transaksi lain) sebelum finalize benar-benar jalan.
     * Guard lockForUpdate di dalam transaksi tetap harus menolak & tidak membuat stok negatif.
     */
    public function test_finalisasi_ditolak_dan_stok_tidak_negatif_saat_lokasi_jual_berkurang_sebelum_finalize(): void
    {
        $branch = $this->makeBranch();
        $avail = $this->makeAvailableLocation($branch);
        $store = (int) DB::table('stock_locations')->insertGetId([
            'branch_id' => $branch, 'code' => 'STR'.substr(uniqid(), -5), 'name' => 'Store', 'type' => 'STORE',
        ]);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $store, 5); // stok gudang, tidak relevan buat jual
        $this->setStock($product, $avail, 2); // cukup saat cart-add

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 20000])->assertOk();

        // Simulasikan race: kasir lain menghabiskan AVAILABLE sesaat sebelum finalize ini jalan.
        DB::table('stock_quants')->where('product_id', $product)->where('location_id', $avail)->update(['qty' => 0]);

        $this->postJson(route('kasir.finalize'))->assertStatus(422);

        $this->assertSame(0, DB::table('pos_sales')->where('branch_id', $branch)->count(),
            'Penjualan tidak boleh terbentuk bila lokasi jual tak cukup.');
        $this->assertGreaterThanOrEqual(0, $this->stockQty($product, $avail),
            'Stok AVAILABLE tidak boleh negatif.');
    }

    /**
     * Bug yang dilaporkan: stok produk terpecah antara STORE (200) dan AVAILABLE (9).
     * Sebelum fix, katalog & cart-add menjumlah SEMUA lokasi (209) sehingga kasir bisa
     * memasukkan qty besar ke keranjang, lalu baru gagal saat finalize dengan pesan
     * membingungkan "stok tidak cukup". Sekarang cart-add harus menolak dari awal,
     * sesuai stok yang benar-benar bisa dijual (AVAILABLE saja).
     */
    public function test_cart_add_ditolak_sejak_awal_bila_stok_hanya_ada_di_lokasi_store(): void
    {
        $branch = $this->makeBranch();
        $avail = $this->makeAvailableLocation($branch);
        $store = (int) DB::table('stock_locations')->insertGetId([
            'branch_id' => $branch, 'code' => 'STR'.substr(uniqid(), -5), 'name' => 'Store', 'type' => 'STORE',
        ]);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $avail, 9);
        $this->setStock($product, $store, 200);

        $this->actingAs($kasir);

        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 50])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        // Sesuai stok AVAILABLE (9), bukan total gabungan (209).
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 9])->assertOk();
    }
}
