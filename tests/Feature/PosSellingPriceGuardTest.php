<?php

namespace Tests\Feature;

use Tests\BusinessTestCase;

class PosSellingPriceGuardTest extends BusinessTestCase
{
    public function test_tidak_bisa_menjual_produk_tanpa_harga_jual(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 0]);
        $this->setStock($product, $loc, 5);

        $this->actingAs($kasir)
            ->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 1])
            ->assertStatus(422);
    }
}
