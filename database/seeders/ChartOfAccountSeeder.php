<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // ASET
            ['code' => '1100', 'name' => 'Kas',                'type' => 'ASSET'],
            ['code' => '1200', 'name' => 'Piutang Usaha',      'type' => 'ASSET'],
            ['code' => '1300', 'name' => 'Persediaan Barang',  'type' => 'ASSET'],

            // KEWAJIBAN
            ['code' => '2100', 'name' => 'Utang Dagang',       'type' => 'LIABILITY'],

            // MODAL
            ['code' => '3100', 'name' => 'Modal Usaha',        'type' => 'EQUITY'],
            ['code' => '3200', 'name' => 'Laba Ditahan',       'type' => 'EQUITY'],

            // PENDAPATAN
            ['code' => '4100', 'name' => 'Penjualan Barang',   'type' => 'REVENUE'],
            ['code' => '4200', 'name' => 'Pendapatan Jasa',    'type' => 'REVENUE'],
            ['code' => '4900', 'name' => 'Retur Penjualan',    'type' => 'REVENUE'],

            // BEBAN
            ['code' => '5100', 'name' => 'Harga Pokok Penjualan', 'type' => 'EXPENSE'],
            ['code' => '5200', 'name' => 'Beban Operasional',     'type' => 'EXPENSE'],
        ];

        foreach ($accounts as $acc) {
            DB::table('chart_of_accounts')->updateOrInsert(
                ['code' => $acc['code']],
                array_merge($acc, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
