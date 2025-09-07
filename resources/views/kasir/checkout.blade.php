{{-- resources/views/kasir/checkout.blade.php --}}
@extends('layouts.app', ['title' => 'Checkout'])

@section('content')
<div class="space-y-6">
  <!-- Header -->
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl md:text-2xl font-semibold">Checkout</h1>
    <a href="{{ route('kasir.home') }}"
       class="inline-flex items-center h-10 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 shadow-sm transition">
      ← Kembali ke Katalog
    </a>
  </div>

  <!-- Main Grid -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    {{-- Kolom kiri --}}
    <div class="lg:col-span-8 space-y-6">
      <div id="cart-list"
           class="rounded-2xl border border-slate-200 bg-white shadow-card overflow-hidden">
        @include('kasir.partials._cart')
      </div>

      <div id="customer-panel"
           class="rounded-2xl border border-slate-200 bg-white shadow-card overflow-hidden">
        @include('kasir.partials._customer')
      </div>
    </div>

    {{-- Kolom kanan --}}
    <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-24 self-start">
      <div id="payments-panel"
           class="rounded-2xl border border-slate-200 bg-white shadow-card overflow-hidden">
        @include('kasir.partials._payments')
      </div>

      <div id="summary-panel"
           class="rounded-2xl border border-slate-200 bg-white shadow-card overflow-hidden">
        @include('kasir.partials._summary')
      </div>
    </div>
  </div>
</div>

@include('kasir.modals._modal_customer')
@include('kasir.modals._modal_invoice')

<script src="{{ asset('js/pos.js') }}" defer></script>
@endsection
