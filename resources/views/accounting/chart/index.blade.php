@extends('layouts.dashboard', ['title' => 'Chart of Accounts'])

@section('content')
<div class="space-y-6">

 {{-- Add Account Form --}}
 <div class="rounded-2xl border bg-white shadow-card border-slate-200">
 <div class="p-6 md:p-7">
 <div class="flex items-start gap-3 mb-6">
 <div class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 grid place-items-center">
 <svg width="24" height="24" class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
 </svg>
 </div>
 <div>
 <h3 class="text-base font-semibold">Tambah Akun Baru</h3>
 <p class="text-sm text-slate-500">Isi data untuk menambahkan akun ke dalam daftar</p>
 </div>
 </div>

 <form method="POST" action="{{ route('accounting.chart.store') }}" class="space-y-4">
 @csrf
 <div class="grid md:grid-cols-4 gap-4">
 <div>
 <label for="code" class="block text-xs uppercase tracking-wide text-slate-500 mb-2">Kode Akun</label>
 <input type="text" name="code" id="code" value="{{ old('code') }}" required
 class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40"
 placeholder="Contoh: 1101">
 </div>
 <div>
 <label for="name" class="block text-xs uppercase tracking-wide text-slate-500 mb-2">Nama Akun</label>
 <input type="text" name="name" id="name" value="{{ old('name') }}" required
 class="w-full bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40"
 placeholder="Nama akun">
 </div>
 <div>
 <label for="type" class="block text-xs uppercase tracking-wide text-slate-500 mb-2">Tipe Akun</label>
 <select name="type" id="type" required
 class="w-full appearance-none bg-white border border-slate-200 rounded-xl h-11 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
 <option value="">Pilih Tipe</option>
 <option value="ASSET" @selected(old('type') == 'ASSET')>Aset</option>
 <option value="LIABILITY" @selected(old('type') == 'LIABILITY')>Liabilitas</option>
 <option value="EQUITY" @selected(old('type') == 'EQUITY')>Ekuitas</option>
 <option value="REVENUE" @selected(old('type') == 'REVENUE')>Pendapatan</option>
 <option value="EXPENSE" @selected(old('type') == 'EXPENSE')>Beban</option>
 </select>
 </div>
 <div class="flex items-end">
 <button type="submit"
 class="inline-flex items-center gap-2 h-11 px-5 rounded-xl bg-slate-900 text-white hover:bg-slate-800 text-sm font-medium transition">
 <svg width="20" height="20" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
 </svg>
 Simpan
 </button>
 </div>
 </div>
 </form>
 </div>
 </div>

 {{-- Chart of Accounts Table --}}
 <div class="rounded-2xl border bg-white shadow-card border-slate-200">
 <div class="p-6 md:p-7">
 <div class="flex items-center justify-between mb-6">
 <div class="flex items-start gap-3">
 <div class="h-10 w-10 rounded-lg bg-slate-100 border border-slate-200 grid place-items-center">
 <svg width="24" height="24" class="h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z"/>
 </svg>
 </div>
 <div>
 <h3 class="text-base font-semibold">Daftar Akun</h3>
 <p class="text-sm text-slate-500">{{ count($accounts) }} akun terdaftar</p>
 </div>
 </div>
 </div>

 <div class="overflow-x-auto">
 <table class="w-full text-sm">
 <thead class="text-left text-slate-600">
 <tr class="border-b border-slate-200">
 <th class="py-3 pr-4">Kode</th>
 <th class="py-3 pr-4">Nama</th>
 <th class="py-3 pr-4">Tipe</th>
 <th class="py-3 pr-4">Status</th>
 <th class="py-3 pr-0 text-right">Aksi</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-slate-200">
 @forelse ($accounts as $account)
 <tr class="hover:bg-slate-50 transition-colors" id="row-{{ $account->id }}">
 <td class="py-3 pr-4">
 <code class="px-2 py-1 rounded-md bg-slate-100 border border-slate-200 text-xs font-semibold">
 {{ $account->code }}
 </code>
 </td>
 <td class="py-3 pr-4 font-medium">
 <span id="name-display-{{ $account->id }}">{{ $account->name }}</span>
 <input type="text" name="name" id="name-edit-{{ $account->id }}" value="{{ $account->name }}"
 class="hidden w-full bg-white border border-slate-200 rounded-lg h-9 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
 </td>
 <td class="py-3 pr-4">
 @php
 $typeColors = [
 'ASSET' => ['bg-blue-100 text-blue-700', 'Aset'],
 'LIABILITY' => ['bg-red-100 text-red-700', 'Liabilitas'],
 'EQUITY' => ['bg-purple-100 text-purple-700', 'Ekuitas'],
 'REVENUE' => ['bg-green-100 text-green-700', 'Pendapatan'],
 'EXPENSE' => ['bg-orange-100 text-orange-700', 'Beban'],
 ];
 $tc = $typeColors[$account->type] ?? ['bg-slate-100 text-slate-700', $account->type];
 @endphp
 <span id="type-display-{{ $account->id }}" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tc[0] }}">
 {{ $tc[1] }}
 </span>
 <select name="type" id="type-edit-{{ $account->id }}"
 class="hidden w-full appearance-none bg-white border border-slate-200 rounded-lg h-9 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
 <option value="ASSET" @selected($account->type == 'ASSET')>Aset</option>
 <option value="LIABILITY" @selected($account->type == 'LIABILITY')>Liabilitas</option>
 <option value="EQUITY" @selected($account->type == 'EQUITY')>Ekuitas</option>
 <option value="REVENUE" @selected($account->type == 'REVENUE')>Pendapatan</option>
 <option value="EXPENSE" @selected($account->type == 'EXPENSE')>Beban</option>
 </select>
 </td>
 <td class="py-3 pr-4">
 @if($account->is_active)
 <span class="inline-flex items-center gap-1.5 text-xs text-emerald-700">
 <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
 </span>
 @else
 <span class="inline-flex items-center gap-1.5 text-xs text-slate-500">
 <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Nonaktif
 </span>
 @endif
 </td>
 <td class="py-3 pr-0">
 <div class="flex items-center justify-end gap-2">
 {{-- Edit / Save --}}
 <button type="button" id="edit-btn-{{ $account->id }}"
 onclick="toggleEdit({{ $account->id }}, '{{ csrf_token() }}')"
 class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-slate-200 hover:bg-slate-100 text-xs font-medium text-slate-700 transition">
 <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
 </svg>
 <span id="edit-label-{{ $account->id }}">Edit</span>
 </button>
 {{-- Delete --}}
 <form method="POST" action="{{ route('accounting.chart.destroy', $account->id) }}"
 onsubmit="return confirm('Yakin ingin menghapus akun ini?')">
 @csrf
 @method('DELETE')
 <button type="submit"
 class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-rose-200 hover:bg-rose-50 text-xs font-medium text-rose-700 transition">
 <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
 </svg>
 Hapus
 </button>
 </form>
 </div>
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="5" class="py-12">
 <div class="text-center space-y-2">
 <p class="font-medium">Belum ada akun</p>
 <p class="text-slate-500">Mulai dengan menambahkan akun baru di form di atas.</p>
 </div>
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>

 </div>
 </div>
</div>

@push('scripts')
<script>
const editingState = {};

function toggleEdit(id, csrf) {
 const nameDisplay = document.getElementById('name-display-' + id);
 const nameEdit = document.getElementById('name-edit-' + id);
 const typeDisplay = document.getElementById('type-display-' + id);
 const typeEdit = document.getElementById('type-edit-' + id);
 const editBtn = document.getElementById('edit-btn-' + id);
 const editLabel = document.getElementById('edit-label-' + id);

 if (!editingState[id]) {
 // Enter edit mode
 editingState[id] = true;
 nameDisplay.classList.add('hidden');
 nameEdit.classList.remove('hidden');
 typeDisplay.classList.add('hidden');
 typeEdit.classList.remove('hidden');
 editLabel.textContent = 'Simpan';
 editBtn.classList.add('border-emerald-200', 'hover:bg-emerald-50', 'text-emerald-700');
 editBtn.classList.remove('border-slate-200', 'hover:bg-slate-100', 'text-slate-700');
 } else {
 // Save via AJAX
 const newName = nameEdit.value.trim();
 const newType = typeEdit.value;
 if (!newName) { alert('Nama akun wajib diisi.'); return; }

 fetch('/accounting/chart/' + id, {
 method: 'PUT',
 headers: {
 'Content-Type': 'application/json',
 'X-CSRF-TOKEN': csrf,
 'Accept': 'application/json'
 },
 body: JSON.stringify({ name: newName, type: newType })
 })
 .then(r => r.json())
 .then(data => {
 if (data.success) {
 // Update display
 nameDisplay.textContent = newName;
 const typeLabels = { ASSET:'Aset', LIABILITY:'Liabilitas', EQUITY:'Ekuitas', REVENUE:'Pendapatan', EXPENSE:'Beban' };
 typeDisplay.textContent = typeLabels[newType] || newType;

 // Exit edit mode
 editingState[id] = false;
 nameDisplay.classList.remove('hidden');
 nameEdit.classList.add('hidden');
 typeDisplay.classList.remove('hidden');
 typeEdit.classList.add('hidden');
 editLabel.textContent = 'Edit';
 editBtn.classList.remove('border-emerald-200', 'hover:bg-emerald-50', 'text-emerald-700');
 editBtn.classList.add('border-slate-200', 'hover:bg-slate-100', 'text-slate-700');
 } else {
 alert(data.message || 'Gagal mengupdate akun.');
 }
 })
 .catch(() => alert('Terjadi kesalahan jaringan.'));
 }
}
</script>
@endpush
@endsection