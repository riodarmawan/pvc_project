<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class InvoiceDetailTest extends BusinessTestCase
{
    /**
     * Keluhan klien: diskon tidak tertera di nota, cuma total akhir yang terlihat,
     * dan metode pembayaran tidak ada sama sekali.
     */
    public function test_invoice_menampilkan_subtotal_diskon_metode_bayar_dan_alamat(): void
    {
        $branch  = $this->makeBranch();
        $loc     = $this->makeAvailableLocation($branch);
        $kasir   = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 10);

        $customer = (int) DB::table('customers')->insertGetId([
            'name' => 'Budi Pelanggan', 'phone' => '08123', 'address' => 'Jl. Mawar No. 7, Depok',
        ]);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.customer.select'), ['customer_id' => $customer])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'TRANSFER', 'amount' => 18000])->assertOk();
        $this->postJson(route('kasir.finalize'), ['discount' => 2000])->assertOk();

        $sale = DB::table('pos_sales')->where('branch_id', $branch)->first();

        $this->get(route('kasir.history.invoice', $sale->id))
            ->assertOk()
            ->assertSee('Subtotal: Rp 20.000,00')   // bruto sebelum diskon
            ->assertSee('Diskon: - Rp 2.000,00')    // potongan terlihat
            ->assertSee('Total: Rp 18.000,00')
            ->assertSee('Transfer')                 // metode bayar
            ->assertSee('Jl. Mawar No. 7, Depok');  // alamat pengiriman
    }

    /** Nota tanpa diskon tidak perlu menampilkan baris diskon. */
    public function test_invoice_tanpa_diskon_tidak_menampilkan_baris_diskon(): void
    {
        $branch  = $this->makeBranch();
        $loc     = $this->makeAvailableLocation($branch);
        $kasir   = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 10);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 1])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 10000])->assertOk();
        $this->postJson(route('kasir.finalize'))->assertOk();

        $sale = DB::table('pos_sales')->where('branch_id', $branch)->first();

        $this->get(route('kasir.history.invoice', $sale->id))
            ->assertOk()
            ->assertDontSee('Diskon:')
            ->assertSee('Tunai');
    }

    /** Struk yang muncul di layar kasir setelah bayar juga harus menunjukkan diskon. */
    public function test_struk_pos_setelah_finalize_menampilkan_diskon(): void
    {
        $branch  = $this->makeBranch();
        $loc     = $this->makeAvailableLocation($branch);
        $kasir   = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $this->setStock($product, $loc, 10);

        $this->actingAs($kasir);
        $this->postJson(route('kasir.cart.add'), ['product_id' => $product, 'qty' => 2])->assertOk();
        $this->postJson(route('kasir.pay.add'), ['method' => 'CASH', 'amount' => 18000])->assertOk();

        $struk = $this->postJson(route('kasir.finalize'), ['discount' => 2000])
            ->assertOk()->json('invoice_html');

        $this->assertStringContainsString('Subtotal: Rp 20.000,00', $struk);
        $this->assertStringContainsString('Diskon:', $struk);
        $this->assertStringContainsString('Total: <b>Rp 18.000,00</b>', $struk);
        $this->assertStringContainsString('Tunai', $struk, 'Metode bayar pakai label yang manusiawi.');
    }

    /**
     * Keluhan klien: search pelanggan tidak memunculkan apa-apa, jadi kontak
     * pembeli ditulis ulang terus. Hasil dari server dulu dibuang template.
     */
    public function test_pencarian_pelanggan_di_checkout_mengembalikan_hasil(): void
    {
        $branch = $this->makeBranch();
        $kasir  = $this->makeUser(3, $branch);

        DB::table('customers')->insert([
            'name' => 'KA KIKI', 'phone' => '081212349228', 'address' => 'Jl. Melati 3',
        ]);

        $res = $this->actingAs($kasir)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('kasir.checkout', ['cq' => 'KIKI']))
            ->assertOk()
            ->json();

        $html = $res['html']['customer'] ?? '';
        $this->assertStringContainsString('KA KIKI', $html, 'Nama pelanggan harus muncul di hasil.');
        $this->assertStringContainsString('081212349228', $html, 'Nomor telepon ikut ditampilkan.');
        $this->assertStringContainsString('checkoutSelectCustomer', $html, 'Hasil harus bisa diklik untuk dipilih.');
    }

    /** Pencarian tanpa hasil memberi pesan, bukan dropdown kosong tanpa keterangan. */
    public function test_pencarian_pelanggan_tanpa_hasil_memberi_pesan(): void
    {
        $branch = $this->makeBranch();
        $kasir  = $this->makeUser(3, $branch);

        $res = $this->actingAs($kasir)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('kasir.checkout', ['cq' => 'zzzTidakAdaZzz']))
            ->assertOk()
            ->json();

        $this->assertStringContainsString('tidak ditemukan', $res['html']['customer'] ?? '');
    }
}
