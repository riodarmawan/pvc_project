(() => {
  const qs = (sel, el = document) => el.querySelector(sel);

  const CSRF = () => {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  };

  const toast = (msg, type = 'ok') => {
    const el = document.createElement('div');
    el.className = `fixed top-4 left-1/2 -translate-x-1/2 z-[60] px-4 py-2 rounded-xl text-white shadow
      ${type === 'error' ? 'bg-rose-600' : 'bg-emerald-600'}`;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transition = 'opacity .3s';
      setTimeout(() => el.remove(), 300)
    }, 1700);
  };

  const isJson = (res) => (res.headers.get('content-type') || '').includes('application/json');

  async function postForm(url, fd) {
    if (!fd.has('_token')) fd.append('_token', CSRF());
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: fd
    });
    if (isJson(res)) {
      const data = await res.json().catch(() => ({}));
      return { ok: res.ok && (data.ok !== false), data };
    } else {
      window.location.reload();
      return { ok: true, data: {} };
    }
  }

  // Token Verification System
  let currentToken = '';
  let currentForm = null;
  let currentFormData = null;

  // Generate secure 5-digit token using Web Crypto API
  function generateSecureToken() {
    if (window.crypto && window.crypto.getRandomValues) {
      // Use secure random for production
      const array = new Uint32Array(1);
      window.crypto.getRandomValues(array);
      return String(array[0]).slice(-5).padStart(5, '0');
    } else {
      // Fallback for older browsers
      return Math.floor(Math.random() * 100000).toString().padStart(5, '0');
    }
  }

  // Show token modal
  function showTokenModal(form, formData) {
    currentForm = form;
    currentFormData = formData;
    currentToken = generateSecureToken();
    
    const modal = qs('#token-modal');
    const tokenDisplay = qs('#verification-token');
    const tokenInput = qs('#token-input');
    const errorDiv = qs('#token-error');
    
    // Reset modal state
    tokenDisplay.textContent = currentToken;
    tokenInput.value = '';
    tokenInput.classList.remove('error');
    errorDiv.style.display = 'none';
    
    modal.classList.add('active');
    
    // Focus on input with slight delay for better UX
    setTimeout(() => tokenInput.focus(), 300);
    
    // Auto-format input (numbers only)
    tokenInput.oninput = (e) => {
      e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 5);
    };
    
    // Enter key to verify
    tokenInput.onkeypress = (e) => {
      if (e.key === 'Enter' && tokenInput.value.length === 5) {
        verifyToken();
      }
    };
  }

  // Close token modal
  window.closeTokenModal = function() {
    const modal = qs('#token-modal');
    modal.classList.remove('active');
    currentForm = null;
    currentFormData = null;
    currentToken = '';
  };

  // Regenerate token
  window.regenerateToken = function() {
    currentToken = generateSecureToken();
    const tokenDisplay = qs('#verification-token');
    const tokenInput = qs('#token-input');
    const errorDiv = qs('#token-error');
    
    tokenDisplay.textContent = currentToken;
    tokenInput.value = '';
    tokenInput.classList.remove('error');
    errorDiv.style.display = 'none';
    tokenInput.focus();
    
    // Add regeneration animation
    tokenDisplay.style.animation = 'none';
    setTimeout(() => {
      tokenDisplay.style.animation = 'modalAppear 0.3s ease-out';
    }, 10);
  };

  // Verify token
  window.verifyToken = async function() {
    const tokenInput = qs('#token-input');
    const errorDiv = qs('#token-error');
    const modalContent = qs('.token-modal-content');
    const verifyBtn = qs('.token-btn-verify');
    
    const enteredToken = tokenInput.value.trim();
    
    if (enteredToken.length !== 5) {
      showTokenError('Masukkan 5 digit angka!');
      return;
    }
    
    if (enteredToken !== currentToken) {
      showTokenError('Kode verifikasi tidak sesuai!');
      modalContent.classList.add('shake');
      setTimeout(() => modalContent.classList.remove('shake'), 500);
      return;
    }
    
    // Token verified, proceed with form submission
    verifyBtn.disabled = true;
    verifyBtn.textContent = 'Memproses...';
    
    try {
      const { ok, data } = await postForm(currentForm.action, currentFormData);
      
      if (!ok) {
        toast(data.message || 'Gagal menyimpan.', 'error');
        return;
      }
      
      if (data.message) toast(data.message);
      
      if (data.html) {
        data.html.summary && qs('#summary-panel') && (qs('#summary-panel').innerHTML = data.html.summary);
        data.html.table && qs('#table-panel') && (qs('#table-panel').innerHTML = data.html.table);
      }
      
      // Reset form fields
      const amountInput = currentForm.querySelector('input[name="amount"]');
      const memoInput = currentForm.querySelector('input[name="memo"]');
      if (amountInput) amountInput.value = '';
      if (memoInput) memoInput.value = '';
      
      // Close modal
      closeTokenModal();
      
    } catch (err) {
      console.error(err);
      toast('Terjadi kesalahan jaringan.', 'error');
    } finally {
      verifyBtn.disabled = false;
      verifyBtn.textContent = 'Verifikasi';
    }
  };

  // Show token error
  function showTokenError(message) {
    const tokenInput = qs('#token-input');
    const errorDiv = qs('#token-error');
    
    tokenInput.classList.add('error');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    tokenInput.focus();
    tokenInput.select();
  }

  // Close modal when clicking backdrop
  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('token-modal-backdrop')) {
      closeTokenModal();
    }
  });

  // Enhanced form submission handler
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form.js-ajax');
    if (!form) return;

    e.preventDefault();
    
    // Check if this form requires security verification
    const isSecureForm = form.classList.contains('js-secure-form');
    
    const btn = form.querySelector('button[type="submit"],button:not([type])');
    if (btn) btn.disabled = true;

    try {
      const fd = new FormData(form);
      
      if (isSecureForm) {
        // Show token verification modal for secure forms
        showTokenModal(form, fd);
        return;
      }
      
      // Regular form submission for non-secure forms
      const { ok, data } = await postForm(form.action, fd);
      
      if (!ok) {
        toast(data.message || 'Gagal menyimpan.', 'error');
        return;
      }
      
      if (data.message) toast(data.message);
      
      if (data.html) {
        data.html.summary && qs('#summary-panel') && (qs('#summary-panel').innerHTML = data.html.summary);
        data.html.table && qs('#table-panel') && (qs('#table-panel').innerHTML = data.html.table);
      }
      
      // Reset form fields
      form.querySelector('input[name="amount"]') && (form.querySelector('input[name="amount"]').value = '');
      form.querySelector('input[name="memo"]') && (form.querySelector('input[name="memo"]').value = '');
      
    } catch (err) {
      console.error(err);
      toast('Terjadi kesalahan jaringan.', 'error');
    } finally {
      if (btn) btn.disabled = false;
    }
  });

  // Auto-close modal on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && qs('#token-modal').classList.contains('active')) {
      closeTokenModal();
    }
  });
})();
