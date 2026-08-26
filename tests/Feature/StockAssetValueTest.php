<?php

namespace Tests\Feature;

use Tests\BusinessTestCase;

class StockAssetValueTest extends BusinessTestCase
{
    /**
     * Keluhan klien: "total keseluruhan uang / aset seluruh barang lihatnya di mana?"
     * Laporan Stok dulu cuma menampilkan kuantitas, tanpa nilai rupiah sama sekali.
     */
    public function test_laporan_stok_menampilkan_nilai_aset_berdasarkan_hpp(): void
    {
        $branch = $this->makeBranch();
        $loc    = $this->makeAvailableLocation($branch);
        $owner  = $this->makeUser(1, $branch);

        $a = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);
        $b = $this->makeProduct(['hpp' => 2500, 'selling_price' => 4000]);
        $this->setStock($a, $loc, 10);   // 60.000
        $this->setStock($b, $loc, 4);    // 10.000

        $ringkasan = $this->actingAs($owner)
            ->get(route('reports.stock.index', ['branch_id' => $branch]))
            ->assertOk()
            ->viewData('ringkasan');

        $this->assertEqualsWithDelta(70000, (float) $ringkasan->total_nilai, 0.01,
            'Nilai aset = (10 x 6.000) + (4 x 2.500).');
        $this->assertEqualsWithDelta(14, (float) $ringkasan->total_qty, 0.01);
        $this->assertEquals(0, (int) $ringkasan->tanpa_hpp);
    }

    /**
     * Produk tanpa HPP dihitung nol dan dilaporkan terpisah — supaya nilai aset yang
     * kelihatan rendah bisa dibedakan antara "stok memang sedikit" dan "master data
     * belum lengkap". Audit sebelumnya menemukan ada produk aktif tanpa HPP.
     */
    public function test_produk_tanpa_hpp_dihitung_nol_dan_ditandai(): void
    {
        $branch = $this->makeBranch();
        $loc    = $this->makeAvailableLocation($branch);
        $owner  = $this->makeUser(1, $branch);

        $adaHpp   = $this->makeProduct(['hpp' => 5000, 'selling_price' => 9000]);
        $tanpaHpp = $this->makeProduct(['hpp' => 0, 'selling_price' => 9000]);
        $this->setStock($adaHpp, $loc, 2);     // 10.000
        $this->setStock($tanpaHpp, $loc, 100); // 0

        $ringkasan = $this->actingAs($owner)
            ->get(route('reports.stock.index', ['branch_id' => $branch]))
            ->assertOk()
            ->viewData('ringkasan');

        $this->assertEqualsWithDelta(10000, (float) $ringkasan->total_nilai, 0.01);
        $this->assertEquals(1, (int) $ringkasan->tanpa_hpp,
            'Ada 1 baris stok yang HPP-nya belum diisi.');
    }
}
