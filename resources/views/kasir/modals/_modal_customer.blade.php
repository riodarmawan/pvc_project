{{-- Modal Customer yang Anda berikan sebelumnya --}}
<div id="modal-customer" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm">
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl transform transition-all">
      {{-- Modal Header --}}
      <div class="bg-gradient-to-r from-green-50 to-teal-50 px-6 py-4 border-b border-gray-200 rounded-t-2xl">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-800 flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
            </svg>
            Tambah Pelanggan Baru
          </h3>
          <button type="button" class="btn-modal-close text-gray-400 hover:text-gray-600 transition-colors duration-200" data-modal-target="#modal-customer">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
          </button>
        </div>
      </div>

      {{-- Modal Body --}}
      <div class="p-6">
        <form class="js-ajax space-y-4" method="post" action="{{ route('kasir.customer.quick') }}">
          @csrf
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Pelanggan *</label>
            <input type="text" name="name" required 
                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                   placeholder="Masukkan nama lengkap...">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Telepon</label>
            <input type="tel" name="phone" 
                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                   placeholder="Contoh: 08123456789">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Alamat</label>
            <textarea name="address" rows="3" 
                      class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors resize-none"
                      placeholder="Masukkan alamat lengkap (opsional)..."></textarea>
          </div>

          {{-- Modal Footer --}}
          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
            <button type="button" 
                    class="btn-modal-close px-6 py-2.5 text-gray-700 bg-gray-100 border border-gray-300 rounded-xl hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200"
                    data-modal-target="#modal-customer">
              Batal
            </button>
            <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-green-600 to-teal-600 text-white font-medium rounded-xl hover:from-green-700 hover:to-teal-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 flex items-center">
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              Simpan Pelanggan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
