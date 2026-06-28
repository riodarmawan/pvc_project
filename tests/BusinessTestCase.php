<?php

namespace Tests;

use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use App\Models\User;

/**
 * Base untuk test alur bisnis. Memakai DatabaseTransactions agar setiap test
 * dibungkus transaksi & di-rollback (skema final_pvc_test tetap utuh).
 */
abstract class BusinessTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountSeeder::class);
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'name' => 'OWNER'],
            ['id' => 2, 'name' => 'KC'],
            ['id' => 3, 'name' => 'KASIR'],
        ]);
    }

    protected function makeBranch(string $code = null): int
    {
        return (int) DB::table('branches')->insertGetId([
            'code' => $code ?: 'BR'.substr(uniqid(), -6),
            'name' => 'Cabang Test',
            'is_active' => 1,
        ]);
    }

    protected function makeAvailableLocation(int $branchId): int
    {
        return (int) DB::table('stock_locations')->insertGetId([
            'branch_id' => $branchId, 'code' => 'AVL'.substr(uniqid(), -5),
            'name' => 'Available', 'type' => 'AVAILABLE',
        ]);
    }

    protected function makeUser(int $roleId, int $branchId): User
    {
        $id = (int) DB::table('users')->insertGetId([
            'username' => 'u'.uniqid(),
            'password_hash' => bcrypt('secret'),
            'full_name' => 'User Test',
            'role_id' => $roleId,
            'default_branch_id' => $branchId,
            'is_active' => 1,
        ]);
        return User::find($id);
    }

    protected function makeProduct(array $attrs = []): int
    {
        $catId = (int) DB::table('product_categories')->insertGetId([
            'code' => 'C'.substr(uniqid(), -6), 'name' => 'Cat',
        ]);
        $uomId = (int) DB::table('uoms')->insertGetId([
            'code' => 'U'.substr(uniqid(), -6), 'name' => 'Pcs',
        ]);
        return (int) DB::table('products')->insertGetId(array_merge([
            'sku' => 'SKU'.substr(uniqid(), -8), 'name' => 'Produk',
            'category_id' => $catId, 'uom_id' => $uomId,
            'hpp' => 6000, 'selling_price' => 10000, 'track_by_meter' => 0, 'is_active' => 1,
        ], $attrs));
    }

    protected function makeCustomer(string $name = 'Pelanggan Test'): int
    {
        return (int) DB::table('customers')->insertGetId(['name' => $name]);
    }

    protected function productUom(int $productId): int
    {
        return (int) DB::table('products')->where('id', $productId)->value('uom_id');
    }

    protected function setStock(int $productId, int $locationId, float $qty): void
    {
        DB::table('stock_quants')->insert([
            'product_id' => $productId, 'location_id' => $locationId, 'qty' => $qty,
        ]);
    }

    protected function stockQty(int $productId, int $locationId): float
    {
        return (float) DB::table('stock_quants')
            ->where('product_id', $productId)->where('location_id', $locationId)->value('qty');
    }

    /** Saldo debit-kredit per akun (code => [debit, credit]) untuk semua jurnal. */
    protected function accountTotals(): array
    {
        $rows = DB::table('journal_entry_lines as l')
            ->join('chart_of_accounts as c', 'c.id', '=', 'l.account_id')
            ->selectRaw('c.code, ROUND(SUM(l.debit),2) d, ROUND(SUM(l.credit),2) c2')
            ->groupBy('c.code')->get();
        $out = [];
        foreach ($rows as $r) {
            $out[$r->code] = ['debit' => (float) $r->d, 'credit' => (float) $r->c2];
        }
        return $out;
    }

    /** Pastikan setiap journal entry seimbang (debit == credit). */
    protected function assertJournalsBalanced(): void
    {
        $rows = DB::table('journal_entry_lines')
            ->selectRaw('journal_entry_id, ROUND(SUM(debit),2) d, ROUND(SUM(credit),2) c')
            ->groupBy('journal_entry_id')->get();
        $this->assertNotEmpty($rows, 'Tidak ada jurnal yang terbentuk.');
        foreach ($rows as $r) {
            $this->assertEqualsWithDelta(
                $r->d, $r->c, 0.01,
                "Jurnal #{$r->journal_entry_id} tidak seimbang (D={$r->d}, C={$r->c})."
            );
        }
    }
}
