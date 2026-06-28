@extends('layouts.dashboard', ['title' => 'Log Audit'])

@section('content')
<div class="space-y-4">

 {{-- Filter --}}
 <form method="get" class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row items-end gap-3">
 <div class="flex-1 w-full sm:w-auto">
 <label class="block text-xs font-medium text-slate-500 mb-1">Event</label>
 <select name="event" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
 <option value="">Semua Event</option>
 @foreach($events as $ev)
 <option value="{{ $ev }}" @selected($event == $ev)>{{ $ev }}</option>
 @endforeach
 </select>
 </div>
 <div class="flex-1 w-full sm:w-auto">
 <label class="block text-xs font-medium text-slate-500 mb-1">User</label>
 <select name="user_id" class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
 <option value="">Semua User</option>
 @foreach($users as $u)
 <option value="{{ $u->id }}" @selected($userId == $u->id)>{{ $u->full_name }}</option>
 @endforeach
 </select>
 </div>
 <div class="flex-1 w-full sm:w-auto">
 <label class="block text-xs font-medium text-slate-500 mb-1">Dari</label>
 <input type="date" name="date_from" value="{{ $dateFrom }}"
 class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
 </div>
 <div class="flex-1 w-full sm:w-auto">
 <label class="block text-xs font-medium text-slate-500 mb-1">Sampai</label>
 <input type="date" name="date_to" value="{{ $dateTo }}"
 class="w-full h-10 px-3 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
 </div>
 <button class="h-10 px-5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition flex-shrink-0">
 Filter
 </button>
 </form>

 {{-- Table --}}
 <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
 <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
 <h3 class="text-sm font-semibold text-slate-900">Log Audit</h3>
 <span class="text-xs text-slate-400">{{ $logs->total() }} catatan</span>
 </div>

 <div class="overflow-x-auto">
 <table class="w-full text-sm">
 <thead class="bg-slate-50 border-b border-slate-200">
 <tr>
 <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500">#</th>
 <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500">Waktu</th>
 <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500">Event</th>
 <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500">User</th>
 <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500">Produk</th>
 <th class="px-4 py-2.5 text-left text-xs font-medium text-slate-500">Detail</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-slate-100">
 @forelse ($logs as $log)
 <tr class="hover:bg-slate-50 transition-colors">
 <td class="px-4 py-3 text-xs text-slate-400">{{ $log->id }}</td>
 <td class="px-4 py-3 text-xs text-slate-600 whitespace-nowrap">
 {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i:s') }}
 </td>
 <td class="px-4 py-3">
 @php
 $eventColors = [
 'STOCK_ADJUST' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
 'STOCK_ADJUST_DIRECT' => 'bg-amber-50 text-amber-700 border-amber-200',
 'STOCK_TRANSFER' => 'bg-blue-50 text-blue-700 border-blue-200',
 ];
 $color = $eventColors[$log->event] ?? 'bg-slate-50 text-slate-600 border-slate-200';
 @endphp
 <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold border {{ $color }}">
 {{ $log->event }}
 </span>
 </td>
 <td class="px-4 py-3 text-xs text-slate-600">{{ $log->user_name ?? '—' }}</td>
 <td class="px-4 py-3">
 @if($log->product_name)
 <div class="text-xs font-medium text-slate-900">{{ $log->product_name }}</div>
 <div class="text-[10px] text-slate-400">{{ $log->product_sku }}</div>
 @else
 <span class="text-xs text-slate-400">—</span>
 @endif
 </td>
 <td class="px-4 py-3">
 @if($log->event === 'STOCK_ADJUST' || $log->event === 'STOCK_ADJUST_DIRECT')
 @php
 $old = $log->payload_data['old_qty'] ?? null;
 $new = $log->payload_data['new_qty'] ?? null;
 $reason = $log->payload_data['reason'] ?? '';
 $delta = $log->payload_data['delta'] ?? null;
 $hpp = $log->payload_data['hpp'] ?? null;
 $deltaVal = $log->payload_data['delta_value'] ?? null;
 @endphp
 <div class="text-xs space-y-0.5">
 @if($old !== null && $new !== null)
 <div class="text-slate-600">
 Stok: <span class="font-medium">{{ number_format((float)$old, 0, ',', '.') }}</span>
 → <span class="font-semibold {{ (float)$new >= (float)$old ? 'text-emerald-700' : 'text-rose-700' }}">{{ number_format((float)$new, 0, ',', '.') }}</span>
 </div>
 @endif
 @if($delta !== null)
 <div class="{{ (float)$delta >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
 Selisih: {{ (float)$delta >= 0 ? '+' : '' }}{{ number_format((float)$delta, 0, ',', '.') }}
 </div>
 @endif
 @if($deltaVal !== null && (float)$deltaVal > 0)
 <div class="text-slate-500">
 Nilai: Rp {{ number_format((float)$deltaVal, 0, ',', '.') }}
 @if($hpp)
 <span class="text-slate-400">(HPP: Rp {{ number_format((float)$hpp, 0, ',', '.') }})</span>
 @endif
 </div>
 @endif
 @if($reason)
 <div class="text-slate-400 italic">{{ $reason }}</div>
 @endif
 </div>
 @elseif($log->event === 'STOCK_TRANSFER')
 @php
 $from = $log->payload_data['from_branch'] ?? null;
 $to = $log->payload_data['to_branch'] ?? null;
 $items = $log->payload_data['items'] ?? [];
 @endphp
 <div class="text-xs text-slate-600">
 {{ $from ?? '?' }} → {{ $to ?? '?' }}
 </div>
 @else
 <span class="text-xs text-slate-400">—</span>
 @endif
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="6" class="px-4 py-12 text-center">
 <div class="flex flex-col items-center">
 <div class="h-12 w-12 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
 <svg class="h-6 w-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
 </div>
 <p class="text-sm font-medium text-slate-500">Belum ada log audit</p>
 <p class="text-xs text-slate-400 mt-1">Log akan muncul setelah ada aktivitas stok</p>
 </div>
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>

 @if($logs->hasPages())
 <div class="px-4 py-3 border-t border-slate-100 flex justify-center">
 {{ $logs->links('pagination::tailwind') }}
 </div>
 @endif
 </div>
</div>
@endsection
