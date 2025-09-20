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

    // CRITICAL: Re-bind events after DOM update
    document.dispatchEvent(new CustomEvent('pos:refreshed'));
  }

  function showInvoice(html) {
    const modal = qs('#modal-invoice');
    const area  = qs('#invoice-content');
    if (!modal || !area) return;
    area.innerHTML = html || '<p class="text-sm text-gray-500">Tidak ada data invoice.</p>';
    modal.classList.remove('hidden');
  }

  /* =========================
   * Event Delegation (global) - UPDATED
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

  // 2) Tombol "Ke Checkout"
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-go-checkout');
    if (!btn) return;
    goCheckout();
  });

  // 3) FINALIZE BUTTON - UPDATED
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('#btn-verify-finalize');
    if (!btn || btn.disabled) return;

    e.preventDefault();
    
    if (!confirm('Yakin ingin menyelesaikan transaksi ini?')) {
      return;
    }

    disableEl(btn, true);
    try {
      const fd = new FormData();
      const { ok, data } = await postForm('/kasir/finalize', fd);
      
      if (ok) {
        toast(data.message || 'Transaksi berhasil diselesaikan!');
        if (data.invoice_html) {
          showInvoice(data.invoice_html);
        }
        if (data.redirect) {
          setTimeout(() => window.location.href = data.redirect, 1500);
        }
      } else {
        toast(data.message || 'Gagal menyelesaikan transaksi.', 'error');
      }
    } catch (err) {
      console.error(err);
      toast('Terjadi kesalahan jaringan.', 'error');
    } finally {
      disableEl(btn, false);
    }
  });

  /* =========================
   * MODAL: Enhanced handler
   * ========================= */
  const openModal = (sel) => {
    const m = typeof sel === 'string' ? qs(sel) : sel;
    if (m) m.classList.remove('hidden');
  };
  
  const closeModal = (sel) => {
    const m = typeof sel === 'string' ? qs(sel) : sel;
    if (m) m.classList.add('hidden');
  };

  // Open / Close by button (delegation) - ENHANCED
  document.addEventListener('click', (e) => {
    // Handle modal open buttons
    const openBtn = e.target.closest('[data-modal-target]');
    if (openBtn && !openBtn.classList.contains('btn-modal-close')) {
      e.preventDefault();
      const targetModal = openBtn.getAttribute('data-modal-target');
      openModal(targetModal);
      return;
    }
    
    // Handle modal close buttons
    const closeBtn = e.target.closest('.btn-modal-close');
    if (closeBtn) {
      e.preventDefault();
      const targetModal = closeBtn.getAttribute('data-modal-target') || '#modal-customer';
      closeModal(targetModal);
      return;
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
   * Form Submission Handler - ENHANCED
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
      if (form.closest('#modal-customer')) {
        closeModal('#modal-customer');
      }
      
      // Reset form jika sukses (kecuali cart update forms)
      if (!form.action.includes('/cart/update')) {
        form.reset();
      }

    } catch (err) {
      console.error(err);
      toast('Terjadi kesalahan jaringan.', 'error');
    } finally {
      disableEl(submitBtn, false);
    }
  });

  /* =========================
   * Price Input Validation - NEW
   * ========================= */
  document.addEventListener('input', (e) => {
    if (e.target.name === 'price') {
      const value = parseFloat(e.target.value) || 0;
      if (value < 0) {
        e.target.value = 0;
        toast('Harga tidak boleh negatif', 'error');
      }
    }
  });

  /* =========================
   * Auto-submit on blur - NEW
   * ========================= */
  document.addEventListener('blur', (e) => {
    if (e.target.name === 'price' || e.target.name === 'qty') {
      const form = e.target.closest('form.js-ajax');
      if (form && form.action.includes('/cart/update')) {
        // Small delay untuk UX
        setTimeout(() => {
          form.dispatchEvent(new Event('submit', { bubbles: true }));
        }, 100);
      }
    }
  });

  /* =========================
   * Re-bind Events After AJAX - CRITICAL FIX
   * ========================= */
  document.addEventListener('pos:refreshed', () => {
    console.log('POS: DOM refreshed, re-binding events...');
    
    // Re-bind any specific events that might have been lost
    // (Most events use delegation so they should work automatically)
    
    // Example: Re-focus first input in forms
    const firstInput = qs('input[type="text"]:not([readonly]), input[type="number"]:not([readonly])');
    if (firstInput && document.activeElement === document.body) {
      setTimeout(() => firstInput.focus(), 100);
    }
  });

  /* =========================
   * Customer Search Enhancement - NEW
   * ========================= */
  let customerSearchTimeout;
  document.addEventListener('input', (e) => {
    if (e.target.name === 'customer_search' || e.target.id === 'customer-search') {
      clearTimeout(customerSearchTimeout);
      const searchInput = e.target;
      const query = searchInput.value.trim();
      
      if (query.length < 2) return;
      
      customerSearchTimeout = setTimeout(() => {
        // Trigger customer search
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('cq', query);
        window.location.href = currentUrl.toString();
      }, 500);
    }
  });

  /* =========================
   * Number Formatting - NEW
   * ========================= */
  function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
  }

  // Format price display on focus out
  document.addEventListener('blur', (e) => {
    if (e.target.type === 'number' && e.target.name === 'price') {
      const value = parseFloat(e.target.value) || 0;
      // Optionally format the display (uncomment if needed)
      // e.target.setAttribute('data-formatted', formatNumber(value));
    }
  });

  /* =========================
   * Initialize on DOM ready
   * ========================= */
  document.addEventListener('DOMContentLoaded', () => {
    console.log('POS System initialized');
    
    // Auto-hide flash messages
    const flash = qs('[data-flash-auto-hide]');
    if (flash) setTimeout(() => flash.remove(), 2500);
    
    // Focus first input if available
    const firstInput = qs('input[type="text"]:not([readonly]), input[type="search"]:not([readonly])');
    if (firstInput) setTimeout(() => firstInput.focus(), 100);
  });

  /* =========================
   * Keyboard Shortcuts - NEW
   * ========================= */
  document.addEventListener('keydown', (e) => {
    // ESC to close modals
    if (e.key === 'Escape') {
      const openModal = qs('.fixed:not(.hidden)[id^="modal-"]');
      if (openModal) {
        closeModal(openModal);
        e.preventDefault();
      }
    }
    
    // F2 to focus search
    if (e.key === 'F2') {
      const searchInput = qs('#q, input[name="q"]');
      if (searchInput) {
        searchInput.focus();
        searchInput.select();
        e.preventDefault();
      }
    }
    
    // F3 to go to checkout
    if (e.key === 'F3') {
      goCheckout();
      e.preventDefault();
    }
  });

})();
