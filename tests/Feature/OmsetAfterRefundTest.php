<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class OmsetAfterRefundTest extends BusinessTestCase
{
    /** Jual $qty unit @10.000, kembalikan id notanya. */
    private function jual(int $branch, int $loc, $kasir, int $product, int $qty): object
    {
        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => $qty])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 10000 * $qty])->assertOk();
        $this->postJson(route('kasir.finalize'))->assertOk();

        return DB::table('pos_sales')->where('branch_id', $branch)->orderByDesc('id')->first();
    }

    /** Nilai retur wajib tersimpan, supaya laporan tidak perlu menghitung ulang. */
    public function test_nilai_retur_tersimpan_di_pos_refunds(): void
    {
        $branch  = $this->makeBranch();
        $loc     = $this->makeAvailableLocation($branch);
        $kasir   = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 10);

        $sale = $this->jual($branch, $loc, $kasir, $product, 3);
        $line = DB::table('pos_sale_lines')->where('pos_sale_id', $sale->id)->first();

        // Retur 1 dari 3 unit.
        $this->postJson(route('kasir.history.refund', $sale->id), [
            'reason' => 'Rusak',
            'items'  => [['pos_sale_line_id' => $line->id, 'qty' => 1]],
        ])->assertOk();

        $this->assertEqualsWithDelta(10000,
            (float) DB::table('pos_refunds')->where('sale_id', $sale->id)->value('amount'), 0.01);
    }

    /**
     * Retur SEBAGIAN: status nota tetap PAID, jadi dulu tetap terhitung penuh.
     * Sekarang omset harus berkurang sebesar nilai returnya.
     */
    public function test_omset_dashboard_berkurang_setelah_retur_sebagian(): void
    {
        $branch  = $this->makeBranch();
        $loc     = $this->makeAvailableLocation($branch);
        $kasir   = $this->makeUser(3, $branch);
        $owner   = $this->makeUser(1, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 10);

        $sale = $this->jual($branch, $loc, $kasir, $product, 3);   // omset 30.000
        $line = DB::table('pos_sale_lines')->where('pos_sale_id', $sale->id)->first();

        $this->postJson(route('kasir.history.refund', $sale->id), [
            'reason' => 'Rusak',
            'items'  => [['pos_sale_line_id' => $line->id, 'qty' => 1]],   // retur 10.000
        ])->assertOk();

        $this->assertSame('PAID', DB::table('pos_sales')->where('id', $sale->id)->value('status'),
            'Retur sebagian memang menyisakan status PAID — justru itu sumber masalahnya dulu.');

        $omset = $this->actingAs($owner)->get(route('owner.home'))
            ->assertOk()->viewData('totalPenjualan');

        $this->assertEqualsWithDelta(20000, (float) $omset, 0.01,
            'Omset harus 30.000 - 10.000 = 20.000.');
    }

    /** Retur PENUH: dulu notanya hilang dari laporan; sekarang omsetnya jadi nol. */
    public function test_omset_nol_setelah_retur_penuh(): void
    {
        $branch  = $this->makeBranch();
        $loc     = $this->makeAvailableLocation($branch);
        $kasir   = $this->makeUser(3, $branch);
        $owner   = $this->makeUser(1, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 10);

        $sale = $this->jual($branch, $loc, $kasir, $product, 2);   // omset 20.000
        $this->postJson(route('kasir.history.refund', $sale->id), ['reason' => 'Semua balik'])->assertOk();

        $omset = $this->actingAs($owner)->get(route('owner.home'))
            ->assertOk()->viewData('totalPenjualan');

        $this->assertEqualsWithDelta(0, (float) $omset, 0.01);
    }

    /** Laporan Riwayat Transaksi: retur tampil sebagai baris sendiri + ringkasan netto. */
    public function test_laporan_menampilkan_retur_sebagai_baris_terpisah(): void
    {
        $branch  = $this->makeBranch();
        $loc     = $this->makeAvailableLocation($branch);
        $kasir   = $this->makeUser(3, $branch);
        $owner   = $this->makeUser(1, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 10);

        $sale = $this->jual($branch, $loc, $kasir, $product, 3);   // 30.000
        $line = DB::table('pos_sale_lines')->where('pos_sale_id', $sale->id)->first();
        $this->postJson(route('kasir.history.refund', $sale->id), [
            'reason' => 'Rusak',
            'items'  => [['pos_sale_line_id' => $line->id, 'qty' => 1]],   // 10.000
        ])->assertOk();

        $res = $this->actingAs($owner)
            ->get(route('reports.transactions.index', ['branch_id' => $branch]))
            ->assertOk();

        $rows = collect($res->viewData('transactions')->items());
        $this->assertCount(2, $rows, 'Harus ada 2 baris: penjualan + retur.');
        $this->assertNotNull($rows->firstWhere('transaction_type', 'Retur'));
        $this->assertEqualsWithDelta(-10000,
            (float) $rows->firstWhere('transaction_type', 'Retur')->transaction_value, 0.01,
            'Baris retur bernilai negatif.');

        $summary = $res->viewData('summary');
        $this->assertEqualsWithDelta(30000, (float) $summary->total_penjualan, 0.01);
        $this->assertEqualsWithDelta(10000, (float) $summary->total_retur, 0.01);
        $this->assertEqualsWithDelta(20000, (float) $summary->total_netto, 0.01);
    }
}
