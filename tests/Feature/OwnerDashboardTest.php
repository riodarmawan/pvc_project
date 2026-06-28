<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class OwnerDashboardTest extends BusinessTestCase
{
    public function test_aktivitas_terbaru_memuat_penjualan_yang_sudah_lunas(): void
    {
        $branch = $this->makeBranch();
        $owner = $this->makeUser(1, $branch);
        $kasir = $this->makeUser(3, $branch);

        $saleId = (int) DB::table('pos_sales')->insertGetId([
            'branch_id' => $branch, 'cashier_id' => $kasir->id,
            'sale_datetime' => now(), 'status' => 'PAID', 'total' => 50000,
        ]);

        $response = $this->actingAs($owner)->get(route('owner.home'));
        $response->assertOk();

        $aktivitas = collect($response->viewData('aktivitas'));
        $penjualan = $aktivitas->firstWhere('id', $saleId);
        $this->assertNotNull($penjualan, 'Penjualan PAID harus muncul di aktivitas terbaru.');
        $this->assertSame('penjualan', $penjualan->type);
    }
}
