(() => {
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));

  const ROUTES = {
    ajaxTable: '/kasir/history/ajax-table',
    detail:    '/kasir/history',
  };

  let searchTimer = null;

  function getFilterParams() {
    const f = $('#filterForm');
    if (!f) return '';
    const fd = new FormData(f);
    return new URLSearchParams(fd).toString();
  }

  async function loadTable(params) {
    const tbody = $('#historyTableBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-10 text-center text-slate-400 text-sm">
      <svg class="animate-spin h-5 w-5 mx-auto mb-2 text-emerald-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
      Memuat data...
    </td></tr>`;

    try {
      const res = await fetch(`${ROUTES.ajaxTable}?${params}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      });
      if (!res.ok) throw new Error('Network error');
      const data = await res.json();
      if (!data.ok) throw new Error(data.message || 'Gagal');

      tbody.innerHTML = data.table;

      const pag = $('#historyPagination');
      if (pag) pag.innerHTML = data.pagination || '';

      // Update summary stats
      const txn = $('#stat-txn');
      const rev = $('#stat-rev');
      const avg = $('#stat-avg');
      if (txn) txn.textContent = Number(data.totalTxn || 0).toLocaleString('id-ID');
      if (rev) rev.textContent = 'Rp ' + Number(data.totalRev || 0).toLocaleString('id-ID');
      if (avg) {
        const avgVal = data.totalTxn > 0 ? data.totalRev / data.totalTxn : 0;
        avg.textContent = 'Rp ' + Math.round(avgVal).toLocaleString('id-ID');
      }

      // Update pagination links to use AJAX
      $$('#historyPagination a').forEach(a => {
        a.addEventListener('click', (e) => {
          e.preventDefault();
          const url = new URL(a.href);
          const p = url.searchParams.toString();
          loadTable(p);
        });
      });
    } catch (err) {
      tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-10 text-center text-red-500 text-sm">Gagal memuat data. <button onclick="location.reload()" class="underline">Muat ulang</button></td></tr>`;
      console.error(err);
    }
  }

  // AJAX search with debounce
  document.addEventListener('DOMContentLoaded', () => {
    const searchInput = $('#searchInput');
    if (searchInput) {
      searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
          const params = getFilterParams();
          loadTable(params);
        }, 400);
      });
    }

    // Form submit via AJAX
    const form = $('#filterForm');
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const params = getFilterParams();
        loadTable(params);
      });
    }

    // Intercept pagination clicks (initial render)
    $$('#historyPagination a').forEach(a => {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        const url = new URL(a.href);
        const p = url.searchParams.toString();
        loadTable(p);
      });
    });
  });

  // Detail toggle
  const formatIDR = (n) => 'Rp ' + Number(n || 0).toLocaleString('id-ID');

  async function fetchDetail(id) {
    const res = await fetch(`${ROUTES.detail}/${id}/detail`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });
    if (!res.ok) throw new Error('Gagal memuat detail');
    return await res.json();
  }

  function renderDetail(payload) {
    const s = payload.sale;
    const lines = payload.lines || [];
    const pays = payload.payments || [];

    let html = `<div class="grid grid-cols-1 md:grid-cols-3 gap-3">`;
    html += `<div class="p-3 rounded-lg bg-white border border-slate-200">
      <div class="text-xs text-slate-500 mb-1">Transaksi</div>
      <div class="font-semibold text-slate-900">#${s.id}</div>
      <div class="text-xs text-slate-500">${s.datetime}</div>
      <div class="text-xs text-slate-500">Cabang: ${s.branch}</div>
      <div class="text-xs text-slate-500">Pelanggan: ${s.customer || 'Umum'} ${s.phone ? '&bull; ' + s.phone : ''}</div>
    </div>`;
    html += `<div class="p-3 rounded-lg bg-white border border-slate-200 md:col-span-2 overflow-auto">
      <div class="text-xs font-semibold text-slate-700 mb-2">Item</div>
      <table class="w-full text-xs">
        <thead class="bg-slate-50"><tr><th class="p-1.5 text-left">Produk</th><th class="p-1.5 text-right">Qty</th><th class="p-1.5 text-right">Harga</th><th class="p-1.5 text-right">Subtotal</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
          ${lines.map(l => `<tr><td class="p-1.5">${l.name} <span class="text-slate-400">(${l.sku})</span></td><td class="p-1.5 text-right">${parseInt(l.qty)}</td><td class="p-1.5 text-right">${formatIDR(l.price)}</td><td class="p-1.5 text-right">${formatIDR(l.subtotal)}</td></tr>`).join('')}
        </tbody>
      </table>
    </div>`;
    html += `</div>`;

    html += `<div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-2">
      <div class="p-3 rounded-lg bg-white border border-slate-200 md:col-span-2">
        <div class="text-xs font-semibold text-slate-700 mb-2">Pembayaran</div>
        ${pays.length === 0 ? '<div class="text-xs text-slate-400">Tidak ada pembayaran.</div>' :
          `<table class="w-full text-xs"><thead class="bg-slate-50"><tr><th class="p-1.5 text-left">Metode</th><th class="p-1.5 text-left">Ref</th><th class="p-1.5 text-right">Nominal</th></tr></thead><tbody class="divide-y divide-slate-100">
            ${pays.map(p => `<tr><td class="p-1.5">${p.method}</td><td class="p-1.5">${p.ref_no || ''}</td><td class="p-1.5 text-right">${formatIDR(p.amount)}</td></tr>`).join('')}
          </tbody></table>`}
      </div>
      <div class="p-3 rounded-lg bg-white border border-slate-200">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold text-slate-700">Total</span>
          <span class="text-sm font-bold text-emerald-600">${formatIDR(s.total)}</span>
        </div>
        <a target="_blank" href="/kasir/history/${s.id}/invoice" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 transition">
          <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          Cetak Invoice
        </a>
        ${s.status === 'PAID' ? `<a href="/kasir/history/${s.id}/refund/confirm" class="inline-flex items-center px-3 py-1.5 mt-2 rounded-lg text-xs font-medium text-white bg-red-600 hover:bg-red-700 transition">Retur</a>` : ''}
      </div>
    </div>`;

    return html;
  }

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-detail');
    if (!btn) return;

    const id = btn.getAttribute('data-sale-id');
    const row = $(`#row-detail-${id}`);
    const cont = $(`[data-detail-container="${id}"]`);
    if (!row || !cont) return;

    if (!row.classList.contains('hidden') && cont.dataset.loaded === '1') {
      row.classList.add('hidden');
      return;
    }

    row.classList.remove('hidden');
    if (cont.dataset.loaded === '1') return;

    cont.innerHTML = `<div class="flex items-center gap-2 text-slate-400 text-xs"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Memuat detail...</div>`;

    try {
      const payload = await fetchDetail(id);
      if (!payload.ok) throw new Error(payload.message || 'Gagal');
      cont.innerHTML = renderDetail(payload);
      cont.dataset.loaded = '1';
    } catch (err) {
      cont.innerHTML = `<div class="text-red-500 text-xs">Gagal memuat detail.</div>`;
      console.error(err);
    }
  });
})();
