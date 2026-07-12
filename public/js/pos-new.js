/**
 * pos-new.js — Single-page POS AJAX handler
 * Handles: cart CRUD, customer, payment, finalize, keyboard shortcuts
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
  const icons = {
    success: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 10-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>',
    error: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>',
    info: '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>',
  };
  const el = document.createElement('div');
  el.className = `toast-enter rounded-xl border px-4 py-3 shadow-lg ${colors[type] || colors.info}`;
  el.innerHTML = `<div class="flex items-start gap-3"><svg class="h-5 w-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">${icons[type] || icons.info}</svg><p class="text-sm font-medium">${msg}</p></div>`;
  c.appendChild(el);
  setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateX(1rem)'; el.style.transition = 'all .3s'; setTimeout(() => el.remove(), 300); }, 3000);
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
let cartAnimating = false;

function addToCart(productId, name, sku, price, stock) {
  if (cartAnimating) return;
  cartAnimating = true;
  postJSON(ROUTES.cartAdd, { product_id: productId, qty: 1 })
    .then(res => {
      if (res.ok) {
        toast(res.message || 'Ditambahkan ke keranjang');
        refreshCart(res.html || null);
      } else {
        toast(res.message || 'Gagal menambahkan', 'error');
      }
    })
    .catch(() => toast('Gagal menghubungi server', 'error'))
    .finally(() => { cartAnimating = false; });
}

function updateCartQty(productId, newQty) {
  if (newQty < 0) return;
  if (newQty === 0) {
    removeCartItem(productId);
    return;
  }
  postJSON(ROUTES.cartUpdate, { product_id: productId, qty: newQty })
    .then(res => {
      if (res.ok) refreshCart(res.html || null);
      else toast(res.message || 'Gagal update', 'error');
    })
    .catch(() => toast('Gagal menghubungi server', 'error'));
}

function removeCartItem(productId) {
  postJSON(ROUTES.cartRemove, { product_id: productId })
    .then(res => {
      if (res.ok) {
        toast('Item dihapus');
        refreshCart(res.html || null);
      }
    })
    .catch(() => toast('Gagal menghubungi server', 'error'));
}

// Alias for shared cart partial compatibility
function checkoutUpdateQty(productId, newQty) {
  updateCartQty(productId, parseInt(newQty, 10));
}

function clearCart() {
  if (!confirm('Kosongkan seluruh keranjang?')) return;
  postJSON(ROUTES.cartClear, {})
    .then(res => {
      if (res.ok) {
        toast('Keranjang dikosongkan');
        refreshCart(res.html || null);
      }
    })
    .catch(() => toast('Gagal menghubungi server', 'error'));
}

function refreshCart(html) {
  if (!html) { location.reload(); return; }

  // Update cart items
  const cartItemsEl = document.getElementById('cart-items');
  if (html.cart && cartItemsEl) cartItemsEl.innerHTML = html.cart;

  // Update customer section
  const custEl = document.getElementById('customer-section');
  if (html.customer && custEl) custEl.innerHTML = html.customer;

  // Update summary
  updateSummaryFromHTML(html);

  // Update cart count badge
  updateCartCount();

  // Update pay button state
  const items = document.querySelectorAll('#cart-items [data-product-id]');
  const btnPay = document.getElementById('btn-pay');
  if (btnPay) disableBtn(btnPay, items.length === 0);
}

function updateSummaryFromHTML(html) {
  if (!html.summary) return;
  // Parse summary HTML to extract values
  const tmp = document.createElement('div');
  tmp.innerHTML = html.summary;
  const totalEl = tmp.querySelector('#summary-total');
  const paidEl = tmp.querySelector('#summary-paid');
  const dueEl = tmp.querySelector('#summary-due');
  if (totalEl) document.getElementById('summary-total').textContent = totalEl.textContent;
  if (paidEl) document.getElementById('summary-paid').textContent = paidEl.textContent;
  if (dueEl) {
    const dueText = dueEl.textContent;
    document.getElementById('summary-due').textContent = dueText;
    const dueVal = parseFloat(dueText.replace(/[^\d]/g, '')) || 0;
    document.getElementById('summary-due').className = `tabular-nums font-bold ${dueVal > 0 ? 'text-red-600' : 'text-emerald-600'}`;
  }
}

function updateCartCount() {
  const items = document.querySelectorAll('#cart-items [data-product-id]');
  const badge = document.getElementById('cart-count-badge');
  if (badge) badge.textContent = items.length;
}

// ============================================================
// CUSTOMER
// ============================================================
let customerSearchTimeout;

document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('customer-search');
  if (!searchInput) return;

  searchInput.addEventListener('input', () => {
    clearTimeout(customerSearchTimeout);
    const q = searchInput.value.trim();
    const results = document.getElementById('customer-results');
    if (!results) return;
    if (q.length < 2) { results.classList.add('hidden'); return; }

    customerSearchTimeout = setTimeout(() => {
      fetch(`/kasir/customer/search?q=${encodeURIComponent(q)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
      })
      .then(r => r.json())
      .then(data => {
        if (!data.ok || !data.customers || data.customers.length === 0) {
          results.classList.add('hidden');
          return;
        }
        results.innerHTML = data.customers.map(c =>
          `<div class="px-3 py-2 hover:bg-emerald-50 cursor-pointer text-sm" onclick="selectCustomer(${c.id}, '${c.name.replace(/'/g, "\\'")}')">
            <span class="font-medium">${c.name}</span>
            ${c.phone ? `<span class="text-slate-400 ml-1">${c.phone}</span>` : ''}
          </div>`
        ).join('');
        results.classList.remove('hidden');
      })
      .catch(() => { results.classList.add('hidden'); });
    }, 300);
  });
});

function selectCustomer(id, name) {
  postJSON(ROUTES.customerSelect, { customer_id: id })
    .then(res => {
      if (res.ok) {
        toast('Pelanggan dipilih: ' + name);
        refreshCart(res.html || null);
      }
    })
    .catch(() => toast('Gagal memilih pelanggan', 'error'));
}

function clearCustomer() {
  postJSON(ROUTES.customerSelect, { clear: 1 })
    .then(res => {
      if (res.ok) refreshCart(res.html || null);
    })
    .catch(() => toast('Gagal', 'error'));
}
const checkoutClearCustomer = clearCustomer;

function openCustomerModal() {
  document.getElementById('customer-modal').classList.remove('hidden');
  document.getElementById('customer-modal').querySelector('input[name="name"]').focus();
}

function closeCustomerModal() {
  document.getElementById('customer-modal').classList.add('hidden');
  document.getElementById('quick-customer-form').reset();
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('quick-customer-form');
  if (!form) return;
  form.addEventListener('submit', e => {
    e.preventDefault();
    const fd = new FormData(form);
    const data = Object.fromEntries(fd.entries());
    postJSON(ROUTES.customerQuick, data)
      .then(res => {
        if (res.ok) {
          toast('Pelanggan ditambahkan');
          closeCustomerModal();
          refreshCart(res.html || null);
        } else {
          toast(res.message || 'Gagal', 'error');
        }
      })
      .catch(() => toast('Gagal menghubungi server', 'error'));
  });
});

// ============================================================
// PAYMENT MODAL
// ============================================================
let selectedMethod = 'CASH';
let payments = [];
let discount = 0;

function openPaymentModal() {
  const items = document.querySelectorAll('#cart-items [data-product-id]');
  if (items.length === 0) { toast('Keranjang kosong', 'error'); return; }

  const total = parseTotal();
  payments = [];
  discount = 0;
  selectedMethod = 'CASH';
  renderPaymentModal(total, 0);
  document.getElementById('payment-modal').classList.remove('hidden');
}

function closePaymentModal() {
  document.getElementById('payment-modal').classList.add('hidden');
}

function parseTotal() {
  const el = document.getElementById('summary-total');
  if (!el) return 0;
  return parseFloat(el.textContent.replace(/[^\d]/g, '')) || 0;
}

function renderPaymentModal(total, paid) {
  const net = Math.max(0, total - discount);
  const due = Math.max(0, net - paid);
  const change = Math.max(0, paid - net);

  const methods = ['CASH', 'CARD', 'QR', 'TRANSFER'];
  const methodLabels = { CASH: 'Tunai', CARD: 'Kartu', QR: 'QRIS', TRANSFER: 'Transfer' };
  const methodIcons = {
    CASH: '<path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>',
    CARD: '<path d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm2 2h8v2H6V6z"/><path d="M4 14h12v2H4z"/>',
    QR: '<path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h2v1H5zM3 13a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1v-4zm2 2v-1h2v1H5zM13 4a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V5a1 1 0 00-1-1h-4zm-1 2V5h2v1h-2zM13 13a1 1 0 00-1 1v1h1a1 1 0 001-1v-1a1 1 0 00-1-1h-1zm3 0h1a1 1 0 011 1v1a1 1 0 01-1 1h-1v-3zm-3 3h1v1h-1v-1zm3 0h1v1h-1v-1zm-3 3v-1h1v1h-1zm3 0h1v1h-1v-1z" clip-rule="evenodd"/>',
    TRANSFER: '<path d="M8 7a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/><path d="M3 5a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm2 0v10h10V5H5z"/>',
  };

  let html = `
    <div class="mb-6">
      <p class="text-sm text-slate-500 mb-1">Total Belanja</p>
      <p class="text-3xl font-bold text-slate-900 tabular-nums">Rp ${fmt(total)}</p>
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium text-slate-700 mb-1">Diskon Nota (opsional)</label>
      <div class="relative">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 text-sm font-medium">Rp</span>
        <input type="number" id="discount-amount" value="${discount || ''}" min="0" max="${total}" placeholder="0"
               class="w-full h-11 pl-10 pr-4 rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-base font-semibold tabular-nums"
               onchange="updateDiscount()">
      </div>
    </div>

    <div class="mb-4">
      <p class="text-sm font-medium text-slate-700 mb-2">Metode Pembayaran</p>
      <div class="grid grid-cols-4 gap-2">
        ${methods.map(m => `
          <button onclick="selectMethod('${m}')" id="method-${m}"
            class="flex flex-col items-center gap-1 p-3 rounded-xl border-2 transition ${m === selectedMethod ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 hover:border-slate-300'}">
            <svg class="h-5 w-5 ${m === selectedMethod ? 'text-emerald-600' : 'text-slate-500'}" fill="currentColor" viewBox="0 0 20 20">${methodIcons[m]}</svg>
            <span class="text-xs font-medium ${m === selectedMethod ? 'text-emerald-700' : 'text-slate-600'}">${methodLabels[m]}</span>
          </button>
        `).join('')}
      </div>
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium text-slate-700 mb-1">Jumlah Bayar</label>
      <div class="relative">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 text-sm font-medium">Rp</span>
        <input type="number" id="pay-amount" value="${due || net}" min="0"
               class="w-full h-12 pl-10 pr-4 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-xl font-bold tabular-nums"
               oninput="updatePaymentPreview()">
      </div>
    </div>

    <div class="flex gap-2 mb-4">
      <button onclick="quickAmount(${due || net})" class="flex-1 h-10 rounded-lg border border-slate-200 text-sm font-medium hover:bg-slate-50 transition">Uang Pas</button>
      <button onclick="quickAmount(${Math.ceil((due || net) / 50000) * 50000})" class="flex-1 h-10 rounded-lg border border-slate-200 text-sm font-medium hover:bg-slate-50 transition">+50rb</button>
      <button onclick="quickAmount(${Math.ceil((due || net) / 100000) * 100000})" class="flex-1 h-10 rounded-lg border border-slate-200 text-sm font-medium hover:bg-slate-50 transition">+100rb</button>
    </div>

    ${payments.length > 0 ? `
      <div class="mb-4 space-y-2">
        <p class="text-sm font-medium text-slate-700">Pembayaran Ditambahkan:</p>
        ${payments.map((p, i) => `
          <div class="flex items-center justify-between bg-slate-50 rounded-lg px-3 py-2">
            <span class="text-sm text-slate-600">${p.method} — Rp ${fmt(p.amount)}</span>
            <button onclick="removePayment(${i})" class="text-red-500 hover:text-red-700"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
          </div>
        `).join('')}
      </div>
    ` : ''}

    <div class="bg-slate-50 rounded-xl p-4 mb-4 space-y-1.5 text-sm">
      <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-medium tabular-nums">Rp ${fmt(total)}</span></div>
      ${discount > 0 ? `<div class="flex justify-between"><span class="text-slate-500">Diskon</span><span class="font-medium text-amber-600 tabular-nums">- Rp ${fmt(discount)}</span></div>` : ''}
      <div class="flex justify-between"><span class="text-slate-500">Total setelah diskon</span><span class="font-semibold tabular-nums">Rp ${fmt(net)}</span></div>
      <div class="flex justify-between"><span class="text-slate-500">Dibayar</span><span class="font-medium text-emerald-600 tabular-nums">Rp ${fmt(paid)}</span></div>
      <div class="flex justify-between"><span class="text-slate-500">Sisa</span><span class="font-bold ${due > 0 ? 'text-red-600' : 'text-emerald-600'} tabular-nums">Rp ${fmt(due)}</span></div>
      ${change > 0 ? `<div class="flex justify-between pt-1 border-t border-slate-200"><span class="text-slate-500">Kembalian</span><span class="font-bold text-emerald-600 tabular-nums">Rp ${fmt(change)}</span></div>` : ''}
    </div>

    <button onclick="addPayment()" ${due <= 0 ? 'disabled' : ''}
            class="w-full h-12 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Tambah Pembayaran
    </button>

    ${due <= 0 ? `
      <button onclick="processPayment()"
              class="w-full h-12 mt-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition flex items-center justify-center gap-2">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        Proses Pembayaran
      </button>
    ` : ''}
  `;

  document.getElementById('payment-content').innerHTML = html;
}

function selectMethod(method) {
  selectedMethod = method;
  const total = parseTotal();
  const paid = payments.reduce((s, p) => s + p.amount, 0);
  renderPaymentModal(total, paid);
}

function quickAmount(amount) {
  document.getElementById('pay-amount').value = amount;
  updatePaymentPreview();
}

function updatePaymentPreview() {
  const total = parseTotal();
  const currentPaid = payments.reduce((s, p) => s + p.amount, 0);
  const thisAmount = parseFloat(document.getElementById('pay-amount')?.value) || 0;
  const paid = currentPaid + thisAmount;
  const due = Math.max(0, total - paid);
  const change = Math.max(0, paid - total);

  // Update preview
  const dueEl = document.getElementById('payment-content');
  if (dueEl) {
    // Re-render with updated values
    renderPaymentModal(total, paid);
    // Restore input value
    const input = document.getElementById('pay-amount');
    if (input) input.value = thisAmount;
  }
}

function updateDiscount() {
  const total = parseTotal();
  const paid = payments.reduce((s, p) => s + p.amount, 0);
  const raw = parseFloat(document.getElementById('discount-amount')?.value) || 0;
  discount = Math.max(0, Math.min(raw, total));
  renderPaymentModal(total, paid);
}

function addPayment() {
  const total = parseTotal();
  const currentPaid = payments.reduce((s, p) => s + p.amount, 0);
  const amount = parseFloat(document.getElementById('pay-amount')?.value) || 0;
  if (amount <= 0) { toast('Jumlah bayar harus lebih dari 0', 'error'); return; }

  payments.push({ method: selectedMethod, amount: amount });
  const paid = payments.reduce((s, p) => s + p.amount, 0);

  postJSON(ROUTES.paymentAdd, { method: selectedMethod, amount: amount })
    .then(res => {
      if (res.ok) {
        toast('Pembayaran ditambahkan');
        renderPaymentModal(total, paid);
      }
    })
    .catch(() => toast('Gagal', 'error'));
}

function removePayment(index) {
  payments.splice(index, 1);
  const total = parseTotal();
  const paid = payments.reduce((s, p) => s + p.amount, 0);

  // Re-sync with server
  postJSON(ROUTES.paymentClear, {})
    .then(() => {
      // Re-add remaining payments sequentially
      const addNext = (i) => {
        if (i >= payments.length) {
          renderPaymentModal(total, paid);
          return;
        }
        postJSON(ROUTES.paymentAdd, payments[i]).then(() => addNext(i + 1));
      };
      if (payments.length === 0) renderPaymentModal(total, 0);
      else addNext(0);
    });
}

function processPayment() {
  const btn = event.target;
  disableBtn(btn, true);
  btn.innerHTML = '<svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Memproses...';

  postJSON(ROUTES.finalize, { discount })
    .then(res => {
      if (res.ok) {
        closePaymentModal();
        // Show invoice
        if (res.invoice_html) {
          document.getElementById('invoice-content').innerHTML = res.invoice_html;
          document.getElementById('invoice-modal').classList.remove('hidden');
        }
        toast('Transaksi berhasil!', 'success');
        // Reload after a delay
        setTimeout(() => location.reload(), 500);
      } else {
        toast(res.message || 'Gagal memproses', 'error');
        disableBtn(btn, false);
        btn.innerHTML = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Proses Pembayaran';
      }
    })
    .catch(() => {
      toast('Gagal menghubungi server', 'error');
      disableBtn(btn, false);
    });
}

// ============================================================
// INVOICE
// ============================================================
function closeInvoiceModal() {
  document.getElementById('invoice-modal').classList.add('hidden');
}

function printInvoice() {
  const content = document.getElementById('invoice-content').innerHTML;
  const w = window.open('', '_blank', 'width=400,height=600');
  w.document.write(`<html><head><title>Struk</title><style>body{font-family:monospace;font-size:12px;padding:16px;}table{width:100%;}th,td{padding:2px 4px;text-align:left;}.text-right{text-align:right;}b{font-weight:bold;}</style></head><body>${content}</body></html>`);
  w.document.close();
  w.print();
}

// ============================================================
// KEYBOARD SHORTCUTS
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('search-input');

  // Global keyboard shortcuts
  document.addEventListener('keydown', e => {
    // F2: Focus search
    if (e.key === 'F2') {
      e.preventDefault();
      searchInput?.focus();
    }
    // F3: Focus search (alias)
    if (e.key === 'F3') {
      e.preventDefault();
      searchInput?.focus();
    }
    // F4: Open payment
    if (e.key === 'F4') {
      e.preventDefault();
      openPaymentModal();
    }
    // Escape: close modals
    if (e.key === 'Escape') {
      closePaymentModal();
      closeInvoiceModal();
      closeCustomerModal();
    }
  });
});

// ============================================================
// SEARCH & FILTER (AJAX, debounced)
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('search-input');
  const categoryFilter = document.getElementById('category-filter');
  let searchTimeout;

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => applyFilters(), 350);
    });
  }

  if (categoryFilter) {
    categoryFilter.addEventListener('change', () => applyFilters());
  }
});

function applyFilters() {
  const q = document.getElementById('search-input')?.value || '';
  const catId = document.getElementById('category-filter')?.value || '';
  const params = new URLSearchParams();
  if (q) params.set('q', q);
  if (catId) params.set('cat_id', catId);
  params.set('ajax_catalog', '1');

  loadCatalog(ROUTES.posPage + '?' + params.toString());
}

function loadCatalog(url) {
  const scroll = document.getElementById('product-scroll');
  if (!scroll) return;

  // Show skeleton
  const grid = document.getElementById('product-grid');
  if (grid) {
    grid.innerHTML = Array.from({length: 8}, () =>
      '<div class="skeleton h-40 rounded-xl"></div>'
    ).join('');
  }

  fetch(url, {
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
  })
  .then(r => r.json())
  .then(data => {
    if (data.html) {
      scroll.innerHTML = data.html;
    }
  })
  .catch(() => {
    // Fallback to page reload on error
    window.location.href = url.replace(/[?&]ajax_catalog=1/, '').replace(/\?$/, '');
  });
}

// Intercept pagination clicks within AJAX-loaded content
document.addEventListener('click', e => {
  const link = e.target.closest('#product-scroll a[href]');
  if (!link) return;
  e.preventDefault();
  const url = new URL(link.href);
  url.searchParams.set('ajax_catalog', '1');
  loadCatalog(url.toString());
});

// Aliases for checkout partials (shared between POS and checkout pages)
const checkoutRemoveItem = removeCartItem;
const checkoutAddPayment = addPayment;
const checkoutFinalize = processPayment;
const checkoutClearPayments = () => {
  payments = [];
  postJSON(ROUTES.paymentClear, {}).then(() => {
    const total = parseTotal();
    renderPaymentModal(total, 0);
  });
};
