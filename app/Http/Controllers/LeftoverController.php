<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeftoverController extends Controller
{
    /**
     * Menampilkan halaman daftar sisa potongan dengan filter.
     */
    public function index(Request $request)
    {
        $products = DB::table('products')->where('is_active', 1)->orderBy('name')->get(['id', 'name', 'sku']);
        $branches = DB::table('branches')->where('is_active', 1)->orderBy('name')->get(['id', 'name']);

        $query = DB::table('leftover_pieces as lp')
            ->join('products as p', 'lp.product_id', '=', 'p.id')
            ->join('branches as b', 'lp.branch_id', '=', 'b.id')
            ->select(
                'lp.id', 'p.name as product_name', 'p.sku as product_sku',
                'b.name as branch_name', 'lp.length_m', 'lp.condition',
                'lp.source_type', 'lp.source_id', 'lp.created_at'
            )
            ->whereNull('lp.consumed_at');

        if ($request->filled('product_id')) {
            $query->where('lp.product_id', $request->input('product_id'));
        }
        if ($request->filled('branch_id')) {
            $query->where('lp.branch_id', $request->input('branch_id'));
        }
        if ($request->filled('condition')) {
            $query->where('lp.condition', $request->input('condition'));
        }

        $query->orderBy('lp.id', 'desc');
        $leftovers = $query->paginate(25)->withQueryString();

        return view('leftovers.index', [
            'leftovers' => $leftovers, 'products' => $products,
            'branches'  => $branches, 'filters' => $request->all(),
        ]);
    }

    /**
     * [BARU] Menampilkan form untuk mengedit sisa potongan.
     */
    public function edit(int $id)
    {
        $leftover = DB::table('leftover_pieces as lp')
            ->join('products as p', 'lp.product_id', '=', 'p.id')
            ->join('branches as b', 'lp.branch_id', '=', 'b.id')
            ->select(
                'lp.id', 'lp.product_id', 'lp.branch_id', 'lp.length_m', 'lp.condition',
                'p.name as product_name', 'p.sku as product_sku', 'b.name as branch_name'
            )
            ->where('lp.id', $id)
            ->first();

        if (!$leftover) {
            return redirect()->route('leftovers.index')->with('error', 'Data sisa potongan tidak ditemukan.');
        }

        return view('leftovers.edit', ['leftover' => $leftover]);
    }

    /**
     * [BARU] Memperbarui data sisa potongan di database.
     */
    public function update(Request $request, int $id)
    {
        // Validasi input
        $data = $request->validate([
            'length_m' => ['required', 'numeric', 'min:0'],
            'condition' => ['required', 'in:GOOD,DAMAGED'],
        ]);

        try {
            DB::beginTransaction();

            $leftoverToUpdate = DB::table('leftover_pieces')->where('id', $id)->first();
            if (!$leftoverToUpdate) {
                return redirect()->back()->with('error', 'Data sisa potongan tidak ditemukan untuk diperbarui.');
            }

            // Simpan data lama untuk audit
            $oldData = json_encode($leftoverToUpdate);

            // Update data di database
            DB::table('leftover_pieces')->where('id', $id)->update([
                'length_m' => $data['length_m'],
                'condition' => $data['condition'],
            ]);

            // Catat di audit log
            DB::table('audit_logs')->insert([
                'event'    => 'LEFTOVER_UPDATED',
                'user_id'  => Auth::id(),
                'ref_type' => 'LEFTOVER',
                'ref_id'   => $id,
                'payload'  => json_encode(['old' => json_decode($oldData), 'new' => $data]),
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('leftovers.index')->with('success', 'Sisa potongan (ID: ' . $id . ') berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui sisa potongan: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus sisa potongan dari sistem.
     */
    public function destroy(int $id)
    {
        try {
            DB::beginTransaction();

            $leftoverToDelete = DB::table('leftover_pieces')->where('id', $id)->first();
            if (!$leftoverToDelete) {
                return redirect()->back()->with('error', 'Data sisa potongan tidak ditemukan.');
            }

            DB::table('leftover_pieces')->where('id', $id)->delete();

            DB::table('audit_logs')->insert([
                'event'    => 'LEFTOVER_DELETED',
                'user_id'  => Auth::id(),
                'ref_type' => 'LEFTOVER',
                'ref_id'   => $id,
                'payload'  => json_encode($leftoverToDelete),
                'created_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('leftovers.index')->with('success', 'Sisa potongan (ID: ' . $id . ') berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus sisa potongan: ' . $e->getMessage());
        }
    }
}



