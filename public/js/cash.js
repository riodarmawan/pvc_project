(() => {
  const qs  = (sel, el=document) => el.querySelector(sel);

  const CSRF = () => {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  };

  const toast = (msg, type='ok') => {
    const el = document.createElement('div');
    el.className = `fixed top-4 left-1/2 -translate-x-1/2 z-[60] px-4 py-2 rounded-xl text-white shadow
      ${type==='error'?'bg-rose-600':'bg-emerald-600'}`;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(()=>{el.style.opacity='0'; el.style.transition='opacity .3s'; setTimeout(()=>el.remove(),300)}, 1700);
  };

  const isJson = (res) => (res.headers.get('content-type')||'').includes('application/json');

  async function postForm(url, fd) {
    if (!fd.has('_token')) fd.append('_token', CSRF());
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' },
      body: fd
    });
    if (isJson(res)) {
      const data = await res.json().catch(()=>({}));
      return { ok: res.ok && (data.ok!==false), data };
    } else {
      window.location.reload();
      return { ok:true, data:{} };
    }
  }

  // Intersep semua form .js-ajax
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form.js-ajax');
    if (!form) return;

    e.preventDefault();
    const btn = form.querySelector('button[type="submit"],button:not([type])');
    btn && (btn.disabled = true);

    try {
      const fd = new FormData(form);
      const { ok, data } = await postForm(form.action, fd);
      if (!ok) {
        toast(data.message || 'Gagal menyimpan.', 'error');
        return;
      }
      if (data.message) toast(data.message);

      if (data.html) {
        data.html.summary && qs('#summary-panel') && (qs('#summary-panel').innerHTML = data.html.summary);
        data.html.table   && qs('#table-panel')   && (qs('#table-panel').innerHTML   = data.html.table);
      }

      // reset nominal & memo
      form.querySelector('input[name="amount"]') && (form.querySelector('input[name="amount"]').value = '');
      form.querySelector('input[name="memo"]') && (form.querySelector('input[name="memo"]').value = '');

    } catch(err) {
      console.error(err);
      toast('Terjadi kesalahan jaringan.', 'error');
    } finally {
      btn && (btn.disabled = false);
    }
  });
})();
