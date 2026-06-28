<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class PosSaleAtomicityTest extends BusinessTestCase
{
    public function test_kegagalan_jurnal_membatalkan_seluruh_transaksi_penjualan(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 5);

        // Rusak COA: hapus akun Penjualan -> jurnal akan gagal saat finalize.
        DB::table('chart_of_accounts')->where('code', '4100')->delete();

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 20000])->assertOk();

        // Finalize harus gagal (jurnal error). Apapun kode HTTP-nya,
        // transaksi penjualan & stok TIDAK boleh berubah.
        try {
            $this->postJson(route('kasir.finalize'));
        } catch (\Throwable $e) {
            // exception dari jurnal — diharapkan
        }

        $this->assertSame(0, DB::table('pos_sales')->where('branch_id', $branch)->count(),
            'Penjualan tidak boleh tersimpan bila jurnal gagal.');
        $this->assertEqualsWithDelta(5, $this->stockQty($product, $loc), 0.01,
            'Stok tidak boleh berkurang bila jurnal gagal.');
    }
}
