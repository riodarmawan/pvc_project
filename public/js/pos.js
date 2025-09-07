/* public/js/pos.js */
(() => {
  /* =========================
   * Utilitas Umum
   * ========================= */
  const qs  = (sel, el = document) => el.querySelector(sel);
  const qsa = (sel, el = document) => Array.from(el.querySelectorAll(sel));

  const CSRF = () => {
    const m = qs('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  };

  const isJsonResponse = (res) => {
    const ct = res.headers.get('content-type') || '';
    return ct.includes('application/json');
  };

  const toast = (msg, type = 'success') => {
    const box = document.createElement('div');
    box.className =
      `fixed top-4 left-1/2 -translate-x-1/2 z-[60] px-4 py-2 rounded-xl shadow-lg text-white 
       ${type === 'error' ? 'bg-rose-600' : 'bg-emerald-600'}`;
    box.textContent = msg;
    document.body.appendChild(box);
    setTimeout(() => {
      box.style.transition = 'opacity .3s ease';
      box.style.opacity = '0';
      setTimeout(() => box.remove(), 300);
    }, 1800);
  };

  const goCheckout = () => {
    window.location.href = '/kasir/checkout';
  };

  const disableEl = (el, yes = true) => {
    if (!el) return;
    el.disabled = !!yes;
    if (yes) el.classList.add('opacity-60', 'pointer-events-none');
    else el.classList.remove('opacity-60', 'pointer-events-none');
  };

  /* =========================
   * AJAX helpers
   * ========================= */
  async function postForm(url, formData) {
    if (!formData.has('_token')) formData.append('_token', CSRF());

    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: formData
    });

    if (isJsonResponse(res)) {
      const data = await res.json().catch(() => ({}));
      return { ok: res.ok && (data.ok !== false), data };
    } else {
      // Fallback non-AJAX: follow redirect
      window.location.href = res.url || '/kasir/checkout';
      return { ok: true, data: {} };
    }
  }

  /* =========================
   * Render partials (Checkout)
   * ========================= */
  function refreshPanels(html) {
    if (!html || typeof html !== 'object') return;

    if (html.cart && qs('#cart-list')) {
      qs('#cart-list').innerHTML = html.cart;
    }
    if (html.customer && qs('#customer-panel')) {
      qs('#customer-panel').innerHTML = html.customer;
    }
    if (html.payments && qs('#payments-panel')) {
      qs('#payments-panel').innerHTML = html.payments;
    }
    if (html.summary && qs('#summary-panel')) {
      qs('#summary-panel').innerHTML = html.summary;
    }
  }

  function showInvoice(html) {
    const modal = qs('#modal-invoice');
    const area  = qs('#invoice-content');
    if (!modal || !area) return;
    area.innerHTML = html || '<p class="text-sm text-gray-500">Tidak ada data invoice.</p>';
    modal.classList.remove('hidden');
  }

  /* =========================
   * Event Delegation (global)
   * ========================= */

  // 1) Klik tombol tambah dari katalog
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-add');
    if (!btn) return;

    const pid = btn.getAttribute('data-product-id');
    const qtySel = btn.getAttribute('data-qty-input');
    const qtyEl = qtySel ? qs(qtySel) : null;
    const qty = Math.max(1, parseInt(qtyEl && qtyEl.value ? qtyEl.value : '1', 10) || 1);

    disableEl(btn, true);
    try {
      const fd = new FormData();
      fd.append('product_id', pid);
      fd.append('qty', qty);

      const { ok, data } = await postForm('/kasir/cart/add', fd);
      if (ok) {
        toast(data.message || 'Item ditambahkan.');
        // tetap di katalog; user pindah ke checkout via tombol khusus
      } else {
        toast(data.message || 'Gagal menambah item.', 'error');
      }
    } catch (err) {
      console.error(err);
      toast('Terjadi kesalahan jaringan.', 'error');
    } finally {
      disableEl(btn, false);
    }
  });

  // 2) Tombol “Ke Checkout”
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-go-checkout');
    if (!btn) return;
    goCheckout();
  });

  /* =========================
   * MODAL: satu-satunya handler open/close
   * ========================= */
  const openModal = (sel) => {
    const m = typeof sel === 'string' ? qs(sel) : sel;
    if (m) m.classList.remove('hidden');
  };
  const closeModal = (sel) => {
    const m = typeof sel === 'string' ? qs(sel) : sel;
    if (m) m.classList.add('hidden');
  };

  // Open / Close by button (delegation)
  document.addEventListener('click', (e) => {
    const openBtn = e.target.closest('[data-modal-target]');
    if (openBtn && !openBtn.classList.contains('btn-modal-close')) {
      e.preventDefault();
      openModal(openBtn.getAttribute('data-modal-target'));
      return;
    }
    const closeBtn = e.target.closest('.btn-modal-close');
    if (closeBtn) {
      e.preventDefault();
      closeModal(closeBtn.getAttribute('data-modal-target') || '#modal-customer');
    }
  });

  // Close when click backdrop (root modal div)
  document.addEventListener('click', (e) => {
    const overlay = e.target;
    if (!overlay.id || !overlay.id.startsWith('modal-')) return;
    if (!overlay.classList.contains('fixed')) return;
    // klik tepat di overlay (bukan content)
    if (overlay === e.target) closeModal(overlay);
  });

  /* =========================
   * Intersep semua form `.js-ajax`
   * ========================= */
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form.js-ajax');
    if (!form) return;

    e.preventDefault();
    const submitBtn = form.querySelector('button[type="submit"],button:not([type])');

    disableEl(submitBtn, true);
    try {
      const fd = new FormData(form);
      const { ok, data } = await postForm(form.action, fd);

      if (!ok) {
        toast(data.message || 'Aksi gagal.', 'error');
        return;
      }

      if (data.message) toast(data.message);
      if (data.html) refreshPanels(data.html);
      if (data.invoice_html) showInvoice(data.invoice_html);

      // Tutup modal customer jika quick create sukses
      if (form.closest('#modal-customer')) closeModal('#modal-customer');
    } catch (err) {
      console.error(err);
      toast('Terjadi kesalahan jaringan.', 'error');
    } finally {
      disableEl(submitBtn, false);
    }
  });

  // 6) Auto-hide flash success dari server
  window.addEventListener('DOMContentLoaded', () => {
    const flash = qs('[data-flash-auto-hide]');
    if (flash) setTimeout(() => flash.remove(), 2500);
  });
})();
