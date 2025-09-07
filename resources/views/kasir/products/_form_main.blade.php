{{-- Identitas Produk & Kategori --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
  {{-- Header --}}
  <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
      <svg class="w-5 h-5 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2v2h5V6H4zm7 0h5v2h-5V6zm5 4H4v4h12v-4z" clip-rule="evenodd"></path>
      </svg>
      Identitas Produk
    </h2>
    <p class="text-sm text-gray-600 mt-1">Informasi dasar dan kategori produk</p>
  </div>

  <div class="p-6 space-y-6">
    {{-- Basic Product Information --}}
    <div class="bg-purple-50 rounded-xl p-4">
      <h3 class="text-sm font-medium text-gray-700 mb-4 flex items-center">
        <svg class="w-4 h-4 mr-2 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
        </svg>
        Informasi Dasar
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            SKU 
            <span class="text-red-500 ml-1">*</span>
          </label>
          <div class="relative">
            <input type="text" name="sku" value="{{ old('sku') }}" required
                   class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors bg-white"
                   placeholder="mis. PVC6M-EL-WOOD18">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
              </svg>
            </div>
          </div>
          <p class="text-xs text-gray-500 mt-1">Kode unik untuk identifikasi produk</p>
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Nama Produk 
            <span class="text-red-500 ml-1">*</span>
          </label>
          <input type="text" name="name" value="{{ old('name') }}" required
                 class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors bg-white"
                 placeholder="mis. Elok Wood 18 6m">
          <p class="text-xs text-gray-500 mt-1">Nama lengkap yang akan ditampilkan pada katalog</p>
        </div>
      </div>
    </div>

    {{-- Category & UOM Information --}}
    <div class="bg-pink-50 rounded-xl p-4">
      <h3 class="text-sm font-medium text-gray-700 mb-4 flex items-center">
        <svg class="w-4 h-4 mr-2 text-pink-500" fill="currentColor" viewBox="0 0 20 20">
          <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"></path>
        </svg>
        Kategori & Satuan
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Kategori 
            <span class="text-red-500 ml-1">*</span>
          </label>
          
          {{-- Existing Category Selection --}}
          <div class="flex items-center gap-3 mb-3">
            <div class="relative flex-1">
              <select name="category_id" class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-colors bg-white appearance-none">
                <option value="">Pilih kategori existing...</option>
                @foreach ($categories as $c)
                  <option value="{{ $c->id }}" @selected(old('category_id')==$c->id)>
                    [{{ $c->code }}] {{ $c->name }}
                  </option>
                @endforeach
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
              </div>
            </div>
            
            {{-- Toggle New Category --}}
            <div class="flex items-center">
              <label class="inline-flex items-center px-4 py-3 bg-green-50 border border-green-200 rounded-xl hover:bg-green-100 transition-colors cursor-pointer">
                <input id="toggle-new-cat" type="checkbox" name="use_new_cat" value="1" {{ old('use_new_cat') ? 'checked' : '' }}
                       class="w-4 h-4 text-green-600 bg-white border-green-300 rounded focus:ring-green-500 focus:ring-2">
                <span class="ml-2 text-sm font-medium text-green-800">Tambah Baru</span>
              </label>
            </div>
          </div>

          {{-- New Category Fields --}}
          <div id="new-cat-fields" class="space-y-3 p-4 bg-green-50 border border-green-200 rounded-xl {{ old('use_new_cat') ? '' : 'hidden' }}">
            <div class="flex items-center mb-2">
              <svg class="w-4 h-4 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
              </svg>
              <h4 class="text-sm font-medium text-green-800">Kategori Baru</h4>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs font-medium text-green-700 mb-1">Kode Kategori</label>
                <input type="text" name="new_category_code" value="{{ old('new_category_code') }}"
                       class="w-full px-3 py-2 border border-green-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors bg-white text-sm"
                       placeholder="Kode unik">
              </div>
              <div class="md:col-span-2">
                <label class="block text-xs font-medium text-green-700 mb-1">Nama Kategori</label>
                <input type="text" name="new_category_name" value="{{ old('new_category_name') }}"
                       class="w-full px-3 py-2 border border-green-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors bg-white text-sm"
                       placeholder="Nama kategori baru">
              </div>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            UOM (Satuan) 
            <span class="text-red-500 ml-1">*</span>
          </label>
          <div class="relative">
            <select name="uom_id" class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-colors bg-white appearance-none" required>
              <option value="">Pilih satuan...</option>
              @foreach ($uoms as $u)
                <option value="{{ $u->id }}" @selected(old('uom_id')==$u->id)>
                  [{{ $u->code }}] {{ $u->name }}
                </option>
              @endforeach
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
              </svg>
            </div>
          </div>
          
          {{-- Info Note --}}
          <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start">
              <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
              </svg>
              <div class="text-xs text-blue-700">
                <p class="font-medium mb-1">Catatan UOM</p>
                <p>UOM wajib untuk integritas stok walau tidak ditampilkan di katalog.</p>
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
    const toggleNewCat = document.getElementById('toggle-new-cat');
    const newCatFields = document.getElementById('new-cat-fields');
    const categorySelect = document.querySelector('select[name="category_id"]');
    
    toggleNewCat?.addEventListener('change', function() {
        if (this.checked) {
            newCatFields?.classList.remove('hidden');
            categorySelect.disabled = true;
        } else {
            newCatFields?.classList.add('hidden');
            categorySelect.disabled = false;
        }
    });
});
</script>