<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class RefundNoteTest extends BusinessTestCase
{
    /** Jual 3 unit @10.000 ke pelanggan beralamat, kembalikan [saleId, lineId, kasir]. */
    private function jualLalu(int $branch, int $loc): array
    {
        $kasir   = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 10);

        $customer = (int) DB::table('customers')->insertGetId([
            'name' => 'Siti Pembeli', 'phone' => '08999', 'address' => 'Jl. Kenanga 12',
        ]);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 3])->assertOk();
        $this->postJson(route('kasir.customer.select'), ['customer_id' => $customer])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 30000])->assertOk();
        $this->postJson(route('kasir.finalize'))->assertOk();

        $sale = DB::table('pos_sales')->where('branch_id', $branch)->first();
        $line = DB::table('pos_sale_lines')->where('pos_sale_id', $sale->id)->first();

        return [$sale, $line, $kasir];
    }

    /** Nota Retur adalah dokumen tersendiri yang mengacu ke invoice asalnya. */
    public function test_nota_retur_bisa_dicetak_dan_merujuk_invoice_asal(): void
    {
        $branch = $this->makeBranch();
        $loc    = $this->makeAvailableLocation($branch);
        [$sale, $line] = $this->jualLalu($branch, $loc);

        $this->postJson(route('kasir.history.refund', $sale->id), [
            'reason' => 'Warna tidak sesuai',
            'items'  => [['pos_sale_line_id' => $line->id, 'qty' => 2]],
        ])->assertOk();

        $refund = DB::table('pos_refunds')->where('sale_id', $sale->id)->first();

        $this->get(route('kasir.refund.print', $refund->id))
            ->assertOk()
            ->assertSee('Nota Retur #'.$refund->id)
            ->assertSee('Atas Invoice #'.$sale->id)        // rujukan ke dokumen asal
            ->assertSee('Barang yang dikembalikan')
            ->assertSee('Total Retur: Rp 20.000,00')       // 2 unit x 10.000
            ->assertSee('Warna tidak sesuai')              // alasan
            ->assertSee('Siti Pembeli')
            ->assertSee('Jl. Kenanga 12')
            ->assertSee('Tunai');                          // dikembalikan via
    }

    /**
     * Invoice asli TIDAK boleh berubah angkanya — cetakan yang dipegang pembeli
     * harus tetap cocok. Yang ditambahkan hanya penanda + rujukan ke Nota Retur.
     */
    public function test_invoice_diberi_penanda_retur_tanpa_mengubah_angkanya(): void
    {
        $branch = $this->makeBranch();
        $loc    = $this->makeAvailableLocation($branch);
        [$sale, $line] = $this->jualLalu($branch, $loc);

        $this->postJson(route('kasir.history.refund', $sale->id), [
            'reason' => 'Rusak',
            'items'  => [['pos_sale_line_id' => $line->id, 'qty' => 1]],
        ])->assertOk();

        $refund = DB::table('pos_refunds')->where('sale_id', $sale->id)->first();

        $this->get(route('kasir.history.invoice', $sale->id))
            ->assertOk()
            ->assertSee('Total: Rp 30.000,00')                       // angka asli TETAP
            ->assertSee('Sebagian / seluruh barang telah diretur')   // penanda
            ->assertSee('Nota Retur #'.$refund->id)                  // rujukan silang
            ->assertSee('Total diretur: <b>Rp 10.000,00</b>', false)
            ->assertSee('Nilai akhir setelah retur: <b>Rp 20.000,00</b>', false);

        // Nilai nota di database juga tidak boleh berubah.
        $this->assertEqualsWithDelta(30000,
            (float) DB::table('pos_sales')->where('id', $sale->id)->value('total'), 0.01);
    }

    /** Nota tanpa retur tidak boleh memunculkan penanda apa pun. */
    public function test_invoice_tanpa_retur_bersih_dari_penanda(): void
    {
        $branch = $this->makeBranch();
        $loc    = $this->makeAvailableLocation($branch);
        [$sale] = $this->jualLalu($branch, $loc);

        $this->get(route('kasir.history.invoice', $sale->id))
            ->assertOk()
            ->assertDontSee('telah diretur')
            ->assertDontSee('Nota Retur');
    }

    /** Panel detail Riwayat menyertakan data retur supaya UI bisa menampilkannya. */
    public function test_panel_detail_menyertakan_riwayat_retur(): void
    {
        $branch = $this->makeBranch();
        $loc    = $this->makeAvailableLocation($branch);
        [$sale, $line] = $this->jualLalu($branch, $loc);

        $this->postJson(route('kasir.history.refund', $sale->id), [
            'reason' => 'Salah ukuran',
            'items'  => [['pos_sale_line_id' => $line->id, 'qty' => 2]],
        ])->assertOk();

        $refunds = $this->getJson(route('kasir.history.detail', $sale->id))
            ->assertOk()
            ->json('refunds');

        $this->assertCount(1, $refunds);
        $this->assertEqualsWithDelta(20000, (float) $refunds[0]['amount'], 0.01);
        $this->assertSame('Salah ukuran', $refunds[0]['reason']);
        $this->assertCount(1, $refunds[0]['items'], 'Rincian item yang diretur ikut dikirim.');
        $this->assertEquals(2, (int) $refunds[0]['items'][0]['qty']);
    }
}
