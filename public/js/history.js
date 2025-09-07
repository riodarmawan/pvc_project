(() => {
  const fmtMoney = (n) => {
    const v = Number(n || 0);
    return 'Rp ' + v.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  const qs  = (s, el=document) => el.querySelector(s);
  const qsa = (s, el=document) => Array.from(el.querySelectorAll(s));

  async function fetchDetail(id) {
    const res = await fetch(`/kasir/history/${id}/detail`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });
    if (!res.ok) throw new Error('Gagal memuat detail');
    return await res.json();
  }

  function renderDetail(payload) {
    const s = payload.sale;
    const lines = payload.lines || [];
    const pays  = payload.payments || [];

    let html = '';
    html += `<div class="grid grid-cols-1 md:grid-cols-3 gap-4">`;
    html += `  <div class="p-3 rounded-lg bg-white shadow">
                <div class="text-sm text-gray-500">Transaksi</div>
                <div class="font-semibold">#${s.id}</div>
                <div class="text-sm text-gray-500">${s.datetime}</div>
                <div class="text-sm text-gray-500">Cabang: ${s.branch}</div>
                <div class="text-sm text-gray-500">Pelanggan: ${s.customer ?? '—'} ${s.phone? '• '+s.phone : ''}</div>
              </div>`;
    html += `  <div class="p-3 rounded-lg bg-white shadow md:col-span-2 overflow-auto">
                <div class="font-semibold mb-2">Item</div>
                <table class="w-full text-sm">
                  <thead class="bg-gray-50">
                    <tr><th class="p-2 text-left">Produk</th><th class="p-2 text-right">Qty</th><th class="p-2 text-right">Harga</th><th class="p-2 text-right">Subtotal</th></tr>
                  </thead>
                  <tbody class="divide-y">
                    ${lines.map(l => `
                      <tr>
                        <td class="p-2">${l.name} <span class="text-gray-500">(${l.sku})</span></td>
                        <td class="p-2 text-right">${parseInt(l.qty)}</td>
                        <td class="p-2 text-right">${fmtMoney(l.price)}</td>
                        <td class="p-2 text-right">${fmtMoney(l.subtotal)}</td>
                      </tr>
                    `).join('')}
                  </tbody>
                </table>
              </div>`;
    html += `</div>`;

    html += `<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
              <div class="p-3 rounded-lg bg-white shadow md:col-span-2">
                <div class="font-semibold mb-2">Pembayaran</div>
                ${(pays.length === 0)
                  ? `<div class="text-gray-500 text-sm">Tidak ada pembayaran.</div>`
                  : `<table class="w-full text-sm">
                      <thead class="bg-gray-50"><tr><th class="p-2 text-left">Metode</th><th class="p-2 text-left">Ref</th><th class="p-2 text-right">Nominal</th></tr></thead>
                      <tbody class="divide-y">
                        ${pays.map(p => `
                          <tr>
                            <td class="p-2">${p.method}</td>
                            <td class="p-2">${p.ref_no ?? ''}</td>
                            <td class="p-2 text-right">${fmtMoney(p.amount)}</td>
                          </tr>`).join('')}
                      </tbody>
                    </table>`}
              </div>
              <div class="p-3 rounded-lg bg-white shadow">
                <div class="flex items-center justify-between">
                  <div class="font-semibold">Total</div>
                  <div class="font-semibold">${fmtMoney(s.total)}</div>
                </div>
                <a target="_blank" href="/kasir/history/${s.id}/invoice"
                   class="mt-3 inline-flex items-center px-3 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                  Cetak Invoice
                </a>
              </div>
            </div>`;

    return html;
  }

  // Toggle & load detail
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-detail');
    if (!btn) return;

    const id = btn.getAttribute('data-sale-id');
    const row = document.getElementById(`row-detail-${id}`);
    const cont = document.querySelector(`[data-detail-container="${id}"]`);
    if (!row || !cont) return;

    // collapse jika sudah terbuka
    if (!row.classList.contains('hidden') && cont.dataset.loaded === '1') {
      row.classList.add('hidden');
      return;
    }

    row.classList.remove('hidden');

    if (cont.dataset.loaded === '1') return;

    cont.innerHTML = `<div class="text-gray-500 text-sm">Memuat detail…</div>`;
    try {
      const payload = await fetchDetail(id);
      if (!payload.ok) throw new Error(payload.message || 'Gagal');
      cont.innerHTML = renderDetail(payload);
      cont.dataset.loaded = '1';
    } catch (err) {
      cont.innerHTML = `<div class="text-rose-600 text-sm">Gagal memuat detail.</div>`;
      console.error(err);
    }
  });
})();
// public/js/history.js
(() => {
  const qs  = (s, el=document) => el.querySelector(s);
  const qsa = (s, el=document) => Array.from(el.querySelectorAll(s));

  const formatIDR = (n) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(n || 0);

  // Fallback parser jika tidak ada data-amount (mis. lupa update view)
  const parseIDRText = (txt) => {
    if (!txt) return 0;
    let s = (txt + '').replace(/\s|Rp/gi, '');
    // "1.234,56" -> "1234.56"
    s = s.replace(/\./g, '').replace(',', '.');
    const n = parseFloat(s);
    return isNaN(n) ? 0 : n;
  };

  const computeSum = () => {
    const cells = qsa('td.sale-total');
    let sum = 0;
    for (const td of cells) {
      const attr = td.getAttribute('data-amount');
      const val  = (attr !== null && attr !== '') ? parseFloat(attr) : parseIDRText(td.textContent);
      if (!isNaN(val)) sum += val;
    }
    const target = qs('#sum-amount');
    if (target) target.textContent = formatIDR(sum);
  };

  // Hitung saat halaman siap
  window.addEventListener('DOMContentLoaded', computeSum);

  // Jika Anda memuat ulang tabel via PJAX/HTMX/Alpine (opsional), panggil computeSum() lagi.
  // window.addEventListener('pos:history:updated', computeSum);
})();
