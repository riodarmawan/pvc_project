<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class ProductMovingAverageTest extends BusinessTestCase
{
    public function test_pembelian_memperbarui_hpp_secara_rata_rata_tertimbang(): void
    {
        $branch = $this->makeBranch();
        $owner = $this->makeUser(1, $branch);
        $supplier = (int) DB::table('suppliers')->insertGetId(['name' => 'Supplier Test']);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);

        $this->actingAs($owner);

        // Pembelian 1: 10 unit @ 6000  -> HPP tetap 6000, stok 10
        $this->post(route('purchase.direct.store'), [
            'supplier_id' => $supplier, 'branch_id' => $branch, 'invoice_no' => 'INV-1',
            'items' => [['product_id' => $product, 'qty' => 10, 'price' => 6000]],
        ]);
        $this->assertEqualsWithDelta(6000, (float) DB::table('products')->where('id', $product)->value('hpp'), 0.01);

        // Pembelian 2: 10 unit @ 8000  -> HPP = (10*6000 + 10*8000)/20 = 7000, stok 20
        $this->post(route('purchase.direct.store'), [
            'supplier_id' => $supplier, 'branch_id' => $branch, 'invoice_no' => 'INV-2',
            'items' => [['product_id' => $product, 'qty' => 10, 'price' => 8000]],
        ]);

        $this->assertEqualsWithDelta(7000, (float) DB::table('products')->where('id', $product)->value('hpp'), 0.01,
            'HPP harus jadi rata-rata tertimbang.');
    }
}
