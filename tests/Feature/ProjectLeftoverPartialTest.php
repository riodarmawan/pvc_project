<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\BusinessTestCase;

class ProjectLeftoverPartialTest extends BusinessTestCase
{
    /** Potongan sisa siap pakai di cabang. */
    private function makePiece(int $branch, int $productId, float $lengthM): int
    {
        return (int) DB::table('leftover_pieces')->insertGetId([
            'product_id' => $productId,
            'branch_id'  => $branch,
            'length_m'   => $lengthM,
            'condition'  => 'GOOD',
            'created_at' => now(),
        ]);
    }

    /** Catat pemakaian sebagian oleh proyek lain yang sudah jalan duluan. */
    private function consume(int $pieceId, float $usedM, int $branch, int $userId): void
    {
        $projectId = (int) DB::table('projects')->insertGetId([
            'branch_id'  => $branch,
            'code'       => 'PRJ-'.substr(uniqid(), -8),
            'title'      => 'Proyek Sebelumnya',
            'status'     => 'DONE',
            'created_by' => $userId,
            'created_at' => now(),
        ]);

        DB::table('leftover_piece_consumptions')->insert([
            'piece_id'   => $pieceId,
            'project_id' => $projectId,
            'used_m'     => $usedM,
            'created_at' => now(),
        ]);
    }

    /**
     * Potongan 5m yang sudah dipakai 2m hanya menyisakan 3m. Sebelum perbaikan,
     * length_m tidak pernah dikurangi sehingga proyek berikutnya masih bisa memakai
     * 5m penuh — material fisik jadi dobel-pakai.
     */
    public function test_tidak_bisa_memakai_melebihi_sisa_potongan(): void
    {
        $branch  = $this->makeBranch();
        $this->makeAvailableLocation($branch);
        $kasir   = $this->makeUser(3, $branch);
        $product = $this->makeProduct(['hpp' => 6000, 'selling_price' => 10000]);

        $piece = $this->makePiece($branch, $product, 5.0);
        $this->consume($piece, 2.0, $branch, $kasir->id);   // proyek lain sudah pakai 2m

        $this->actingAs($kasir);

        // 4m > sisa 3m -> harus ditolak.
        $this->post(route('projects.cart.add'), [
            'type' => 'leftover', 'piece_id' => $piece, 'used_length_m' => 4.0, 'price' => 50000,
        ])->assertSessionHasErrors();

        // 3m (tepat sisa) -> boleh.
        $this->post(route('projects.cart.add'), [
            'type' => 'leftover', 'piece_id' => $piece, 'used_length_m' => 3.0, 'price' => 50000,
        ])->assertSessionHasNoErrors();
    }

    /** Potongan yang sisanya sudah habis tidak boleh dipakai lagi sama sekali. */
    public function test_potongan_habis_terpakai_ditolak(): void
    {
        $branch  = $this->makeBranch();
        $this->makeAvailableLocation($branch);
        $kasir   = $this->makeUser(3, $branch);
        $product = $this->makeProduct();

        $piece = $this->makePiece($branch, $product, 4.0);
        $this->consume($piece, 4.0, $branch, $kasir->id);   // sudah habis, tapi consumed_at belum ditandai

        $this->actingAs($kasir)->post(route('projects.cart.add'), [
            'type' => 'leftover', 'piece_id' => $piece, 'used_length_m' => 1.0, 'price' => 50000,
        ])->assertSessionHasErrors();
    }

    /** Daftar potongan (datalist) harus menampilkan SISA, dan menyembunyikan yang habis. */
    public function test_daftar_potongan_menampilkan_sisa_dan_menyembunyikan_yang_habis(): void
    {
        $branch  = $this->makeBranch();
        $kasir   = $this->makeUser(3, $branch);
        $product = $this->makeProduct();

        $sebagian = $this->makePiece($branch, $product, 5.0);
        $this->consume($sebagian, 2.0, $branch, $kasir->id);          // sisa 3m
        $habis = $this->makePiece($branch, $product, 4.0);
        $this->consume($habis, 4.0, $branch, $kasir->id);             // sisa 0m

        $rows = collect($this->actingAs($kasir)
            ->getJson(route('projects.leftover.list'))
            ->assertOk()
            ->json());

        $this->assertNotNull($rows->firstWhere('id', $sebagian), 'Potongan bersisa harus tetap muncul.');
        $this->assertEqualsWithDelta(3.0, (float) $rows->firstWhere('id', $sebagian)['length_m'], 0.001,
            'Yang ditampilkan harus sisa (3m), bukan panjang awal (5m).');
        $this->assertNull($rows->firstWhere('id', $habis), 'Potongan yang habis tidak boleh ditawarkan lagi.');
    }
}
