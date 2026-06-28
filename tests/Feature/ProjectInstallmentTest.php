<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class ProjectInstallmentTest extends BusinessTestCase
{
    public function test_cicilan_tagihan_proyek_membukukan_piutang_dan_penerimaan_kas(): void
    {
        $branch = $this->makeBranch();
        $kasir = $this->makeUser(3, $branch);
        $customer = $this->makeCustomer();

        $projectId = (int) DB::table('projects')->insertGetId([
            'branch_id' => $branch, 'customer_id' => $customer, 'code' => 'PRJ-T1',
            'title' => 'Proyek Cicilan', 'status' => 'READY_TO_BILL',
            'created_by' => $kasir->id, 'created_at' => now(),
        ]);
        DB::table('project_services')->insert([
            'project_id' => $projectId, 'name' => 'Jasa Pasang', 'price' => 5000, 'created_at' => now(),
        ]);

        $this->actingAs($kasir);

        // Cicilan pertama: 2000 dari 5000
        $this->post(route('projects.bill.pay.add', $projectId), ['method' => 'CASH', 'amount' => 2000]);

        $sale = DB::table('pos_sales')->where('project_id', $projectId)->first();
        $this->assertNotNull($sale);
        $this->assertEqualsWithDelta(5000, (float) $sale->total, 0.01);
        $this->assertSame('DRAFT', $sale->status, 'Belum lunas → tetap DRAFT.');

        $acc = $this->accountTotals();
        $this->assertEqualsWithDelta(5000, $acc['1200']['debit'], 0.01);  // Piutang billing
        $this->assertEqualsWithDelta(5000, $acc['4200']['credit'], 0.01); // Pendapatan jasa
        $this->assertEqualsWithDelta(2000, $acc['1100']['debit'], 0.01);  // Kas masuk cicilan 1
        $this->assertEqualsWithDelta(2000, $acc['1200']['credit'], 0.01); // Piutang berkurang
        $this->assertJournalsBalanced();

        // Cicilan kedua: pelunasan 3000
        $this->post(route('projects.bill.pay.add', $projectId), ['method' => 'CASH', 'amount' => 3000]);

        $sale = DB::table('pos_sales')->where('project_id', $projectId)->first();
        $this->assertSame('PAID', $sale->status, 'Lunas → PAID.');

        $acc = $this->accountTotals();
        $this->assertEqualsWithDelta(5000, $acc['1100']['debit'], 0.01);  // total kas masuk
        $this->assertEqualsWithDelta(5000, $acc['1200']['credit'], 0.01); // piutang lunas (net 0)
        $this->assertJournalsBalanced();
    }
}
