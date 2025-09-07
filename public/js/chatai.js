document.addEventListener('DOMContentLoaded', () => {
  /* ======================== THEME ======================== */
  const btnTheme = document.getElementById('btn-theme');
  const sunIcon = document.getElementById('icon-sun');
  const moonIcon = document.getElementById('icon-moon');
  const htmlElement = document.documentElement;

  const savedTheme = localStorage.getItem('theme') || 'auto'; // auto | light | dark
  setTheme(savedTheme);

  function setTheme(theme) {
    htmlElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    // tampilkan ikon sesuai state
    const isLight = (theme === 'light');
    sunIcon.classList.toggle('hidden', !isLight);
    moonIcon.classList.toggle('hidden', isLight);
  }

  btnTheme.addEventListener('click', () => {
    const cur = htmlElement.getAttribute('data-theme') || 'auto';
    const next = cur === 'auto' ? 'light' : (cur === 'light' ? 'dark' : 'auto');
    setTheme(next);
  });

  /* ======================== DOM ========================== */
  const messageInput = document.getElementById('message');       // textarea baru
  const chatContainer = document.getElementById('stream');        // stream baru
  const btnSend = document.getElementById('btn-send');            // tombol kirim baru
  const btnUpload = document.getElementById('btn-upload');        // tombol upload
  const fileInput = document.getElementById('file');              // input file
  const chips = document.getElementById('chips');                 // area preview chip
  const composer = document.querySelector('.composer');           // untuk DnD & tinggi

  /* ====================== KONSTANTA ======================= */
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const CHAT_URL = `http://43.173.29.242:9000/chat`;
  const CATALOG_URL = `http://43.173.29.242:9000/catalog`;
  const INTERNAL_TOKEN = 'super-secret-token';
  const MAX_BOOTSTRAP_ITEMS = 300;

  /* ======================== STATE ======================== */
  let conversationHistory = [];   // chaining percakapan
  let imageBase64 = null;         // base64 1 gambar aktif (sesuai versi lama)
  let isLoading = false;
  let catalogData = null;

  /* =================== UI HELPERS (BARU) ================= */
  const timeStr = (d = new Date()) => d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

  const addMessageToUI = (sender, messageHTML) => {
    // node "msg" ala layout baru
    const wrap = document.createElement('div');
    wrap.className = 'msg message-animation';

    const av = document.createElement('div');
    av.className = 'avatar';
    av.textContent = sender === 'user' ? 'U' : 'AI';
    av.setAttribute('aria-hidden', 'true');

    const bubble = document.createElement('div');
    bubble.className = 'bubble' + (sender === 'user' ? ' bubble--user' : '');
    bubble.innerHTML = messageHTML;

    const meta = document.createElement('div');
    meta.className = 'meta';
    meta.innerHTML = `<span>${timeStr()}</span>${
      sender === 'ai' ? `<span aria-hidden="true">•</span><button class="btn-ghost" data-act="copy-msg" aria-label="Salin balasan">Copy</button>` : ''
    }`;

    const col = document.createElement('div');
    col.appendChild(bubble);
    col.appendChild(meta);

    wrap.appendChild(av);
    wrap.appendChild(col);
    chatContainer.appendChild(wrap);

    // auto-scroll cerdas
    const nearBottom = (chatContainer.scrollHeight - (chatContainer.scrollTop + chatContainer.clientHeight)) < 80;
    if (nearBottom) chatContainer.scrollTo({ top: chatContainer.scrollHeight, behavior: 'smooth' });

    // highlight code bila ada
    if (window.hljs) {
      wrap.querySelectorAll('pre code').forEach(c => { try { hljs.highlightElement(c); } catch {} });
    }
  };

  const addTyping = () => {
    const wrap = document.createElement('div');
    wrap.className = 'msg';
    wrap.id = 'loading-indicator'; // pertahankan id lama untuk kompatibilitas
    wrap.setAttribute('aria-busy', 'true');

    const av = document.createElement('div');
    av.className = 'avatar';
    av.textContent = 'AI';
    av.setAttribute('aria-hidden', 'true');

    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    bubble.innerHTML = `
      <span class="typing" aria-label="Sedang mengetik">
        <span class="dot"></span><span class="dot"></span><span class="dot"></span>
      </span>`;

    const meta = document.createElement('div');
    meta.className = 'meta';
    meta.textContent = 'menyusun jawaban…';

    const col = document.createElement('div');
    col.appendChild(bubble);
    col.appendChild(meta);

    wrap.appendChild(av);
    wrap.appendChild(col);
    chatContainer.appendChild(wrap);
    chatContainer.scrollTo({ top: chatContainer.scrollHeight, behavior: 'smooth' });
  };

  const removeTyping = () => {
    const t = document.getElementById('loading-indicator');
    if (t) t.remove();
  };

  const setLoadingState = (loading) => {
    isLoading = loading;
    messageInput.disabled = loading;
    btnSend.disabled = loading;

    if (loading) {
      addTyping();
      btnSend.style.transform = 'scale(0.95)';
    } else {
      removeTyping();
      btnSend.style.transform = 'scale(1)';
    }
  };

  /* ================== CATALOG MANAGEMENT ================= */
  const loadCatalogOnce = async () => {
    if (catalogData) return catalogData;
    try {
      const res = await fetch(CATALOG_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Internal-Token': INTERNAL_TOKEN,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ branch_id: BRANCH_ID })
      });
      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(`Catalog error (${res.status}): ${err.error || 'Unknown error'}`);
      }
      const data = await res.json();
      catalogData = Array.isArray(data) ? data.slice(0, MAX_BOOTSTRAP_ITEMS) : [];
      return catalogData;
    } catch (e) {
      console.error('❌ Error loading catalog:', e);
      catalogData = [];
      return catalogData;
    }
  };

  /* =========== BUILD CONTEXT (katalog + riwayat) ========= */
  const buildFullConversationContext = (newUserInput) => {
    let full = '';
    full += '=== KATALOG PRODUK TOKO PVC ===\n';
    if (catalogData?.length) {
      full += 'Data produk tersedia (format: name, category_name, stock, price):\n';
      full += JSON.stringify(catalogData, null, 2) + '\n';
    } else {
      full += 'KATALOG TIDAK TERSEDIA\n';
    }
    full += '\n=== INSTRUKSI SISTEM ===\n';
    full += 'Anda adalah AI assistant toko PVC yang cerdas dan ramah.\n';
    full += 'ATURAN:\n';
    full += '- Gunakan HANYA data dari katalog di atas\n';
    full += '- Dapat memahami dan menganalisis gambar yang diupload user\n';
    full += '- Berikan rekomendasi produk berdasarkan gambar atau pertanyaan\n';
    full += '- Format harga dalam Rupiah (Rp) dengan pemisah ribuan\n';
    full += '- Jawab dengan ramah, informatif, dan gunakan emoji yang sesuai\n';
    full += '- Ingat konteks percakapan sebelumnya\n';
    full += '- Gunakan markdown untuk formatting (bold, italic, list)\n\n';

    if (conversationHistory.length) {
      full += '=== RIWAYAT PERCAKAPAN ===\n';
      conversationHistory.forEach((entry, i) => {
        full += `[${i + 1}] USER: ${entry.userMessage}\n`;
        if (entry.hasImage) full += `    [User mengirim gambar]\n`;
        full += `[${i + 1}] ASSISTANT: ${entry.aiResponse}\n\n`;
      });
    }

    full += '=== PESAN TERBARU ===\n';
    full += `USER: ${newUserInput}\n`;
    return full;
  };

  /* ======================= UTILITIES ===================== */
  const escapeHtml = (text) => {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  };

  const sanitize = (html) =>
    window.DOMPurify ? DOMPurify.sanitize(html) : html;

  const formatAIResponse = (text) => {
    const html = text
      .replace(/\n/g, '<br>')
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/\*(.*?)\*/g, '<em>$1</em>')
      .replace(/`(.*?)`/g, '<code>$1</code>')
      .replace(/Rp\s*(\d+(?:[.,]\d+)*)/g, '<span class="price-highlight">Rp $1</span>')
      .replace(/(\d+)\s*(stock|stok|tersedia)/gi, '<span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-xs font-medium">$1 $2</span>');
    return sanitize(html);
  };

  const showNotification = (message, type = 'info') => {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-xl shadow-lg max-w-sm transform transition-all duration-300 ease-out translate-x-full opacity-0`;
    const colors = {
      info: 'bg-blue-500 text-white',
      error: 'bg-red-500 text-white',
      success: 'bg-green-500 text-white'
    };
    notification.classList.add(...colors[type].split(' '));
    notification.innerHTML = `
      <div class="flex items-center gap-3">
        <span class="font-medium">${message}</span>
        <button class="ml-auto opacity-70 hover:opacity-100" aria-label="Tutup">×</button>
      </div>
    `;
    notification.addEventListener('click', (e) => {
      if (e.target.closest('button')) notification.remove();
    });
    document.body.appendChild(notification);
    setTimeout(() => notification.classList.remove('translate-x-full', 'opacity-0'), 100);
    setTimeout(() => {
      notification.classList.add('translate-x-full', 'opacity-0');
      setTimeout(() => notification.remove(), 300);
    }, 4000);
  };

  /* ==================== IMAGE UPLOAD ===================== */
  // versi baru: preview via "chips"; simpan satu gambar terakhir ke imageBase64 (kompatibel dengan backend Anda)
  btnUpload.addEventListener('click', () => fileInput.click());

  fileInput.addEventListener('change', async () => {
    const file = fileInput.files?.[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
      showNotification('File terlalu besar. Maksimal 5MB.', 'error');
      return;
    }
    if (!file.type.startsWith('image/')) {
      showNotification('File harus berupa gambar.', 'error');
      return;
    }
    const base64 = await fileToDataURL(file);
    imageBase64 = base64; // kirim satu gambar (kompatibel)
    addChipPreview(file.name, base64);
    fileInput.value = '';
  });

  function addChipPreview(name, dataUrl) {
    chips.innerHTML = ''; // pastikan satu gambar aktif (sesuai versi lama)
    const chip = document.createElement('span');
    chip.className = 'chip';
    chip.dataset.name = name;
    chip.innerHTML = `
      <img src="${dataUrl}" alt="preview" />
      <span>${escapeHtml(name)}</span>
      <button type="button" class="rm" aria-label="Hapus lampiran" title="Hapus">&times;</button>
    `;
    chips.appendChild(chip);
  }

  chips.addEventListener('click', (e) => {
    const rm = e.target.closest('.rm');
    if (rm) {
      chips.innerHTML = '';
      imageBase64 = null;
    }
  });

  // drag & drop ke composer
  composer.addEventListener('dragover', (e) => { e.preventDefault(); composer.style.borderTopColor = 'var(--accent)'; });
  composer.addEventListener('dragleave', () => { composer.style.borderTopColor = 'var(--border)'; });
  composer.addEventListener('drop', async (e) => {
    e.preventDefault(); composer.style.borderTopColor = 'var(--border)';
    const f = e.dataTransfer.files?.[0];
    if (!f) return;
    if (!f.type.startsWith('image/') || f.size > 5 * 1024 * 1024) return showNotification('Hanya gambar ≤ 5MB.', 'error');
    const base64 = await fileToDataURL(f);
    imageBase64 = base64;
    addChipPreview(f.name, base64);
  });

  function fileToDataURL(f) {
    return new Promise((resolve, reject) => {
      const r = new FileReader();
      r.onload = () => resolve(r.result);
      r.onerror = reject;
      r.readAsDataURL(f);
    });
  }

  /* ===================== KIRIM PESAN ===================== */
 /* ===================== KIRIM PESAN (VERSI PERBAIKAN) ===================== */
 const handleSend = async () => {
    const userText = messageInput.value.trim();
    if (!userText && !imageBase64) return;
    if (isLoading) return;

    setLoadingState(true);

    // 1. Simpan state input saat ini sebelum dibersihkan
    const sentUserText = userText;
    const sentImageBase64 = imageBase64;

    // 2. Tampilkan pesan pengguna ke UI menggunakan data yang disimpan
    let userMessageHTML = `<p>${escapeHtml(sentUserText).replace(/\n/g,'<br>')}</p>`;
    if (sentImageBase64) {
      userMessageHTML += `<img src="${sentImageBase64}" class="mt-3 rounded-xl max-w-xs shadow-lg" alt="Upload">`;
    }
    addMessageToUI('user', userMessageHTML);

    // 3. SEGERA bersihkan input setelah pesan ditampilkan di UI
    messageInput.value = '';
    autoResize();
    chips.innerHTML = '';
    imageBase64 = null;

    try {
      if (!catalogData) await loadCatalogOnce();

      const fullConversationContext = buildFullConversationContext(sentUserText);
      const requestPayload = {
        message: fullConversationContext,
        branch_id: BRANCH_ID
      };
      // 4. Gunakan data gambar yang disimpan untuk request API
      if (sentImageBase64) requestPayload.image = sentImageBase64;

      const response = await fetch(CHAT_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Internal-Token': INTERNAL_TOKEN,
          'Accept': 'application/json',
          ...(CSRF ? { 'X-CSRF-TOKEN': CSRF } : {})
        },
        body: JSON.stringify(requestPayload)
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || `HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();
      const aiReply = data.reply || '';

      // Simpan ke history menggunakan data yang sudah disimpan
      conversationHistory.push({
        userMessage: sentUserText,
        aiResponse: aiReply,
        hasImage: !!sentImageBase64,
        timestamp: new Date().toISOString()
      });

      // Render jawaban AI
      addMessageToUI('ai', formatAIResponse(aiReply));

    } catch (error) {
      console.error('❌ Chat error:', error);
      addMessageToUI('ai', `<div class="p-3 bg-red-50 border border-red-200 rounded-lg"><p class="text-red-700 mb-0"><strong>⚠️ Error:</strong> ${escapeHtml(error.message)}</p></div>`);
    } finally {
      setLoadingState(false);
      // Reset input sudah dipindah ke atas, cukup fokuskan kembali
      messageInput.focus();
    }
 };


  // tombol kirim & keyboard
  btnSend.addEventListener('click', handleSend);
  messageInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); handleSend(); }
  });

  /* ===================== AUTO-RESIZE ===================== */
  function autoResize() {
    messageInput.style.height = 'auto';
    const h = Math.max(44, Math.min(messageInput.scrollHeight, 220));
    messageInput.style.height = h + 'px';
    // update padding bawah stream sesuai tinggi composer
    const footer = document.querySelector('.composer');
    const inner = document.querySelector('.composer__inner');
    const composerH = Math.max(footer?.offsetHeight || 76, inner?.offsetHeight || 76);
    document.documentElement.style.setProperty('--composer-h', composerH + 'px');
  }
  messageInput.addEventListener('input', autoResize);
  window.addEventListener('resize', () => {
    autoResize();
    chatContainer.scrollTo({ top: chatContainer.scrollHeight });
  });
  autoResize();

  /* =================== STREAM ACTIONS ==================== */
  // Copy code / bubble assistant
  chatContainer.addEventListener('click', async (e) => {
    const copyBtn = e.target.closest('[data-act="copy"], [data-act="copy-msg"]');
    if (!copyBtn) return;

    let text = '';
    const pre = copyBtn.closest('pre');
    if (pre) text = pre.innerText;
    else {
      const bubble = copyBtn.closest('.msg')?.querySelector('.bubble');
      text = bubble ? bubble.innerText : '';
    }

    try { await navigator.clipboard.writeText(text.trim()); showNotification('Tersalin ✓','success'); }
    catch { showNotification('Gagal menyalin','error'); }
  });

  /* ===================== INIT ============================ */
  loadCatalogOnce().then(() => {
    console.log('🚀 Chat system ready');
    showNotification('Chat system siap digunakan! 🚀', 'success');
  });

  // Debug helpers (opsional)
  window.chatDebug = {
    getHistory: () => conversationHistory,
    getCatalog: () => catalogData,
    clearHistory: () => { conversationHistory = []; console.log('🗑️ History cleared'); },
    reloadCatalog: async () => { catalogData = null; return await loadCatalogOnce(); },
    toggleTheme: () => btnTheme.click(),
    stats: () => ({
      historyPairs: conversationHistory.length,
      catalogItems: catalogData?.length || 0,
      hasCurrentImage: !!imageBase64,
      currentTheme: htmlElement.getAttribute('data-theme')
    })
  };
});
