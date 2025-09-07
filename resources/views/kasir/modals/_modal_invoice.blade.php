<div id="modal-invoice" class="hidden fixed inset-0 z-40 bg-black/50">
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-2xl rounded-2xl bg-white p-5 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold">Invoice</h3>
        <div class="flex items-center gap-2">
          <button type="button"
                  class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200"
                  onclick="window.print()">Print</button>
          <button type="button" class="btn-modal-close text-gray-500" data-modal-target="#modal-invoice">✕</button>
        </div>
      </div>
      <div id="invoice-content" class="prose max-w-none"></div>
    </div>
  </div>
</div>
