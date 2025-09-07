// public/js/product_new.js
(() => {
  const qs  = (s, el=document) => el.querySelector(s);
  const qsa = (s, el=document) => Array.from(el.querySelectorAll(s));

  const fmt = new Intl.NumberFormat('id-ID');

  function updatePreview() {
    const hppEl    = qs('#hpp');
    const prevEl   = qs('#price-preview');
    const markupEl = qs('#hpp[data-markup]');
    if (!hppEl || !prevEl || !markupEl) return;

    const hpp    = parseFloat(hppEl.value || '0') || 0;
    const markup = parseFloat(markupEl.getAttribute('data-markup') || '1') || 1;
    const price  = hpp * markup;
    prevEl.textContent = 'Rp ' + fmt.format(price);
  }

  function toggleNewCategory() {
    const cb   = qs('#toggle-new-cat');
    const box  = qs('#new-cat-fields');
    const sel  = qs('select[name="category_id"]');
    if (!cb || !box || !sel) return;

    const on = cb.checked;
    box.classList.toggle('hidden', !on);
    sel.disabled = on;
  }

  document.addEventListener('DOMContentLoaded', () => {
    // preview harga jual
    updatePreview();
    qsa('#hpp').forEach(el => el.addEventListener('input', updatePreview));

    // toggle kategori baru
    toggleNewCategory();
    const tgl = qs('#toggle-new-cat');
    if (tgl) tgl.addEventListener('change', toggleNewCategory);
  });
})();
