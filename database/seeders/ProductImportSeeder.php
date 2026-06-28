<?php

/**
 * Seeder: Import data dari PENJUALAN ALINA DEPOK JUNI 2026.xlsx
 *
 * Jalankan: php artisan db:seed --class=ProductImportSeeder
 *
 * Yang dibuat:
 *  1. Branch DEPOK
 *  2. 8 Product Categories
 *  3. 2 UOMs (MTR, PCS)
 *  4. Stock locations (AVAILABLE + STORE) untuk branch DEPOK
 *  5. 94 Products dari Excel
 *  6. Stock quants (stok awal) di location AVAILABLE
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductImportSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        try {
            $branchId   = $this->createBranch();
            $locationIds = $this->createLocations($branchId);
            $categories  = $this->createCategories();
            $uoms        = $this->createUoms();
            $this->createProducts($categories, $uoms, $locationIds);

            DB::commit();
            $this->command?->info("✅ Import selesai! Branch DEPOK + 94 produk + stok.");
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /* ======================== STEP 1: Branch ======================== */

    private function createBranch(): int
    {
        $id = DB::table('branches')->insertGetId([
            'code'       => 'DEPOK',
            'name'       => 'Depok',
            'address'    => 'Jl. Raya Depok, Jawa Barat',
            'is_active'  => 1,
        ]);
        $this->command?->info("  Branch DEPOK (id={$id})");
        return $id;
    }

    /* ======================== STEP 2: Stock Locations ======================== */

    private function createLocations(int $branchId): array
    {
        $availableId = DB::table('stock_locations')->insertGetId([
            'branch_id' => $branchId,
            'code'      => 'AVL',
            'name'      => 'Available Stock',
            'type'      => 'AVAILABLE',
        ]);

        $storeId = DB::table('stock_locations')->insertGetId([
            'branch_id' => $branchId,
            'code'      => 'STR',
            'name'      => 'Store / Warehouse',
            'type'      => 'STORE',
        ]);

        $this->command?->info("  Stock locations: AVAILABLE(id={$availableId}), STORE(id={$storeId})");
        return ['available' => $availableId, 'store' => $storeId];
    }

    /* ======================== STEP 3: Categories ======================== */

    private function createCategories(): array
    {
        $cats = [
            ['code' => 'SC',    'name' => 'SI CANTIK'],
            ['code' => 'SUMA',  'name' => 'SUMA'],
            ['code' => 'TC',    'name' => 'TIAN CHENG'],
            ['code' => 'WPC',   'name' => 'WPC'],
            ['code' => 'LH',    'name' => 'LIST & HOLO'],
            ['code' => 'TACO',  'name' => 'TACO'],
            ['code' => 'KB',    'name' => 'KANG BANG'],
            ['code' => 'RIJEK', 'name' => 'RIJEK'],
        ];

        $map = [];
        foreach ($cats as $cat) {
            $id = DB::table('product_categories')->insertGetId([
                'code' => $cat['code'],
                'name' => $cat['name'],
            ]);
            $map[$cat['name']] = $id;
        }

        $this->command?->info("  Categories: " . implode(', ', array_keys($map)));
        return $map;
    }

    /* ======================== STEP 4: UOMs ======================== */

    private function createUoms(): array
    {
        $uoms = [
            ['code' => 'MTR', 'name' => 'Meter'],
            ['code' => 'PCS', 'name' => 'Piece'],
            ['code' => 'SET', 'name' => 'Set'],
        ];

        $map = [];
        foreach ($uoms as $uom) {
            $id = DB::table('uoms')->insertGetId($uom);
            $map[$uom['code']] = $id;
        }

        $this->command?->info("  UOMs: MTR, PCS, SET");
        return $map;
    }

    /* ======================== STEP 5: Products + Stock ======================== */

    private function createProducts(array $categories, array $uoms, array $locationIds): void
    {
        $products = $this->getProductData();
        $created = 0;

        foreach ($products as $p) {
            $catId   = $categories[$p['category']] ?? $categories['RIJEK'];
            $uomId   = $uoms['MTR'];
            $name    = $p['name'] ?: $p['category'];
            $sku     = $p['sku'];
            $hpp     = $p['hpp'];
            $sell    = $hpp > 0 ? round($hpp * 1.3, 0) : null; // margin 30%
            $stock   = $p['stock'];
            $lenCm   = $p['length_cm'];

            // Insert product
            $productId = DB::table('products')->insertGetId([
                'sku'            => $sku,
                'name'           => $name,
                'category_id'    => $catId,
                'uom_id'         => $uomId,
                'hpp'            => $hpp,
                'selling_price'  => $sell,
                'length_cm'      => $lenCm,
                'track_by_meter' => ($lenCm && $lenCm > 0) ? 1 : 0,
                'is_active'      => 1,
                'notes'          => "Imported from Excel - {$p['category']} - {$p['dimension']}",
            ]);

            // Insert stock quant (stok awal di AVAILABLE location)
            if ($stock > 0) {
                DB::table('stock_quants')->insert([
                    'product_id'  => $productId,
                    'location_id' => $locationIds['available'],
                    'qty'         => $stock,
                ]);
            }

            $created++;
        }

        $this->command?->info("  Products created: {$created}");
    }

    /* ======================== DATA dari EXCEL ======================== */

    private function getProductData(): array
    {
        return [
            // === SI CANTIK ===
            ['sku'=>'SC 5100',   'name'=>'SI CANTIK',    'category'=>'SI CANTIK',    'dimension'=>'4mtr', 'length_cm'=>400, 'stock'=>112,  'hpp'=>30000],
            ['sku'=>'SC 5001',   'name'=>'SI CANTIK',    'category'=>'SI CANTIK',    'dimension'=>'4mtr', 'length_cm'=>400, 'stock'=>619,  'hpp'=>30000],
            ['sku'=>'SC 5008B',  'name'=>'SI CANTIK',    'category'=>'SI CANTIK',    'dimension'=>'4mtr', 'length_cm'=>400, 'stock'=>278,  'hpp'=>30000],
            ['sku'=>'SC 5036',   'name'=>'SI CANTIK',    'category'=>'SI CANTIK',    'dimension'=>'4mtr', 'length_cm'=>400, 'stock'=>135,  'hpp'=>30000],
            ['sku'=>'SC 5107',   'name'=>'SI CANTIK',    'category'=>'SI CANTIK',    'dimension'=>'4mtr', 'length_cm'=>400, 'stock'=>3,    'hpp'=>30000],
            ['sku'=>'SC 5048',   'name'=>'SI CANTIK',    'category'=>'SI CANTIK',    'dimension'=>'4mtr', 'length_cm'=>400, 'stock'=>1,    'hpp'=>30000],
            ['sku'=>'SC 5001-6', 'name'=>'SI CANTIK',    'category'=>'SI CANTIK',    'dimension'=>'6mtr', 'length_cm'=>600, 'stock'=>478,  'hpp'=>45000],
            ['sku'=>'SC 802',    'name'=>'SI CANTIK',    'category'=>'SI CANTIK',    'dimension'=>'4mtr', 'length_cm'=>400, 'stock'=>60,   'hpp'=>30000],

            // === SUMA ===
            ['sku'=>'D 2001',    'name'=>'SUMA',         'category'=>'SUMA',         'dimension'=>'4mtr', 'length_cm'=>400, 'stock'=>209,  'hpp'=>32000],

            // === TIAN CHENG ===
            ['sku'=>'7068',         'name'=>'Putih Dove',       'category'=>'TIAN CHENG', 'dimension'=>'4mtr', 'length_cm'=>400, 'stock'=>9,   'hpp'=>36000],
            ['sku'=>'EP 8060 M2',   'name'=>'8mm',              'category'=>'TIAN CHENG', 'dimension'=>'6mtr', 'length_cm'=>600, 'stock'=>150, 'hpp'=>57000],
            ['sku'=>'SCL 733',      'name'=>'Dove',             'category'=>'TIAN CHENG', 'dimension'=>'6mtr', 'length_cm'=>600, 'stock'=>40,  'hpp'=>54000],
            ['sku'=>'SC 702 NS',    'name'=>'Glossy',           'category'=>'TIAN CHENG', 'dimension'=>'6mtr', 'length_cm'=>600, 'stock'=>173, 'hpp'=>48000],
            ['sku'=>'SCL 730 NAT',  'name'=>'Dove',             'category'=>'TIAN CHENG', 'dimension'=>'6mtr', 'length_cm'=>600, 'stock'=>170, 'hpp'=>54000],
            ['sku'=>'SCL 732 NAT',  'name'=>'Dove',             'category'=>'TIAN CHENG', 'dimension'=>'6mtr', 'length_cm'=>600, 'stock'=>170, 'hpp'=>54000],
            ['sku'=>'SCL 731',      'name'=>'Dove',             'category'=>'TIAN CHENG', 'dimension'=>'6mtr', 'length_cm'=>600, 'stock'=>170, 'hpp'=>54000],
            ['sku'=>'SC 701 NS',    'name'=>'Glossy',           'category'=>'TIAN CHENG', 'dimension'=>'6mtr', 'length_cm'=>600, 'stock'=>0,   'hpp'=>45000],

            // === WPC ===
            ['sku'=>'160-03',    'name'=>'WPC',          'category'=>'WPC',          'dimension'=>'3mtr', 'length_cm'=>300, 'stock'=>35,   'hpp'=>49000],
            ['sku'=>'160-06',    'name'=>'WPC',          'category'=>'WPC',          'dimension'=>'3mtr', 'length_cm'=>300, 'stock'=>42,   'hpp'=>49000],
            ['sku'=>'160-07',    'name'=>'WPC',          'category'=>'WPC',          'dimension'=>'3mtr', 'length_cm'=>300, 'stock'=>7,    'hpp'=>49000],
            ['sku'=>'160-15',    'name'=>'WPC',          'category'=>'WPC',          'dimension'=>'3mtr', 'length_cm'=>300, 'stock'=>197,  'hpp'=>49000],
            ['sku'=>'160-19',    'name'=>'WPC',          'category'=>'WPC',          'dimension'=>'3mtr', 'length_cm'=>300, 'stock'=>1,    'hpp'=>49000],
            ['sku'=>'160-22',    'name'=>'WPC',          'category'=>'WPC',          'dimension'=>'3mtr', 'length_cm'=>300, 'stock'=>7,    'hpp'=>49000],

            // === LIST & HOLO ===
            ['sku'=>'A COKLAT',      'name'=>'LIST',              'category'=>'LIST & HOLO', 'dimension'=>'',  'length_cm'=>null, 'stock'=>30,   'hpp'=>0],
            ['sku'=>'TD 02 BG',      'name'=>'LIST',              'category'=>'LIST & HOLO', 'dimension'=>'',  'length_cm'=>null, 'stock'=>124,  'hpp'=>24000],
            ['sku'=>'TD 01',         'name'=>'LIST',              'category'=>'LIST & HOLO', 'dimension'=>'',  'length_cm'=>null, 'stock'=>135,  'hpp'=>24000],
            ['sku'=>'TD 03',         'name'=>'LIST',              'category'=>'LIST & HOLO', 'dimension'=>'',  'length_cm'=>null, 'stock'=>67,   'hpp'=>24000],
            ['sku'=>'TD 02',         'name'=>'LIST',              'category'=>'LIST & HOLO', 'dimension'=>'',  'length_cm'=>null, 'stock'=>34,   'hpp'=>24000],
            ['sku'=>'TD 04',         'name'=>'LIST',              'category'=>'LIST & HOLO', 'dimension'=>'',  'length_cm'=>null, 'stock'=>66,   'hpp'=>24000],
            ['sku'=>'TD 01 BG',      'name'=>'LIST',              'category'=>'LIST & HOLO', 'dimension'=>'',  'length_cm'=>null, 'stock'=>16,   'hpp'=>24000],
            ['sku'=>'TD 05',         'name'=>'LIST',              'category'=>'LIST & HOLO', 'dimension'=>'',  'length_cm'=>null, 'stock'=>35,   'hpp'=>24000],
            ['sku'=>'H 8033',        'name'=>'HOLO',              'category'=>'LIST & HOLO', 'dimension'=>'3mtr','length_cm'=>300, 'stock'=>287,  'hpp'=>35000],
            ['sku'=>'H 8033-6',      'name'=>'HOLO',              'category'=>'LIST & HOLO', 'dimension'=>'6mtr','length_cm'=>600, 'stock'=>18,   'hpp'=>65000],
            ['sku'=>'H 8030',        'name'=>'HOLO',              'category'=>'LIST & HOLO', 'dimension'=>'3mtr','length_cm'=>300, 'stock'=>422,  'hpp'=>35000],
            ['sku'=>'H 8030-6',      'name'=>'HOLO',              'category'=>'LIST & HOLO', 'dimension'=>'6mtr','length_cm'=>600, 'stock'=>33,   'hpp'=>65000],
            ['sku'=>'H 8027',        'name'=>'HOLO',              'category'=>'LIST & HOLO', 'dimension'=>'3mtr','length_cm'=>300, 'stock'=>83,   'hpp'=>35000],
            ['sku'=>'H 8025',        'name'=>'HOLO',              'category'=>'LIST & HOLO', 'dimension'=>'3mtr','length_cm'=>300, 'stock'=>30,   'hpp'=>35000],
            ['sku'=>'S 8025',        'name'=>'SIKU',              'category'=>'LIST & HOLO', 'dimension'=>'3mtr','length_cm'=>300, 'stock'=>24,   'hpp'=>35000],
            ['sku'=>'S 8030',        'name'=>'SIKU',              'category'=>'LIST & HOLO', 'dimension'=>'3mtr','length_cm'=>300, 'stock'=>32,   'hpp'=>35000],
            ['sku'=>'S 8025-6',      'name'=>'SIKU',              'category'=>'LIST & HOLO', 'dimension'=>'6mtr','length_cm'=>600, 'stock'=>18,   'hpp'=>65000],
            ['sku'=>'S 8030-6',      'name'=>'SIKU',              'category'=>'LIST & HOLO', 'dimension'=>'6mtr','length_cm'=>600, 'stock'=>12,   'hpp'=>65000],
            ['sku'=>'H 8030-4',      'name'=>'HOLO',              'category'=>'LIST & HOLO', 'dimension'=>'4mtr','length_cm'=>400, 'stock'=>46,   'hpp'=>45000],
            ['sku'=>'H 8025-4',      'name'=>'HOLO',              'category'=>'LIST & HOLO', 'dimension'=>'4mtr','length_cm'=>400, 'stock'=>40,   'hpp'=>45000],
            ['sku'=>'S 8025-4',      'name'=>'SIKU',              'category'=>'LIST & HOLO', 'dimension'=>'4mtr','length_cm'=>400, 'stock'=>40,   'hpp'=>45000],
            ['sku'=>'S 8030-4',      'name'=>'SIKU',              'category'=>'LIST & HOLO', 'dimension'=>'4mtr','length_cm'=>400, 'stock'=>54,   'hpp'=>45000],
            ['sku'=>'LIST A',        'name'=>'LIST A',            'category'=>'LIST & HOLO', 'dimension'=>'',   'length_cm'=>null,'stock'=>144,  'hpp'=>22000],
            ['sku'=>'LIST B',        'name'=>'LIST B',            'category'=>'LIST & HOLO', 'dimension'=>'',   'length_cm'=>null,'stock'=>86,   'hpp'=>22000],
            ['sku'=>'LIST C',        'name'=>'LIST C',            'category'=>'LIST & HOLO', 'dimension'=>'',   'length_cm'=>null,'stock'=>181,  'hpp'=>22000],
            ['sku'=>'SILEN A',       'name'=>'SILEN',             'category'=>'LIST & HOLO', 'dimension'=>'',   'length_cm'=>null,'stock'=>425,  'hpp'=>12000],
            ['sku'=>'SILEN B',       'name'=>'SILEN',             'category'=>'LIST & HOLO', 'dimension'=>'',   'length_cm'=>null,'stock'=>436,  'hpp'=>12000],
            ['sku'=>'SILEN C',       'name'=>'SILEN',             'category'=>'LIST & HOLO', 'dimension'=>'',   'length_cm'=>null,'stock'=>54,   'hpp'=>12000],
            ['sku'=>'SKRUP',         'name'=>'SKRUP',             'category'=>'LIST & HOLO', 'dimension'=>'',   'length_cm'=>null,'stock'=>3800, 'hpp'=>150],
            ['sku'=>'SEALANT P',     'name'=>'Sealant Putih',     'category'=>'LIST & HOLO', 'dimension'=>'',   'length_cm'=>null,'stock'=>165,  'hpp'=>17000],
            ['sku'=>'SEALANT B',     'name'=>'Sealant Bening',    'category'=>'LIST & HOLO', 'dimension'=>'',   'length_cm'=>null,'stock'=>48,   'hpp'=>17000],
            ['sku'=>'PAKU BETON 2',  'name'=>'Paku Beton',        'category'=>'LIST & HOLO', 'dimension'=>'2in','length_cm'=>null,'stock'=>3,    'hpp'=>40000],
            ['sku'=>'PAKU BETON 1',  'name'=>'Paku Beton',        'category'=>'LIST & HOLO', 'dimension'=>'1in','length_cm'=>null,'stock'=>9.5,  'hpp'=>40000],
            ['sku'=>'KLIP WPC',      'name'=>'Klip WPC',          'category'=>'LIST & HOLO', 'dimension'=>'',   'length_cm'=>null,'stock'=>1200, 'hpp'=>200],

            // === TACO ===
            ['sku'=>'SK 001',        'name'=>'Snow Birch',              'category'=>'TACO', 'dimension'=>'5 mtr', 'length_cm'=>500, 'stock'=>1,   'hpp'=>50000],
            ['sku'=>'HK 005',        'name'=>'Iron Walnut',             'category'=>'TACO', 'dimension'=>'4 mtr', 'length_cm'=>400, 'stock'=>120, 'hpp'=>52000],
            ['sku'=>'HK 005 NP',     'name'=>'Iron Walnut Nat Polos',   'category'=>'TACO', 'dimension'=>'4 mtr', 'length_cm'=>400, 'stock'=>207, 'hpp'=>52000],
            ['sku'=>'SK 003 NG',     'name'=>'River Oak Nat Gold',      'category'=>'TACO', 'dimension'=>'4 mtr', 'length_cm'=>400, 'stock'=>100, 'hpp'=>40000],
            ['sku'=>'HK 005-4',      'name'=>'Iron Walnut',             'category'=>'TACO', 'dimension'=>'4mtr',  'length_cm'=>400, 'stock'=>15,  'hpp'=>52000],
            ['sku'=>'SK 001-6',      'name'=>'Snow Birch',              'category'=>'TACO', 'dimension'=>'6 mtr', 'length_cm'=>600, 'stock'=>1,   'hpp'=>75000],
            ['sku'=>'HK 005-6',      'name'=>'Iron Walnut',             'category'=>'TACO', 'dimension'=>'6 mtr', 'length_cm'=>600, 'stock'=>40,  'hpp'=>78000],
            ['sku'=>'HK 005NP-6',    'name'=>'Iron Walnut Nat',         'category'=>'TACO', 'dimension'=>'6 mtr', 'length_cm'=>600, 'stock'=>20,  'hpp'=>78000],
            ['sku'=>'SK 002',        'name'=>'Ash Wood',                'category'=>'TACO', 'dimension'=>'6 mtr', 'length_cm'=>600, 'stock'=>5,   'hpp'=>60000],
            ['sku'=>'SK 002NG',      'name'=>'Ash Wood Nat Gold',       'category'=>'TACO', 'dimension'=>'6 mtr', 'length_cm'=>600, 'stock'=>13,  'hpp'=>60000],
            ['sku'=>'SK 003',        'name'=>'River Oak',               'category'=>'TACO', 'dimension'=>'6 mtr', 'length_cm'=>600, 'stock'=>8,   'hpp'=>60000],
            ['sku'=>'SK 003NG',      'name'=>'River Oak Nat Gold',      'category'=>'TACO', 'dimension'=>'6 mtr', 'length_cm'=>600, 'stock'=>95,  'hpp'=>60000],
            ['sku'=>'SK 004',        'name'=>'Sheen Teak',              'category'=>'TACO', 'dimension'=>'6 mtr', 'length_cm'=>600, 'stock'=>40,  'hpp'=>60000],
            ['sku'=>'SK 005NG',      'name'=>'Red Mahagony Nat Gold',   'category'=>'TACO', 'dimension'=>'6 mtr', 'length_cm'=>600, 'stock'=>99,  'hpp'=>60000],
            ['sku'=>'HK 004 NP',     'name'=>'Rustic Cherry',           'category'=>'TACO', 'dimension'=>'6mtr',  'length_cm'=>600, 'stock'=>0,   'hpp'=>78000],
            ['sku'=>'HK 002',        'name'=>'Retro Oak',               'category'=>'TACO', 'dimension'=>'6 mtr', 'length_cm'=>600, 'stock'=>70,  'hpp'=>78000],
            ['sku'=>'HK 005-6-B',    'name'=>'Iron Walnut',             'category'=>'TACO', 'dimension'=>'6 mtr', 'length_cm'=>600, 'stock'=>10,  'hpp'=>78000],
            ['sku'=>'HK 005NP-6-B',  'name'=>'Iron Walnut Nat',         'category'=>'TACO', 'dimension'=>'6 mtr', 'length_cm'=>600, 'stock'=>59,  'hpp'=>78000],

            // === KANG BANG ===
            ['sku'=>'KANG BANG',     'name'=>'KANG BANG',    'category'=>'KANG BANG', 'dimension'=>'6mtr', 'length_cm'=>600, 'stock'=>15, 'hpp'=>48000],

            // === RIJEK ===
            ['sku'=>'PLAVON-4',   'name'=>'Plavon',     'category'=>'RIJEK', 'dimension'=>'4mtr', 'length_cm'=>400, 'stock'=>93,  'hpp'=>0],
            ['sku'=>'PLAVON-6',   'name'=>'Plavon',     'category'=>'RIJEK', 'dimension'=>'6mtr', 'length_cm'=>600, 'stock'=>152, 'hpp'=>0],
            ['sku'=>'WPC-R',      'name'=>'WPC',         'category'=>'RIJEK', 'dimension'=>'3mtr', 'length_cm'=>300, 'stock'=>55,  'hpp'=>0],
        ];
    }
}
