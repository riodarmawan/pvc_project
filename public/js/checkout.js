/**
 * checkout.js — AJAX checkout handler
 * Handles: cart CRUD, customer search, payment management, finalize
 */

// ============================================================
// HELPERS
// ============================================================
function fmt(n) {
  return new Intl.NumberFormat('id-ID').format(Math.round(n));
}

function toast(msg, type = 'success') {
  const c = document.getElementById('toast-container');
  if (!c) return;
  const colors = {
    success: 'bg-emerald-50 border-emerald-200 text-emerald-800',
    error: 'bg-red-50 border-red-200 text-red-800',
    info: 'bg-blue-50 border-blue-200 text-blue-800',
  };
  const el = document.createElement('div');
  el.className = `toast-enter rounded-lg border px-3 py-2 shadow-lg text-sm font-medium ${colors[type] || colors.info}`;
  el.textContent = msg;
  c.appendChild(el);
  setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; setTimeout(() => el.remove(), 300); }, 2500);
}

async function postJSON(url, data) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    body: JSON.stringify(data),
  });
  return res.json();
}

function disableBtn(btn, disabled) {
  if (!btn) return;
  btn.disabled = disabled;
  btn.classList.toggle('opacity-50', disabled);
  btn.classList.toggle('cursor-not-allowed', disabled);
}

// ============================================================
// CART
// ============================================================
function checkoutUpdateQty(productId, newQty) {
  newQty = parseInt(newQty, 10);
  if (isNaN(newQty) || newQty < 0) return;
  if (newQty === 0) { checkoutRemoveItem(productId); return; }

  postJSON(ROUTES.cartUpdate, { product_id: productId, qty: newQty })
    .then(res => {
      if (res.ok) {
        refreshCheckoutPanels(res.html);
      } else {
        toast(res.message || 'Gagal update', 'error');
      }
    })
    .catch(() => toast('Gagal menghubungi server', 'error'));
}

function checkoutRemoveItem(productId) {
  postJSON(ROUTES.cartRemove, { product_id: productId })
    .then(res => {
      if (res.ok) {
        toast('Item dihapus');
        refreshCheckoutPanels(res.html);
      }
    })
    .catch(() => toast('Gagal menghubungi server', 'error'));
}

function checkoutClearCart() {
  if (!confirm('Kosongkan seluruh keranjang?')) return;
  postJSON(ROUTES.cartClear, {})
    .then(res => {
      if (res.ok) {
        toast('Keranjang dikosongkan');
        refreshCheckoutPanels(res.html);
      }
    })
    .catch(() => toast('Gagal menghubungi server', 'error'));
}

// ============================================================
// REFRESH PARTIALS
// ============================================================
function refreshCheckoutPanels(html) {
  if (!html || typeof html !== 'object') { location.reload(); return; }

  if (html.cart) {
    document.getElementById('cart-items').innerHTML = html.cart;
  }
  if (html.customer) {
    document.getElementById('customer-panel').innerHTML = html.customer;
    rebindCustomerSearch();
  }
  if (html.payments) {
    document.getElementById('payments-panel').innerHTML = html.payments;
  }
  if (html.summary) {
    document.getElementById('summary-panel').innerHTML = html.summary;
  }

  // Update cart count badge
  const items = document.querySelectorAll('#cart-tbody tr[data-product-id]');
  const badge = document.getElementById('cart-count');
  if (badge) badge.textContent = items.length;
}

// ============================================================
// CUSTOMER SEARCH (AJAX)
// ============================================================
let customerSearchTimeout;

function rebindCustomerSearch() {
  const input = document.getElementById('customer-search');
  if (!input) return;

  input.addEventListener('input', () => {
    clearTimeout(customerSearchTimeout);
    const q = input.value.trim();
    const results = document.getElementById('customer-results');
    if (!results) return;
    if (q.length < 2) { results.classList.add('hidden'); return; }

    customerSearchTimeout = setTimeout(() => {
      // Fetch full checkout page HTML to get customer results
      fetch(`${ROUTES.customerSearch}?cq=${encodeURIComponent(q)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
      })
      .then(r => r.json())
      .then(data => {
        if (data.ok && data.html && data.html.customer) {
          // Parse the customer partial HTML to extract results
          const parser = new DOMParser();
          const doc = parser.parseFromString(data.html.customer, 'text/html');
          const resultsDiv = doc.querySelector('#customer-results');
          if (resultsDiv) {
            results.innerHTML = resultsDiv.innerHTML;
            results.classList.remove('hidden');
          } else {
            results.classList.add('hidden');
          }
        }
      })
      .catch(() => { results.classList.add('hidden'); });
    }, 300);
  });

  // Close results on outside click
  document.addEventListener('click', (e) => {
    if (!e.target.closest('#customer-search') && !e.target.closest('#customer-results')) {
      const r = document.getElementById('customer-results');
      if (r) r.classList.add('hidden');
    }
  });
}

function checkoutSelectCustomer(id, name) {
  postJSON(ROUTES.customerSelect, { customer_id: id })
    .then(res => {
      if (res.ok) {
        toast('Pelanggan: ' + name);
        refreshCheckoutPanels(res.html);
      }
    })
    .catch(() => toast('Gagal memilih pelanggan', 'error'));
}

function checkoutClearCustomer() {
  postJSON(ROUTES.customerSelect, { clear: 1 })
    .then(res => {
      if (res.ok) refreshCheckoutPanels(res.html);
    })
    .catch(() => toast('Gagal', 'error'));
}

// ============================================================
// CUSTOMER MODAL
// ============================================================
function openCustomerModal() {
  document.getElementById('modal-customer').classList.remove('hidden');
  const nameInput = document.querySelector('#modal-customer input[name="name"]');
  if (nameInput) nameInput.focus();
}

function closeCustomerModal() {
  document.getElementById('modal-customer').classList.add('hidden');
  const form = document.getElementById('quick-customer-form');
  if (form) form.reset();
}

// ============================================================
// PAYMENT
// ============================================================
let selectedPayMethod = 'CASH';

function selectPayMethod(method) {
  selectedPayMethod = method;
  // Update button styles
  document.querySelectorAll('.paymethod-btn').forEach(btn => {
    btn.className = btn.className
      .replace('border-emerald-500 bg-emerald-50', 'border-slate-200')
      .replace('text-emerald-700', 'text-slate-500')
      .replace('font-semibold', 'font-medium');
  });
  const active = document.getElementById('paymethod-' + method);
  if (active) {
    active.className = active.className
      .replace('border-slate-200', 'border-emerald-500 bg-emerald-50')
      .replace('text-slate-500', 'text-emerald-600')
      .replace('text-[10px] font-medium', 'text-[10px] font-semibold');
    // Fix the text color for the label
    const label = active.querySelector('span');
    if (label) {
      label.className = label.className
        .replace('text-slate-500', 'text-emerald-700')
        .replace('font-medium', 'font-semibold');
    }
  }
}

function setQuickAmount(type) {
  const summaryTotal = document.querySelector('#summary-panel');
  if (!summaryTotal) return;
  const totalText = summaryTotal.textContent;
  const totalMatch = totalText.match(/Subtotal[\s\S]*?Rp\s*([\d.]+)/);
  const total = totalMatch ? parseInt(totalMatch[1].replace(/\./g, ''), 0) : 0;

  const input = document.getElementById('pay-amount');
  if (!input) return;

  if (type === 'exact') {
    input.value = total;
  } else if (type === '50k') {
    const current = parseInt(input.value || '0', 10);
    input.value = Math.ceil((current + 50000) / 10000) * 10000;
  } else if (type === '100k') {
    const current = parseInt(input.value || '0', 10);
    input.value = Math.ceil((current + 100000) / 10000) * 10000;
  }
}

function checkoutAddPayment() {
  const amount = parseFloat(document.getElementById('pay-amount')?.value) || 0;
  if (amount <= 0) { toast('Masukkan jumlah bayar', 'error'); return; }

  const refInput = document.querySelector('#payments-panel input[name="ref_no"]');
  const refNo = refInput ? refInput.value.trim() : '';

  postJSON(ROUTES.paymentAdd, { method: selectedPayMethod, amount: amount, ref_no: refNo || null })
    .then(res => {
      if (res.ok) {
        toast('Pembayaran ditambahkan');
        refreshCheckoutPanels(res.html);
      } else {
        toast(res.message || 'Gagal', 'error');
      }
    })
    .catch(() => toast('Gagal menghubungi server', 'error'));
}

function checkoutClearPayments() {
  postJSON(ROUTES.paymentClear, {})
    .then(res => {
      if (res.ok) {
        toast('Pembayaran direset');
        refreshCheckoutPanels(res.html);
      }
    })
    .catch(() => toast('Gagal', 'error'));
}

// ============================================================
// FINALIZE
// ============================================================
function checkoutFinalize() {
  const btn = document.getElementById('btn-finalize');
  if (!btn || btn.disabled) return;

  if (!confirm('Finalisasi transaksi ini?')) return;

  disableBtn(btn, true);
  btn.innerHTML = '<svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Memproses...';

  postJSON(ROUTES.finalize, {})
    .then(res => {
      if (res.ok) {
        toast('Transaksi berhasil!', 'success');
        // Show invoice
        if (res.invoice_html) {
          document.getElementById('invoice-content').innerHTML = res.invoice_html;
          document.getElementById('modal-invoice').classList.remove('hidden');
        }
        // Reload after delay
        setTimeout(() => location.reload(), 3000);
      } else {
        toast(res.message || 'Gagal memproses', 'error');
        disableBtn(btn, false);
        btn.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Finalisasi Transaksi';
      }
    })
    .catch(() => {
      toast('Gagal menghubungi server', 'error');
      disableBtn(btn, false);
    });
}

// ============================================================
// KEYBOARD SHORTCUTS
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
  rebindCustomerSearch();

  document.addEventListener('keydown', (e) => {
    // F8: Finalize
    if (e.key === 'F8') {
      e.preventDefault();
      checkoutFinalize();
    }
    // Escape: close modals
    if (e.key === 'Escape') {
      closeCustomerModal();
      document.getElementById('modal-invoice')?.classList.add('hidden');
    }
  });

  // Customer modal form submit
  const form = document.getElementById('quick-customer-form');
  if (form) {
    form.addEventListener('submit', e => {
      e.preventDefault();
      const fd = new FormData(form);
      const data = Object.fromEntries(fd.entries());
      postJSON(ROUTES.customerQuick, data)
        .then(res => {
          if (res.ok) {
            toast('Pelanggan ditambahkan');
            closeCustomerModal();
            refreshCheckoutPanels(res.html);
          } else {
            toast(res.message || 'Gagal', 'error');
          }
        })
        .catch(() => toast('Gagal menghubungi server', 'error'));
    });
  }
});
