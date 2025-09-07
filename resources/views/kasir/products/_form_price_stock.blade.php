{{-- Harga (HPP) & Stok Awal --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
  {{-- Header --}}
  <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
      <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
      </svg>
      Harga & Stok Awal
    </h2>
    <p class="text-sm text-gray-600 mt-1">Pengaturan harga modal dan stok pembukaan</p>
  </div>

  <div class="p-6 space-y-6">
    {{-- Pricing Information --}}
    <div class="bg-green-50 rounded-xl p-4">
      <h3 class="text-sm font-medium text-gray-700 mb-4 flex items-center">
        <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
        </svg>
        Informasi Harga
     </h3>
     
     <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
       {{-- HPP Input --}}
       <div class="md:col-span-2">
         <label class="block text-sm font-medium text-gray-700 mb-2">
           Harga Modal (HPP) 
           <span class="text-red-500 ml-1">*</span>
         </label>
         
         <div class="relative">
           <div class="absolute inset-y-0 left-0 flex items-center pl-4">
             <span class="text-gray-500 text-sm font-medium">Rp</span>
           </div>
           <input id="hpp" type="number" step="0.01" name="hpp" value="{{ old('hpp') }}"
                  class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors bg-white text-lg font-medium"
                  min="0" required placeholder="0.00"
                  data-markup="{{ $markup }}">
         </div>
         
         {{-- HPP Information Note --}}
         <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
           <div class="flex items-start">
             <svg class="w-4 h-4 text-yellow-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
               <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
             </svg>
             <div class="text-xs text-yellow-700">
               <p class="font-medium mb-1">Catatan Penyimpanan</p>
               <p>Nilai HPP disimpan ke kolom <code class="bg-yellow-100 px-1 rounded">products.notes</code> dengan format <strong>hpp:&lt;angka&gt;</strong></p>
             </div>
           </div>
         </div>
       </div>

       {{-- Price Preview --}}
       <div>
         <label class="block text-sm font-medium text-gray-700 mb-2">Preview Harga Jual</label>
         
         <div class="bg-white border-2 border-dashed border-gray-300 rounded-xl p-4 text-center">
           <div class="text-xs text-gray-500 mb-2">
             HPP × {{ number_format($markup, 2) }} (Markup)
           </div>
           
           <div class="bg-gradient-to-r from-green-100 to-emerald-100 rounded-lg p-3">
             <div class="text-sm text-green-700 font-medium mb-1">Harga Jual Estimasi</div>
             <div id="price-preview" class="text-2xl font-bold text-green-800">Rp 0</div>
           </div>
           
           <div class="mt-3 text-xs text-gray-500">
             <div class="flex items-center justify-center">
               <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                 <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>
               </svg>
               Auto-calculate saat input HPP
             </div>
           </div>
         </div>
       </div>
     </div>
   </div>

   {{-- Stock Information --}}
   <div class="bg-emerald-50 rounded-xl p-4">
     <h3 class="text-sm font-medium text-gray-700 mb-4 flex items-center">
       <svg class="w-4 h-4 mr-2 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
         <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
       </svg>
       Stok Pembukaan
     </h3>
     
     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
       {{-- Stock Input --}}
       <div>
         <label class="block text-sm font-medium text-gray-700 mb-2">
           Jumlah Stok Awal 
           <span class="text-red-500 ml-1">*</span>
         </label>
         
         <div class="relative">
           <input type="number" name="initial_stock" value="{{ old('initial_stock', 0) }}"
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors bg-white text-lg font-medium"
                  min="0" required placeholder="0">
           <div class="absolute inset-y-0 right-0 flex items-center pr-4">
             <span class="text-gray-500 text-sm">unit</span>
           </div>
         </div>
         
         {{-- Quick Stock Buttons --}}
         <div class="mt-3 flex flex-wrap gap-2">
           <button type="button" onclick="setStock(10)" class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">10</button>
           <button type="button" onclick="setStock(50)" class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">50</button>
           <button type="button" onclick="setStock(100)" class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">100</button>
           <button type="button" onclick="setStock(500)" class="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">500</button>
         </div>
       </div>

       {{-- Branch & Location Info --}}
       <div>
         <label class="block text-sm font-medium text-gray-700 mb-2">Lokasi Penyimpanan</label>
         
         <div class="bg-white border border-gray-300 rounded-xl p-4">
           <div class="flex items-center space-x-3 mb-3">
             <div class="flex-shrink-0 w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
               <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                 <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4z" clip-rule="evenodd"></path>
               </svg>
             </div>
             <div class="flex-1">
               <div class="font-medium text-gray-900">
                 Cabang {{ $branch->id ?? '—' }}
               </div>
               <div class="text-sm text-gray-600">
                 @if($branch?->name)
                   {{ $branch->name }}
                 @else
                   <span class="italic">Nama cabang tidak tersedia</span>
                 @endif
               </div>
             </div>
           </div>
           
           <div class="border-t border-gray-200 pt-3">
             <div class="flex items-center justify-between text-sm">
               <span class="text-gray-600">Status Lokasi:</span>
               <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                 <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                   <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                 </svg>
                 AVAILABLE
               </span>
             </div>
           </div>
         </div>
         
         {{-- Additional Info --}}
         <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
           <div class="flex items-start">
             <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
               <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
             </svg>
             <div class="text-xs text-blue-700">
               <p class="font-medium mb-1">Catatan Stok</p>
               <p>Stok akan tercatat pada cabang aktif dengan status AVAILABLE dan siap untuk dijual.</p>
             </div>
           </div>
         </div>
       </div>
     </div>
   </div>
 </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
   const hppInput = document.getElementById('hpp');
   const pricePreview = document.getElementById('price-preview');
   const markup = parseFloat(hppInput.dataset.markup) || 1;
   
   // Update price preview when HPP changes
   function updatePricePreview() {
       const hpp = parseFloat(hppInput.value) || 0;
       const salePrice = hpp * markup;
       
       if (salePrice > 0) {
           pricePreview.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(salePrice);
           pricePreview.parentElement.classList.remove('opacity-50');
       } else {
           pricePreview.textContent = 'Rp 0';
           pricePreview.parentElement.classList.add('opacity-50');
       }
   }
   
   // Set stock value function
   window.setStock = function(value) {
       const stockInput = document.querySelector('input[name="initial_stock"]');
       if (stockInput) {
           stockInput.value = value;
           stockInput.focus();
       }
   };
   
   // Event listeners
   hppInput?.addEventListener('input', updatePricePreview);
   hppInput?.addEventListener('change', updatePricePreview);
   
   // Initial calculation
   updatePricePreview();
});
</script>