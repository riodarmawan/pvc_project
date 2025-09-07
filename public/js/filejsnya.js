/* public/js/filejsnya.js
   Sinkron dgn view & ProjectController terbaru
   Fitur:
   • Keranjang ganda: MAIN (berbayar) & LEFTOVER (harga 0, bisa pakai piece_id)
   • Datalist potongan sisa dari /projects/leftover/list (GOOD & belum consumed)
   • Otomatis isi length_m & product_id saat pilih Piece ID
   • Finalize via AJAX (inline_docs) + fallback URL
*/

(() => {
  // =============================
  // Helpers DOM & HTTP
  // =============================
  const $  = (sel, ctx=document) => ctx.querySelector(sel);
  const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));

  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

  async function postJSON(url, payload) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': csrf(),
        'X-Requested-With':'XMLHttpRequest',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload || {})
    });
    const ct = res.headers.get('content-type') || '';
    if (!res.ok) {
      let msg = `HTTP ${res.status}`;
      if (ct.includes('application/json')) {
        try { const j = await res.json(); msg = j.message || JSON.stringify(j); } catch(_){}
      } else {
        try { msg = (await res.text()).slice(0,600); } catch(_){}
      }
      throw new Error(msg);
    }
    if (ct.includes('application/json')) return res.json();
    return {};
  }

  async function getJSON(url) {
    const res = await fetch(url, { headers: {'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json'} });
    if (!res.ok) throw new Error(`GET ${url} -> ${res.status}`);
    return res.json();
  }

  const fmtMoney = n => {
    const x = Number(n || 0);
    const [i, d] = x.toFixed(2).split('.');
    const ii = i.replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    return `Rp ${ii},${d}`;
  };

  // =============================
  // State mirror (UI <-> Session)
  // =============================
  let cartMain = {};     // key=product_id: {product_id, sku, name, uom_id, price, qty_pcs, len_per}
  let cartLeft = {};     // key=product_id: {product_id, sku, name, uom_id, price:0, qty_pcs, len_per, piece_id?}

  const subtotal = row => Number(row.price||0) * Number(row.qty_pcs||0);

  // =============================
  // Render keranjang
  // =============================
function renderCart() {
  const mainBody = $('#cart-main-body');
  const leftBody = $('#cart-left-body');
  if (!mainBody || !leftBody) return;

  mainBody.innerHTML = '';
  leftBody.innerHTML = '';

  const addRow = (tbody, src, row) => {
    const tr = document.createElement('tr');

    // kolom harga: editable hanya untuk LEFTOVER
    const priceCell = (src === 'LEFTOVER')
      ? `<input type="number" min="0" step="1" value="${Number(row.price||0)}"
                 class="w-24 rounded-lg border-gray-300 text-right js-price"
                 data-src="${src}" data-id="${row.product_id}">`
      : `${fmtMoney(row.price||0)}`;

    tr.innerHTML = `
      <td class="p-3">
        <div class="font-medium">${row.name || ('#'+row.product_id)}</div>
        <div class="text-xs text-gray-500">SKU: ${row.sku || '-'}</div>
        ${src==='LEFTOVER' && row.piece_id ? `<div class="text-xs text-amber-600">Piece #${row.piece_id}</div>`:''}
      </td>
      <td class="p-3 text-right">
        <input type="number" min="1" step="1" value="${row.qty_pcs}"
               class="w-20 rounded-lg border-gray-300 text-right js-qty"
               data-src="${src}" data-id="${row.product_id}">
      </td>
      <td class="p-3 text-right">
        <input type="number" min="0" step="0.01" value="${row.len_per||0}"
               class="w-24 rounded-lg border-gray-300 text-right js-len"
               data-src="${src}" data-id="${row.product_id}">
      </td>
      <td class="p-3 text-right">${priceCell}</td>
      <td class="p-3 text-right">${fmtMoney(subtotal(row))}</td>
      <td class="p-3 text-center">
        <button type="button" class="px-2 py-1 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 js-remove"
                data-src="${src}" data-id="${row.product_id}">
          Hapus
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  };

  Object.values(cartMain).forEach(r => addRow(mainBody,'MAIN',r));
  Object.values(cartLeft).forEach(r => addRow(leftBody,'LEFTOVER',r));

  bindCartRowEvents();
  recalcSummary();
}


  // =============================
  // Ringkasan + enable tombol
  // =============================
// --- GANTI fungsi recalcSummary jadi:
function recalcSummary() {
  const lines  = Object.keys(cartMain).length + Object.keys(cartLeft).length;
  const totalMain = Object.values(cartMain).reduce((s,r)=> s + subtotal(r), 0);
  const totalLeft = Object.values(cartLeft).reduce((s,r)=> s + subtotal(r), 0);
  const svcAmt    = Number($('#svc-amount')?.value || 0); // NEW: biaya service dari UI
  const total     = totalMain + totalLeft + svcAmt;       // NEW: include service

  const method = $('#pay-method')?.value || 'CASH';
  const cash   = Number($('#cash-given')?.value || 0);

  $('#sum-items')  && ($('#sum-items').textContent  = String(lines));
  $('#sum-total')  && ($('#sum-total').textContent  = fmtMoney(total));
  $('#sum-paid')   && ($('#sum-paid').textContent   = fmtMoney(method==='CASH' ? cash : total));
  const change = (method === 'CASH') ? Math.max(cash - total, 0) : 0;
  $('#sum-change') && ($('#sum-change').textContent = fmtMoney(change));

  const titleOk    = ($('#project-title')?.value || '').trim().length > 0;
  const hasAnyLine = lines > 0;
  const payOk      = (method !== 'CASH') || (cash + 1e-9 >= total);

  const btn = $('#btn-finalize');
  if (!btn) return;
  const reasons = [];
  if (!titleOk) reasons.push('Isi Judul');
  if (!hasAnyLine) reasons.push('Keranjang kosong');
  if (method === 'CASH' && !payOk) reasons.push('Uang diterima harus ≥ Total');

  btn.disabled = !(titleOk && hasAnyLine && payOk);
  btn.title    = btn.disabled ? reasons.join(' • ') : '';
}

// --- Tambah listener agar service merefresh ringkasan
$('#svc-amount') && $('#svc-amount').addEventListener('input', recalcSummary);
$('#svc-label')  && $('#svc-label').addEventListener('input', () => {}); // tidak mempengaruhi total


  // =============================
  // Event pada baris cart
  // =============================
function bindCartRowEvents() {
  $$('.js-qty').forEach(el => {
    el.addEventListener('change', async (e) => {
      const src = e.target.dataset.src;
      const pid = Number(e.target.dataset.id);
      const qty = Math.max(0, parseInt(e.target.value || '0', 10));
      const lenInput = document.querySelector(`.js-len[data-src="${src}"][data-id="${pid}"]`);
      const len = Math.max(0, parseFloat(lenInput?.value || '0'));
      // ikutkan price jika LEFTOVER (agar tidak ter-reset)
      const priceInput = document.querySelector(`.js-price[data-src="${src}"][data-id="${pid}"]`);
      const price = priceInput ? Math.max(0, parseFloat(priceInput.value || '0')) : undefined;

      try {
        await postJSON(window.PROJ_ROUTES.cartUpdate, {
          source: src, product_id: pid, qty_pcs: qty, len_per: len, ...(price!==undefined?{price}:{})
        });
        const bucket = (src==='MAIN') ? cartMain : cartLeft;
        if (qty === 0) { delete bucket[pid]; }
        else { bucket[pid].qty_pcs = qty; bucket[pid].len_per = len; if (price!==undefined) bucket[pid].price = price; }
        renderCart();
      } catch (err) { alert(err.message); }
    });
  });

  $$('.js-len').forEach(el => {
    el.addEventListener('change', async (e) => {
      const src = e.target.dataset.src;
      const pid = Number(e.target.dataset.id);
      const len = Math.max(0, parseFloat(e.target.value || '0'));
      const qtyInput = document.querySelector(`.js-qty[data-src="${src}"][data-id="${pid}"]`);
      const qty = Math.max(0, parseInt(qtyInput?.value || '0', 10));
      const priceInput = document.querySelector(`.js-price[data-src="${src}"][data-id="${pid}"]`);
      const price = priceInput ? Math.max(0, parseFloat(priceInput.value || '0')) : undefined;

      try {
        await postJSON(window.PROJ_ROUTES.cartUpdate, {
          source: src, product_id: pid, qty_pcs: qty, len_per: len, ...(price!==undefined?{price}:{})
        });
        const bucket = (src==='MAIN') ? cartMain : cartLeft;
        if (qty === 0) { delete bucket[pid]; }
        else { bucket[pid].qty_pcs = qty; bucket[pid].len_per = len; if (price!==undefined) bucket[pid].price = price; }
        renderCart();
      } catch (err) { alert(err.message); }
    });
  });

  // NEW: ubah harga (LEFTOVER)
  $$('.js-price').forEach(el => {
    el.addEventListener('change', async (e) => {
      const src = e.target.dataset.src; // LEFTOVER
      const pid = Number(e.target.dataset.id);
      const price = Math.max(0, parseFloat(e.target.value || '0'));
      const qtyInput = document.querySelector(`.js-qty[data-src="${src}"][data-id="${pid}"]`);
      const lenInput = document.querySelector(`.js-len[data-src="${src}"][data-id="${pid}"]`);
      const qty = Math.max(0, parseInt(qtyInput?.value || '0', 10));
      const len = Math.max(0, parseFloat(lenInput?.value || '0'));

      try {
        await postJSON(window.PROJ_ROUTES.cartUpdate, {
          source: src, product_id: pid, qty_pcs: qty, len_per: len, price
        });
        cartLeft[pid].price = price;
        renderCart();
      } catch (err) { alert(err.message); }
    });
  });

  $$('.js-remove').forEach(el => {
    el.addEventListener('click', async (e) => {
      const src = e.currentTarget.dataset.src;
      const pid = Number(e.currentTarget.dataset.id);
      try {
        await postJSON(window.PROJ_ROUTES.cartRemove, { source: src, product_id: pid });
        const bucket = (src==='MAIN') ? cartMain : cartLeft;
        delete bucket[pid];
        renderCart();
      } catch (err) { alert(err.message); }
    });
  });
}


  // =============================
  // Katalog: tambah MAIN
  // =============================
  $$('.btn-add-main').forEach(btn => {
    btn.addEventListener('click', async () => {
      const pid = Number(btn.dataset.productId);
      const sku = btn.dataset.sku;
      const name = btn.dataset.name;
      const price = Number(btn.dataset.price || 0);
      const qtyInput = $(btn.dataset.qtyInput);
      const lenInput = $(btn.dataset.lenInput);
      const qty = Math.max(1, parseInt(qtyInput?.value || '1', 10));
      const len = Math.max(0, parseFloat(lenInput?.value || '0'));

      try {
        await postJSON(window.PROJ_ROUTES.cartAdd, {
          source: 'MAIN', product_id: pid, qty_pcs: qty, len_per: len
        });
        cartMain[pid] = cartMain[pid] || {product_id: pid, sku, name, uom_id: 0, price, qty_pcs:0, len_per:len};
        if (cartMain[pid].len_per <= 0 && len > 0) cartMain[pid].len_per = len;
        cartMain[pid].price = price;
        cartMain[pid].qty_pcs += qty;
        renderCart();
      } catch (err) { alert(err.message); }
    });
  });

  // =============================
  // LEFTOVER panel (manual add + optional piece_id)
  // =============================
  const btnAddLeft = $('#btn-add-leftover');
  const leftPidEl  = $('#left-product-id');
  const leftPieceEl= $('#left-piece-id');
  const leftLenEl  = $('#left-len');
  const leftQtyEl  = $('#left-qty');
  const dataList   = $('#datalist-leftover');

  // cache piece detail { [id]: {length_m, product_id} }
  const LO_CACHE = {};

  async function refreshLeftoverDatalist(productId) {
    if (!dataList) return;
    dataList.innerHTML = '';
    Object.keys(LO_CACHE).forEach(k => delete LO_CACHE[k]);
    if (!productId) return;

    try {
      const url = `${window.PROJ_ROUTES.leftoverList}?product_id=${encodeURIComponent(productId)}&condition=GOOD`;
      const resp = await getJSON(url);
      const rows = resp?.data || [];
      rows.forEach(r => {
        LO_CACHE[r.id] = { length_m: r.length_m, product_id: r.product_id };
        const opt = document.createElement('option');
        opt.value = r.id;                 // piece id
        opt.label = r.label || (`#${r.id} • ${Number(r.length_m).toFixed(3)} m`);
        dataList.appendChild(opt);
      });
    } catch(e){ console.warn(e); }
  }

  if (leftPidEl && dataList) {
    leftPidEl.addEventListener('change', (e)=> refreshLeftoverDatalist(e.target.value));
    if (leftPidEl.value) refreshLeftoverDatalist(leftPidEl.value);
  }

  // Saat pilih piece → isi product & length otomatis
  if (leftPieceEl) {
    leftPieceEl.addEventListener('change', async () => {
      const id = leftPieceEl.value.trim();
      if (!id) return;

      let rec = LO_CACHE[id];
      if (!rec) {
        try {
          const j = await getJSON(`${window.PROJ_ROUTES.leftoverList}?id=${encodeURIComponent(id)}`);
          if (j.ok && j.data) rec = LO_CACHE[id] = { length_m: j.data.length_m, product_id: j.data.product_id };
        } catch(_){}
      }
      if (rec) {
        if (!leftPidEl.value) leftPidEl.value = rec.product_id;
        if (!leftLenEl.value || Number(leftLenEl.value) <= 0) leftLenEl.value = Number(rec.length_m||0).toFixed(3);
        if (leftQtyEl) leftQtyEl.value = 1;
      }
    });
  }

if (btnAddLeft) {
  btnAddLeft.addEventListener('click', async () => {
    const pid      = Number($('#left-product-id')?.value || 0);
    const qty      = Math.max(1, parseInt($('#left-qty')?.value || '1', 10));
    const len      = Math.max(0, parseFloat($('#left-len')?.value || '0'));
    const pieceId  = Number($('#left-piece-select')?.value || 0) || null;   // dropdown baru
    const priceIn  = Math.max(0, parseFloat($('#left-price')?.value || '0'));

    if (!pid) { alert('Pilih potongan sisa terlebih dahulu.'); return; }

    try {
      await postJSON(window.PROJ_ROUTES.cartAdd, {
        source: 'LEFTOVER',
        product_id: pid,
        qty_pcs: qty,
        len_per: len,
        piece_id: pieceId,
        price: priceIn > 0 ? priceIn : 0
      });

      const existing = cartLeft[pid] || {
        product_id: pid, sku: '-', name: `#${pid}`, uom_id: 0, price: 0, qty_pcs:0, len_per: len
      };
      if (existing.len_per <= 0 && len > 0) existing.len_per = len;
      existing.qty_pcs += qty;
      if (priceIn > 0) existing.price = priceIn; // simpan harga manual
      if (pieceId) existing.piece_id = pieceId;
      cartLeft[pid] = existing;

      renderCart();

      // reset input ringan
      $('#left-qty')   && ($('#left-qty').value = '1');
      $('#left-len')   && ($('#left-len').value = '0');
      $('#left-price') && ($('#left-price').value = '0');
      $('#left-piece-select') && ($('#left-piece-select').value = '');
      $('#left-product-id') && ($('#left-product-id').value = '');
    } catch (err) { alert(err.message); }
  });
}

  // =============================
  // Kosongkan keranjang (server clear semua; UI clear sesuai tombol)
  // =============================
  $$('.btn-cart-clear').forEach(btn => {
    btn.addEventListener('click', async () => {
      const target = btn.dataset.target; // MAIN/LEFTOVER
      try {
        await postJSON(window.PROJ_ROUTES.cartClear, {});
        if (target === 'MAIN') cartMain = {};
        if (target === 'LEFTOVER') cartLeft = {};
        if (target !== 'MAIN' && target !== 'LEFTOVER') { cartMain = {}; cartLeft = {}; }
        renderCart();
      } catch (err) { alert(err.message); }
    });
  });

  // =============================
  // Customer forms (ajax)
  // =============================
  $$('.js-ajax').forEach(form => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      const url = form.getAttribute('action');
      const obj = {};
      fd.forEach((v,k)=> obj[k]=v);
      try {
        const res = await postJSON(url, obj);
        if (res && res.customer_id) {
          const badge = $('#selected-cust-badge');
          if (badge) {
            badge.className = 'px-2 py-0.5 rounded bg-green-50 text-green-700 border border-green-200';
            badge.innerHTML = `Terpilih (ID: <span id="selected-cust-id">${res.customer_id}</span>)`;
          }
        }
        const targetSel = form.closest('#modal-customer') ? '#modal-customer' : null;
        if (targetSel) toggleModal(targetSel, false);
      } catch (err) { alert(err.message); }
    });
  });

  // =============================
  // Modal helpers
  // =============================
  function toggleModal(sel, show) {
    const el = $(sel);
    if (!el) return;
    if (show) el.classList.remove('hidden'); else el.classList.add('hidden');
  }
  $$('[data-modal-target]').forEach(btn => {
    btn.addEventListener('click', () => toggleModal(btn.dataset.modalTarget, true));
  });
  $$('.btn-modal-close').forEach(btn => {
    btn.addEventListener('click', () => toggleModal(btn.dataset.modalTarget, false));
  });

  // =============================
  // Payment behaviour
  // =============================
  const payMethod = $('#pay-method');
  const cashWrap  = $('#wrap-cash');
  const cashInput = $('#cash-given');

  function updatePayUI() {
    const m = payMethod?.value || 'CASH';
    if (cashWrap) cashWrap.style.display = (m === 'CASH') ? '' : 'none';
    recalcSummary();
  }
  if (payMethod) payMethod.addEventListener('change', updatePayUI);
  if (cashInput) cashInput.addEventListener('input', recalcSummary);
  if ($('#project-title')) $('#project-title').addEventListener('input', recalcSummary);

  // =============================
  // Finalize (AJAX + prewarm popup + redirect)
  // =============================
  const btnFinalize = $('#btn-finalize');
// =============================
// Finalize (SUBMIT FORM -> redirect ke /projects)
// =============================
// =============================
// Finalize (SUBMIT FORM -> redirect ke /projects)
// =============================
// Finalize (AJAX + Buka Tab Cetak + Redirect)
// =============================
// GANTI BLOK LAMA DI filejsnya.js DENGAN YANG INI

// =============================
// Finalize (AJAX + Buka Tab Cetak + Redirect)
// =============================
if (btnFinalize) {
  btnFinalize.addEventListener('click', async (e) => {
    e.preventDefault();
    e.stopPropagation();

    btnFinalize.disabled = true;
    btnFinalize.innerHTML = `
      <svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <circle cx="12" cy="12" r="10" stroke-width="4" stroke-opacity="0.25"></circle>
        <path d="M12 2a10 10 0 0 1 10 10" stroke-width="4"></path>
      </svg>
      Memproses...`;

    const title    = ($('#project-title')?.value || '').trim();
    const notes    = ($('#project-notes')?.value || '').trim();
    const method   = $('#pay-method')?.value || 'CASH';
    const cash     = Number($('#cash-given')?.value || 0);
    const svcLbl   = ($('#svc-label')?.value || '').trim();
    const svcAmt   = Number($('#svc-amount')?.value || 0);

    const totalMain = Object.values(cartMain).reduce((s, r) => s + subtotal(r), 0);
    const totalLeft = Object.values(cartLeft).reduce((s, r) => s + subtotal(r), 0);
    const total     = totalMain + totalLeft + svcAmt;

    if (!title) {
      alert('Judul proyek wajib diisi.');
      revertButtonState();
      return;
    }
    if ((Object.keys(cartMain).length + Object.keys(cartLeft).length) === 0) {
      alert('Keranjang masih kosong.');
      revertButtonState();
      return;
    }
    if (method === 'CASH' && cash + 1e-9 < total) {
      alert(`Uang diterima kurang dari Total (${fmtMoney(total)}).`);
      revertButtonState();
      return;
    }

    try {
      // ✅ MENGGUNAKAN VARIABEL DARI FILE BLADE
      const response = await postJSON(window.PROJ_ROUTES.finalize, {
        title: title,
        notes: notes,
        pay_method: method,
        cash_given: cash,
        extra_label: svcLbl,
        extra_amount: svcAmt,
        inline_docs: false
      });

      if (response.ok && response.project_id) {
        alert(response.message || 'Proyek berhasil disimpan!');

        if (response.sj_url) window.open(response.sj_url, '_blank');
        if (response.invoice_url) window.open(response.invoice_url, '_blank');

        // ✅ MENGGUNAKAN VARIABEL DARI FILE BLADE
        window.location.href = window.PROJ_ROUTES.index;
      } else {
        throw new Error(response.message || 'Terjadi kesalahan di server.');
      }
    } catch (err) {
      alert(`Gagal menyimpan proyek: ${err.message}`);
      revertButtonState();
    }
  });
}

// Helper untuk mengembalikan state tombol jika terjadi error
function revertButtonState() {
    if (!btnFinalize) return;
    btnFinalize.disabled = false;
    btnFinalize.innerHTML = `
      <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
      </svg>
      Simpan & Cetak (SJ + Invoice)`;
    recalcSummary();
}


  // =============================
  // Debug util
  // =============================
  window.PROJDBG = {
    state() {
      const lines  = Object.keys(cartMain).length + Object.keys(cartLeft).length;

      const method = $('#pay-method')?.value || 'CASH';
      const cash   = Number($('#cash-given')?.value || 0);
      const btn    = $('#btn-finalize');
      const btnDisabled = !!btn?.disabled;
      const titleOk = ($('#project-title')?.value || '').trim().length > 0;
      const hasAny = lines > 0;
    const totalMain = Object.values(cartMain).reduce((s,r)=> s + subtotal(r), 0);
const totalLeft = Object.values(cartLeft).reduce((s,r)=> s + subtotal(r), 0);
const total     = totalMain + totalLeft;
const payOk     = (method !== 'CASH') || (cash + 1e-9 >= total);


      console.table({
        btnExists: !!btn,
        btnDisabled,
        method,
        total,
        cash,
        titleOK: titleOk,
        hasAny,
        payOK: payOk,
      });
      return {cartMain, cartLeft};
    }
  };

  // Init
  updatePayUI();
  renderCart();
})();
