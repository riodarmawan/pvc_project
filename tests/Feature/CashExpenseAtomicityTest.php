<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class CashExpenseAtomicityTest extends BusinessTestCase
{
    public function test_kegagalan_jurnal_membatalkan_pencatatan_kas_keluar(): void
    {
        $branch = $this->makeBranch();
        $kasir = $this->makeUser(3, $branch);

        // Rusak COA beban -> jurnal pengeluaran akan gagal.
        DB::table('chart_of_accounts')->where('code', '5200')->delete();

        $this->actingAs($kasir);
        try {
            $this->post(route('kasir.cash.out'), [
                'amount' => 50000, 'category' => 'BBM', 'memo' => 'Isi bensin',
            ]);
        } catch (\Throwable $e) {
            // exception jurnal — diharapkan
        }

        $this->assertSame(0, DB::table('cash_movements')->where('branch_id', $branch)->count(),
            'Mutasi kas tidak boleh tersimpan bila jurnal gagal.');
    }
}
