<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class RefundConfirmPageTest extends BusinessTestCase
{
    private function makePaidSale(int $branch, int $kasirId, string $status = 'PAID'): int
    {
        $saleId = (int) DB::table('pos_sales')->insertGetId([
            'branch_id' => $branch, 'cashier_id' => $kasirId,
            'sale_datetime' => now(), 'status' => $status, 'total' => 20000,
        ]);
        $product = $this->makeProduct();
        DB::table('pos_sale_lines')->insert([
            'pos_sale_id' => $saleId, 'product_id' => $product, 'uom_id' => $this->productUom($product),
            'qty' => 2, 'price' => 10000, 'discount' => 0, 'subtotal' => 20000,
        ]);
        return $saleId;
    }

    public function test_halaman_konfirmasi_retur_tampil_untuk_penjualan_paid(): void
    {
        $branch = $this->makeBranch();
        $kasir = $this->makeUser(3, $branch);
        $saleId = $this->makePaidSale($branch, $kasir->id);

        $this->actingAs($kasir)
            ->get(route('kasir.history.refund.confirm', $saleId))
            ->assertOk()
            ->assertSee('Konfirmasi Retur')
            ->assertSee('Proses Retur');
    }

    public function test_tidak_bisa_konfirmasi_retur_penjualan_yang_sudah_diretur(): void
    {
        $branch = $this->makeBranch();
        $kasir = $this->makeUser(3, $branch);
        $saleId = $this->makePaidSale($branch, $kasir->id, 'REFUND');

        $this->actingAs($kasir)
            ->get(route('kasir.history.refund.confirm', $saleId))
            ->assertRedirect(route('kasir.history'));
    }
}
