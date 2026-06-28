<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class ProductHppRequiredTest extends BusinessTestCase
{
    public function test_produk_baru_wajib_punya_hpp(): void
    {
        $branch = $this->makeBranch();
        $user = $this->makeUser(3, $branch);
        $cat = (int) DB::table('product_categories')->insertGetId(['code' => 'CC'.substr(uniqid(), -5), 'name' => 'Cat']);
        $uom = (int) DB::table('uoms')->insertGetId(['code' => 'UU'.substr(uniqid(), -5), 'name' => 'Pcs']);

        $this->actingAs($user)
            ->post(route('products.store'), [
                'sku' => 'SKU-'.substr(uniqid(), -6),
                'name' => 'Produk Tanpa HPP',
                'uom_id' => $uom,
                'category_id' => $cat,
                'selling_price' => 10000,
                // hpp sengaja dikosongkan
            ])
            ->assertSessionHasErrors('hpp');
    }
}
