<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class PosRefundTest extends BusinessTestCase
{
    public function test_retur_penjualan_mengembalikan_stok_dan_membalik_jurnal(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 5);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 20000])->assertOk();
        $this->postJson(route('kasir.finalize'))->assertOk();

        $sale = DB::table('pos_sales')->where('branch_id', $branch)->first();
        $this->assertEqualsWithDelta(3, $this->stockQty($product, $loc), 0.01);

        // Proses retur
        $this->postJson(route('kasir.history.refund', $sale->id), ['reason' => 'Barang rusak'])
            ->assertOk();

        $sale = DB::table('pos_sales')->where('id', $sale->id)->first();
        $this->assertSame('REFUND', $sale->status);
        $this->assertEqualsWithDelta(5, $this->stockQty($product, $loc), 0.01, 'Stok harus kembali.');
        $this->assertSame(1, DB::table('pos_refunds')->where('sale_id', $sale->id)->count());

        // Jurnal: penjualan & HPP terbalik bersih (net nol)
        $this->assertJournalsBalanced();
        $acc = $this->accountTotals();
        $this->assertEqualsWithDelta(20000, $acc['4900']['debit'], 0.01);  // Retur penjualan
        $this->assertEqualsWithDelta(20000, $acc['1100']['credit'], 0.01); // Kas keluar (refund)
        $this->assertEqualsWithDelta(20000, $acc['1100']['debit'], 0.01);  // Kas masuk (penjualan) -> net 0
        $this->assertEqualsWithDelta(12000, $acc['1300']['debit'], 0.01);  // Persediaan kembali
        $this->assertEqualsWithDelta(12000, $acc['5100']['credit'], 0.01); // HPP dibalik
    }

    public function test_retur_parsial_qty_custom_lalu_retur_sisanya(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $productA = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $productB = $this->makeProduct(['hpp' => 3000, 'selling_price' => 5000]);
        $this->setStock($productA, $loc, 10);
        $this->setStock($productB, $loc, 10);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $productA, 'qty' => 4])->assertOk();
        $this->postJson(route('kasir.cart.add'), ['product_id' => $productB, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 50000])->assertOk();
        $this->postJson(route('kasir.finalize'))->assertOk();

        $sale = DB::table('pos_sales')->where('branch_id', $branch)->first();
        $lineA = DB::table('pos_sale_lines')->where('pos_sale_id', $sale->id)->where('product_id', $productA)->first();
        $lineB = DB::table('pos_sale_lines')->where('pos_sale_id', $sale->id)->where('product_id', $productB)->first();

        // Retur parsial: cuma 1 dari 4 unit produk A, produk B tidak disentuh.
        $this->postJson(route('kasir.history.refund', $sale->id), [
            'reason' => 'Rusak sebagian',
            'items'  => [['pos_sale_line_id' => $lineA->id, 'qty' => 1]],
        ])->assertOk();

        $this->assertEqualsWithDelta(7, $this->stockQty($productA, $loc), 0.01, 'Stok A cuma balik 1.');
        $this->assertEqualsWithDelta(8, $this->stockQty($productB, $loc), 0.01, 'Stok B tidak berubah.');
        $sale = DB::table('pos_sales')->where('id', $sale->id)->first();
        $this->assertSame('PAID', $sale->status, 'Retur parsial tidak menutup status penjualan.');

        $this->assertJournalsBalanced();
        $acc = $this->accountTotals();
        $this->assertEqualsWithDelta(10000, $acc['4900']['debit'], 0.01); // 1 x harga jual A
        $this->assertEqualsWithDelta(6000, $acc['1300']['debit'], 0.01);  // 1 x HPP A

        // Retur sisanya: 3 unit A + 2 unit B.
        $this->postJson(route('kasir.history.refund', $sale->id), [
            'reason' => 'Sisa retur',
            'items'  => [
                ['pos_sale_line_id' => $lineA->id, 'qty' => 3],
                ['pos_sale_line_id' => $lineB->id, 'qty' => 2],
            ],
        ])->assertOk();

        $this->assertEqualsWithDelta(10, $this->stockQty($productA, $loc), 0.01, 'Stok A balik penuh.');
        $this->assertEqualsWithDelta(10, $this->stockQty($productB, $loc), 0.01, 'Stok B balik penuh.');
        $sale = DB::table('pos_sales')->where('id', $sale->id)->first();
        $this->assertSame('REFUND', $sale->status, 'Semua sisa sudah diretur -> status REFUND.');
        $this->assertSame(2, DB::table('pos_refunds')->where('sale_id', $sale->id)->count());
    }

    public function test_retur_ditolak_bila_qty_melebihi_sisa_refundable(): void
    {
        $branch = $this->makeBranch();
        $loc = $this->makeAvailableLocation($branch);
        $kasir = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 5);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 20000])->assertOk();
        $this->postJson(route('kasir.finalize'))->assertOk();

        $sale = DB::table('pos_sales')->where('branch_id', $branch)->first();
        $line = DB::table('pos_sale_lines')->where('pos_sale_id', $sale->id)->first();

        $this->postJson(route('kasir.history.refund', $sale->id), [
            'reason' => 'Coba lebih dari terjual',
            'items'  => [['pos_sale_line_id' => $line->id, 'qty' => 5]],
        ])->assertStatus(422)->assertJson(['ok' => false]);

        $this->assertEqualsWithDelta(3, $this->stockQty($product, $loc), 0.01, 'Stok tidak berubah, request ditolak semua.');
        $this->assertSame(0, DB::table('pos_refunds')->where('sale_id', $sale->id)->count());
    }
}
