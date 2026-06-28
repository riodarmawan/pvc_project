<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class PosDoubleSubmitTest extends BusinessTestCase
{
    public function test_finalize_ditolak_saat_transaksi_sebelumnya_masih_terkunci(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 5);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 1])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 10000])->assertOk();

        // Simulasikan transaksi paralel yang sedang memegang kunci.
        Cache::lock('pos-finalize-'.$kasir->id, 10)->get();

        $this->postJson(route('kasir.finalize'))->assertStatus(422);

        $this->assertSame(0, DB::table('pos_sales')->where('branch_id', $branch)->count(),
            'Finalisasi kedua harus ditolak selagi yang pertama berjalan.');
    }
}
