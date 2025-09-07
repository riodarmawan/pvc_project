/* public/js/project.js */
(() => {
  // ---------- Helpers ----------
  const qs  = (s, el=document) => el.querySelector(s);
  const qsa = (s, el=document) => Array.from(el.querySelectorAll(s));
  const money = (n) => 'Rp ' + (Number(n||0).toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2}));
  const num = (v) => {
    if (typeof v === 'number') return v;
    const s = String(v||'').replace(/[^0-9,.-]/g,'').replace(/\./g,'').replace(',','.');
    const n = parseFloat(s);
    return isNaN(n) ? 0 : n;
  };

  const CSRF = () => (qs('meta[name="csrf-token"]')?.getAttribute('content') || '');

  const toast = (msg, type='ok') => {
    const d = document.createElement('div');
    d.className = `fixed top-4 left-1/2 -translate-x-1/2 z-[70] px-4 py-2 rounded-lg text-white shadow
                   ${type==='err'?'bg-rose-600':'bg-emerald-600'}`;
    d.textContent = msg;
    document.body.appendChild(d);
    setTimeout(()=>{ d.style.opacity='0'; d.style.transition='opacity .3s'; setTimeout(()=>d.remove(),300); },1600);
  };

  const postForm = async (url, payload) => {
    const fd = payload instanceof FormData ? payload : new FormData();
    if (!(payload instanceof FormData)) Object.entries(payload||{}).forEach(([k,v])=>fd.append(k,v));
    if (!fd.has('_token')) fd.append('_token', CSRF());

    const res = await fetch(url, {
      method:'POST',
      headers:{'X-Requested-With':'XMLHttpRequest', Accept:'application/json'},
      body:fd
    });

    let data = {};
    try { data = await res.json(); } catch {}

    // gabungkan pesan error validasi jika ada
    if (!res.ok && data?.errors) {
      const first = Object.values(data.errors)[0];
      if (Array.isArray(first) && first.length) data.message = first[0];
    }
    return { ok: res.ok && (data.ok !== false), data };
  };

  // ---------- Cart summary ----------
  const $cartBody = qs('#cart-body');

  const recalcSummary = () => {
    if (!$cartBody) return { items:0, total:0 };

    let items = 0, total = 0;
    qsa('tr[data-row]', $cartBody).forEach(tr => {
      const pcs   = num(qs('.js-cart-qty',tr)?.value);
      const price = num(qs('.js-price',tr)?.textContent);
      items += pcs;
      total += pcs * price;

      const sub = qs('.js-sub', tr);
      if (sub) sub.textContent = (price*pcs).toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2});
    });

    if (qs('#sum-items')) qs('#sum-items').textContent = items;
    if (qs('#sum-total')) qs('#sum-total').textContent = money(total);

    // Paid / change (for CASH)
    const method = qs('#pay-method')?.value || 'CASH';
    const given  = method === 'CASH' ? num(qs('#cash-given')?.value) : total;
    const change = Math.max(0, given - total);

    if (qs('#sum-paid'))   qs('#sum-paid').textContent   = money(given);
    if (qs('#sum-change')) qs('#sum-change').textContent = money(change);

    return { items, total, given, change };
  };

  const addRowIfNotExists = ({id, sku, name, price, qty, len}) => {
    if (!$cartBody) return;

    let tr = qs(`tr[data-row="${id}"]`, $cartBody);
    if (!tr) {
      tr = document.createElement('tr');
      tr.setAttribute('data-row', id);
      tr.innerHTML = `
        <td class="p-3">
          <div class="font-medium">${name || '(tanpa nama)'}</div>
          <div class="text-xs text-gray-500">SKU: ${sku || '-'}</div>
        </td>
        <td class="p-3 text-right">
          <input type="number" min="1" step="1" value="${qty}" class="js-cart-qty w-20 rounded-lg border-gray-300 text-right">
        </td>
        <td class="p-3 text-right">
          <input type="number" min="0" step="0.1" value="${len}" class="js-cart-len w-24 rounded-lg border-gray-300 text-right">
        </td>
        <td class="p-3 text-right">Rp <span class="js-price">${Number(price||0).toLocaleString('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2})}</span></td>
        <td class="p-3 text-right">Rp <span class="js-sub">0,00</span></td>
        <td class="p-3 text-center"><button class="btn-cart-remove text-rose-600 hover:text-rose-700">Hapus</button></td>
      `;
      $cartBody.appendChild(tr);
    } else {
      const qtyEl = qs('.js-cart-qty', tr);
      qtyEl.value = num(qtyEl.value) + qty;
      const lenEl = qs('.js-cart-len', tr);
      if (num(lenEl.value) <= 0 && len > 0) lenEl.value = len;
    }
    recalcSummary();
  };

  // ---------- Add from catalog ----------
  document.addEventListener('click', async (e) => {
    const btn =
      e.target.closest('.btn-add') ||
      e.target.closest('.btn-add-prj'); // dukung 2 varian tombol

    if (!btn) return;

    // cegah submit form/filter di header katalog
    e.preventDefault();

    // ambil data dari atribut tombol, fallback ke DOM table jika tidak ada
    const pid   = btn.getAttribute('data-product-id');
    let sku     = btn.getAttribute('data-sku')   || '';
    let name    = btn.getAttribute('data-name')  || '';
    let price   = Number(btn.getAttribute('data-price') || 0);

    const qtySel = btn.getAttribute('data-qty-input');
    const lenSel = btn.getAttribute('data-len-input');

    const qtyEl = qtySel ? qs(qtySel) : null;
    const lenEl = lenSel ? qs(lenSel) : null;

    // Fallback dari baris tabel bila data-* tidak lengkap
    if (!name || !price) {
      const row = btn.closest('tr');
      if (row) {
        const nameEl  = row.querySelector('.font-medium') || row.querySelector('td:first-child');
        const priceEl = row.querySelector('td:nth-child(2)') || row.querySelector('.js-price');
        name  = name || (nameEl?.textContent?.trim() || '');
        price = price || num(priceEl?.textContent);
      }
    }

    const qty = Math.max(1, num(qtyEl?.value || 1));
    const len = Math.max(0, num(lenEl?.value || 0));

    btn.disabled = true;
    try {
      const { ok, data } = await postForm('/projects/cart/add', { product_id: pid, qty_pcs: qty, len_per: len });
      if (!ok) return toast(data.message || 'Gagal menambah item.', 'err');

      addRowIfNotExists({ id: pid, sku, name, price, qty, len });
      toast('Item ditambahkan.');
    } catch (err) {
      console.error(err);
      toast('Terjadi kesalahan jaringan.', 'err');
    } finally {
      btn.disabled = false;
    }
  });

  // ---------- Cart: update/remove/clear ----------
  document.addEventListener('change', async (e) => {
    const tr = e.target.closest('tr[data-row]');
    if (!tr) return;

    if (e.target.classList.contains('js-cart-qty') || e.target.classList.contains('js-cart-len')) {
      const pid = tr.getAttribute('data-row');
      const qty = Math.max(1, num(qs('.js-cart-qty', tr)?.value));
      const len = Math.max(0, num(qs('.js-cart-len', tr)?.value));
      const { ok, data } = await postForm('/projects/cart/update', { product_id: pid, qty_pcs: qty, len_per: len });
      if (!ok) toast(data.message || 'Gagal memperbarui.', 'err');
      recalcSummary();
    }
  });

  document.addEventListener('click', async (e) => {
    const btnDel = e.target.closest('.btn-cart-remove');
    if (btnDel) {
      const tr  = btnDel.closest('tr[data-row]');
      const pid = tr?.getAttribute('data-row');
      const { ok, data } = await postForm('/projects/cart/remove', { product_id: pid });
      if (ok) { tr.remove(); recalcSummary(); toast('Item dihapus.'); }
      else toast(data.message || 'Gagal menghapus.', 'err');
      return;
    }

    const btnClear = e.target.closest('.btn-cart-clear');
    if (btnClear) {
      const { ok, data } = await postForm('/projects/cart/clear', {});
      if (ok) { if ($cartBody) $cartBody.innerHTML=''; recalcSummary(); toast('Keranjang dikosongkan.'); }
      else toast(data.message || 'Gagal mengosongkan.', 'err');
      return;
    }
  });

  // ---------- Customer modal / ajax forms ----------
  const openModal  = (sel) => { const m=qs(sel); if (m) m.classList.remove('hidden'); };
  const closeModal = (sel) => { const m=qs(sel); if (m) m.classList.add('hidden'); };

  document.addEventListener('click', (e) => {
    const openBtn  = e.target.closest('[data-modal-target]:not(.btn-modal-close)');
    const closeBtn = e.target.closest('.btn-modal-close');
    if (openBtn)  { e.preventDefault(); openModal(openBtn.getAttribute('data-modal-target')); }
    if (closeBtn) { e.preventDefault(); closeModal(closeBtn.getAttribute('data-modal-target')); }
  });

  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form.js-ajax');
    if (!form) return;
    e.preventDefault();

    const submit = form.querySelector('button[type="submit"],button:not([type])');
    submit?.setAttribute('disabled','disabled');
    try {
      const fd = new FormData(form);
      const { ok, data } = await postForm(form.action, fd);
      if (!ok) return toast(data.message || 'Aksi gagal.', 'err');

      toast(data.message || 'Berhasil.');
      // update badge selected customer jika ada id
      if (data.customer_id || fd.get('customer_id')) {
        const id    = data.customer_id || fd.get('customer_id');
        const badge = qs('#selected-cust-badge');
        if (badge) {
          badge.className = 'px-2 py-0.5 rounded bg-green-50 text-green-700 border border-green-200';
          badge.textContent = `Terpilih (ID: ${id})`;
        }
        if (qs('#selected-cust-id')) qs('#selected-cust-id').textContent = id;
      }
      if (form.closest('#modal-customer')) closeModal('#modal-customer');
    } catch (err) {
      console.error(err);
      toast('Terjadi kesalahan jaringan.', 'err');
    } finally {
      submit?.removeAttribute('disabled');
    }
  });

  // ---------- Pembayaran UI ----------
  const methodEl = qs('#pay-method');
  const cashWrap  = qs('#wrap-cash');
  const cashInput = qs('#cash-given');

  const toggleCash = () => {
    const m = methodEl?.value || 'CASH';
    if (!cashWrap) return;
    if (m === 'CASH') cashWrap.classList.remove('hidden');
    else cashWrap.classList.add('hidden');
    recalcSummary();
  };

  methodEl && methodEl.addEventListener('change', toggleCash);
  cashInput && cashInput.addEventListener('input', recalcSummary);

  // ---------- Finalize ----------
  const openHtmlInNewTab = (html) => {
    const w = window.open('', '_blank');
    if (!w) return;
    w.document.open('text/html');
    w.document.write(html || '<p>Tidak ada dokumen</p>');
    w.document.close();
  };

  qs('#btn-finalize')?.addEventListener('click', async () => {
    const title = qs('#project-title')?.value?.trim();
    if (!title) return toast('Judul proyek wajib diisi.', 'err');

    const { items, total } = recalcSummary();
    if (items === 0) return toast('Keranjang masih kosong.', 'err');

    const method = methodEl?.value || 'CASH';
    const given  = method === 'CASH' ? num(cashInput?.value) : total;

    if (method === 'CASH' && given + 0.0001 < total) {
      return toast(`Uang diterima kurang. Total: ${money(total)}.`, 'err');
    }

    const notes  = qs('#project-notes')?.value || '';
    const payload = { title, notes, pay_method: method, cash_given: given };

    const { ok, data } = await postForm('/projects/finalize', payload);
    if (!ok) return toast(data.message || 'Gagal menyimpan proyek.', 'err');

    // kosongkan UI
    if ($cartBody) $cartBody.innerHTML = '';
    recalcSummary();

    // tampilkan dokumen
    if (data.sj_html)      openHtmlInNewTab(data.sj_html);
    if (data.invoice_html) openHtmlInNewTab(data.invoice_html);

    toast('Proyek disimpan & dialokasikan.');
  });

  // ---------- Return page (opsional ringan) ----------
  // Jika halaman return punya input qty dengan class .js-return-qty, hitung totalnya
  const $returnWrap = qs('#return-wrap');
  if ($returnWrap) {
    const updateReturnSummary = () => {
      const totalQty = qsa('.js-return-qty', $returnWrap).reduce((acc,el)=> acc + num(el.value), 0);
      if (qs('#ret-sum-qty')) qs('#ret-sum-qty').textContent = totalQty.toLocaleString('id-ID');
      const btn = qs('#btn-process-return');
      if (btn) btn.disabled = totalQty <= 0;
    };
    $returnWrap.addEventListener('input', (e) => {
      if (e.target.classList.contains('js-return-qty')) updateReturnSummary();
    });
    updateReturnSummary();
  }

  // Init
  toggleCash();
  recalcSummary();
})();
