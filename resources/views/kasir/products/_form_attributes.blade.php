{{-- Atribut Teknis --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
  {{-- Header --}}
  <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
      <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
      </svg>
      Atribut Teknis
    </h2>
    <p class="text-sm text-gray-600 mt-1">Spesifikasi detail dan karakteristik produk</p>
  </div>

  <div class="p-6 space-y-6">
    {{-- Material & Series Information --}}
    <div class="bg-gray-50 rounded-xl p-4">
      <h3 class="text-sm font-medium text-gray-700 mb-4 flex items-center">
        <svg class="w-4 h-4 mr-2 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 002 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd"></path>
        </svg>
        Informasi Material & Seri
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Material</label>
          <div class="relative">
            <input type="text" name="material" value="{{ old('material') }}"
                   class="w-full pl-4 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
                   placeholder="mis. PVC / WPC">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Merek/Seri</label>
          <input type="text" name="series" value="{{ old('series') }}"
                 class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
                 placeholder="mis. Elok">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Kode Pola</label>
          <input type="text" name="pattern_code" value="{{ old('pattern_code') }}"
                 class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
                 placeholder="mis. Y08">
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Finishing</label>
          <input type="text" name="finish" value="{{ old('finish') }}"
                 class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
                 placeholder="mis. Nat / Silk">
        </div>
      </div>
    </div>

    {{-- Dimensions & Technical Details --}}
    <div class="bg-indigo-50 rounded-xl p-4">
      <h3 class="text-sm font-medium text-gray-700 mb-4 flex items-center">
        <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M12 1.586l-4 4v12.828l4-4V1.586zM3.707 3.293A1 1 0 002 4v4a1 1 0 00.293.707L6 12.414V11a1 1 0 011-1h4a1 1 0 011 1v1.414l3.707-3.707A1 1 0 0016 8V4a1 1 0 00-1.707-.707L12 5.586 9.707 3.293A1 1 0 008 4v4a1 1 0 00.293.707L12 12.414V11a1 1 0 011-1h2a1 1 0 011 1v1.414l1.707-1.707A1 1 0 0018 10V6a1 1 0 00-.293-.707l-4-4z" clip-rule="evenodd"></path>
        </svg>
        Dimensi & Spesifikasi
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Panjang (cm)</label>
          <div class="relative">
            <input type="number" name="length_cm" value="{{ old('length_cm') }}"
                   class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white" min="0">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <span class="text-sm text-gray-500">cm</span>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Lebar (mm)</label>
          <div class="relative">
            <input type="number" name="width_mm" value="{{ old('width_mm') }}"
                   class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white" min="0">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <span class="text-sm text-gray-500">mm</span>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tebal (mm)</label>
          <div class="relative">
            <input type="number" step="0.01" name="thickness_mm" value="{{ old('thickness_mm') }}"
                   class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white" min="0">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <span class="text-sm text-gray-500">mm</span>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Barcode</label>
          <div class="relative">
            <input type="text" name="barcode" value="{{ old('barcode') }}"
                   class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white"
                   placeholder="Scan atau input manual">
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
              <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zM13 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1V4zM9 4a1 1 0 000 2v9a1 1 0 001 1h1a1 1 0 100-2v-4a1 1 0 011-1h1a1 1 0 100-2H9z" clip-rule="evenodd"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Status & Activation --}}
    <div class="bg-green-50 border border-green-200 rounded-xl p-4">
      <div class="flex items-center space-x-3">
        <label class="inline-flex items-center">
          <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}
                 class="w-5 h-5 text-green-600 bg-white border-green-300 rounded focus:ring-green-500 focus:ring-2">
          <span class="ml-3 text-sm font-medium text-green-800 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            Status Aktif
          </span>
        </label>
        <div class="text-sm text-green-700">
          Produk dapat ditampilkan dan dijual ketika diaktifkan
        </div>
      </div>
    </div>
  </div>
</div>