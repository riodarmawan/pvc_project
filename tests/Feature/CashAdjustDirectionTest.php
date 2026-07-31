<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class CashAdjustDirectionTest extends BusinessTestCase
{
    /**
     * Jurnal SETOR_BANK selalu DR Bank / CR Kas (kas turun) apapun arah yang dipilih user.
     * Arah yang tersimpan di cash_movements harus ikut OUT, kalau tidak saldo kas &
     * Rekonsiliasi Kas melenceng dua kali lipat dari nominalnya.
     */
    public function test_setor_bank_selalu_tercatat_out_meski_user_salah_pilih_in(): void
    {
        $branch = $this->makeBranch();
        $kasir  = $this->makeUser(3, $branch);

        $this->actingAs($kasir)->post(route('kasir.cash.adjust'), [
            'direction' => 'IN',           // salah pilih — setor ke bank itu kas keluar
            'category'  => 'SETOR_BANK',
            'amount'    => 500000,
            'memo'      => 'Setor ke bank',
        ])->assertRedirect();

        $mv = DB::table('cash_movements')->where('branch_id', $branch)->first();
        $this->assertSame('OUT', $mv->direction,
            'SETOR_BANK harus tersimpan sebagai OUT agar konsisten dengan jurnalnya.');

        // GL: kas turun 500rb, bank naik 500rb.
        $this->assertJournalsBalanced();
        $acc = $this->accountTotals();
        $this->assertEqualsWithDelta(500000, $acc['1100']['credit'], 0.01);
        $this->assertEqualsWithDelta(500000, $acc['1110']['debit'], 0.01);
    }

    /** Kebalikannya: TARIK_BANK selalu menambah kas, jadi arah tersimpan harus IN. */
    public function test_tarik_bank_selalu_tercatat_in_meski_user_salah_pilih_out(): void
    {
        $branch = $this->makeBranch();
        $kasir  = $this->makeUser(3, $branch);

        $this->actingAs($kasir)->post(route('kasir.cash.adjust'), [
            'direction' => 'OUT',          // salah pilih — tarik dari bank itu kas masuk
            'category'  => 'TARIK_BANK',
            'amount'    => 300000,
            'memo'      => 'Tarik tunai',
        ])->assertRedirect();

        $mv = DB::table('cash_movements')->where('branch_id', $branch)->first();
        $this->assertSame('IN', $mv->direction);

        $this->assertJournalsBalanced();
        $acc = $this->accountTotals();
        $this->assertEqualsWithDelta(300000, $acc['1100']['debit'], 0.01);
        $this->assertEqualsWithDelta(300000, $acc['1110']['credit'], 0.01);
    }

    /** OPENING & LAINNYA arahnya memang bermakna — pilihan user tetap dihormati. */
    public function test_kategori_non_bank_tetap_menghormati_arah_pilihan_user(): void
    {
        $branch = $this->makeBranch();
        $kasir  = $this->makeUser(3, $branch);

        $this->actingAs($kasir)->post(route('kasir.cash.adjust'), [
            'direction' => 'IN',
            'category'  => 'OPENING',
            'amount'    => 1000000,
            'memo'      => 'Saldo awal',
        ])->assertRedirect();

        $mv = DB::table('cash_movements')->where('branch_id', $branch)->first();
        $this->assertSame('IN', $mv->direction);

        $acc = $this->accountTotals();
        $this->assertEqualsWithDelta(1000000, $acc['1100']['debit'], 0.01); // kas naik
        $this->assertEqualsWithDelta(1000000, $acc['3100']['credit'], 0.01); // dari modal
    }
}
