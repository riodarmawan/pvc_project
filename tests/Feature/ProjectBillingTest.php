<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class ProjectBillingTest extends BusinessTestCase
{
    public function test_finalize_proyek_akrual_pisah_pendapatan_dan_material_harga_jual(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $customer = $this->makeCustomer();
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $uom = $this->productUom($product);
        $this->setStock($product, $loc, 5);

        $this->actingAs($kasir);
        $this->post(route('projects.cart.add'), [
            'type' => 'material', 'product_id' => $product, 'uom_id' => $uom, 'qty' => 2,
        ]);
        $this->post(route('projects.cart.add'), [
            'type' => 'service', 'name' => 'Pemasangan', 'price' => 5000,
        ]);
        $this->post(route('projects.finalize'), [
            'title' => 'Proyek A', 'customer_id' => $customer,
            'pay_method' => 'CASH', 'pay_amount' => 25000,
        ]);

        $sale = DB::table('pos_sales')->where('branch_id', $branch)->whereNotNull('project_id')->first();
        $this->assertNotNull($sale, 'Sale proyek harus terbentuk.');
        // Material di harga jual (20000) + jasa (5000) = 25000
        $this->assertEqualsWithDelta(25000, (float) $sale->total, 0.01);
        $this->assertSame('PAID', $sale->status);

        // Stok material berkurang 2 (5 -> 3)
        $this->assertEqualsWithDelta(3, $this->stockQty($product, $loc), 0.01);

        // Akuntansi akrual
        $this->assertJournalsBalanced();
        $acc = $this->accountTotals();
        $this->assertEqualsWithDelta(25000, $acc['1200']['debit'], 0.01);  // Piutang (billing)
        $this->assertEqualsWithDelta(25000, $acc['1200']['credit'], 0.01); // Piutang (pelunasan)
        $this->assertEqualsWithDelta(20000, $acc['4100']['credit'], 0.01); // Penjualan barang
        $this->assertEqualsWithDelta(5000,  $acc['4200']['credit'], 0.01); // Pendapatan jasa
        $this->assertEqualsWithDelta(25000, $acc['1100']['debit'], 0.01);  // Kas
        $this->assertEqualsWithDelta(12000, $acc['5100']['debit'], 0.01);  // COGS = hpp 6000 x 2
        $this->assertEqualsWithDelta(12000, $acc['1300']['credit'], 0.01); // Persediaan
    }
}
