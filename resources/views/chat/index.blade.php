<!doctype html>
<html lang="id" data-theme="auto">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Customer Service AI — Cabang: {{ $branch->name }}</title>

  <!-- Tailwind (utility) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Open Props (design tokens opsional) -->
  <link rel="stylesheet" href="https://unpkg.com/open-props"/>
  <!-- highlight.js -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/styles/github.min.css">
  <script src="https://cdn.jsdelivr.net/npm/highlight.js@11.9.0/lib/common.min.js" defer></script>
  <!-- DOMPurify -->
  <script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.6/dist/purify.min.js" defer></script>
  <!-- Lucide Icons (opsional) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lucide-static@latest/font/lucide.css">
  <!-- Inter (fallback untuk "OpenAI Sans") -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    /* ==== TOKENS & THEME ==== */
    :root{
      --accent: #74AA9C;          /* ChatGPT green */
      --accent-alt: #10A37F;      /* OpenAI teal */
      --text: #111214;
      --muted: #6B7178;
      --bg: #ffffff;
      --surface: #F7F7F8;
      --border: #E5E7EB;
      --radius: 14px;
      --composer-h: 76px;         /* disesuaikan JS */
      color-scheme: light dark;
    }
    @media (prefers-color-scheme: dark) {
      :root[data-theme="auto"]{
        --text: #E6E8EB;
        --muted:#A3A9B1;
        --bg:#0B0F14;
        --surface:#14161A;
        --border:#2A2F37;
      }
    }
    :root[data-theme="dark"]{
      --text:#E6E8EB; --muted:#A3A9B1; --bg:#0B0F14; --surface:#14161A; --border:#2A2F37;
    }
    :root[data-theme="light"]{
      --text:#111214; --muted:#6B7178; --bg:#ffffff; --surface:#F7F7F8; --border:#E5E7EB;
    }

    /* ==== BASE ==== */
    *{ box-sizing: border-box }
    html,body{ height:100%; }
    body{
      font-family: "OpenAI Sans", Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans",
                   "Helvetica Neue", Arial, "Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol", sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.6;
      margin:0;
      -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;
    }
    :focus-visible{ outline:2px solid var(--accent); outline-offset:2px; border-radius:8px }

    /* ==== APP SHELL (full-bleed, no frame) ==== */
    .app{
      min-height: 100svh; /* viewport unit baru - mobile */
      min-height: 100vh;
      display: grid;
      grid-template-rows: auto 1fr;
      overflow: hidden;
      background: var(--bg);
    }

    /* Header (sticky, minimal) */
    .appbar{
      position: sticky; top: 0; z-index: 20;
      background: color-mix(in srgb, var(--bg) 92%, transparent);
      backdrop-filter: saturate(120%) blur(8px);
      border-bottom: 1px solid var(--border);
    }
    .brand-dot{
      width: 20px; height: 20px; border-radius: 8px;
      background: radial-gradient(75% 75% at 30% 30%, var(--accent), var(--accent-alt));
      box-shadow: inset 0 0 0 1.5px rgb(255 255 255 / .25);
    }
    .btn-ghost{
      border: 1px solid var(--border);
      background: color-mix(in srgb, var(--surface) 60%, var(--bg));
      border-radius: 12px; padding: 8px; display: grid; place-items:center;
      transition: transform .06s ease, background .15s ease;
    }
    .btn-ghost:hover{ background: color-mix(in srgb, var(--surface) 80%, var(--bg)); }
    .btn-ghost:active{ transform: translateY(1px); }

    /* Stream (centered max-width, no outer card) */
    .stream{
      width: 100%;
      max-width: 780px;
      margin: 0 auto;
      padding: 16px 16px calc(var(--composer-h) + env(safe-area-inset-bottom, 0px) + 24px);
      overflow: auto;
    }

    /* Bubbles */
    .msg{ display:grid; grid-template-columns: 36px 1fr; gap: 12px; align-items:start; }
    .avatar{
      width: 36px; height: 36px; border-radius: 999px;
      background: var(--surface); border: 1px solid var(--border);
      display:grid; place-items:center; font-size: 12px; color: var(--muted);
    }
    .bubble{
      border: 1px solid var(--border);
      background: var(--surface);
      border-radius: var(--radius);
      padding: 14px 14px 10px; position: relative;
      animation: msgIn .3s ease;
      word-wrap: break-word;
      overflow-wrap: anywhere;
    }
    .bubble--user{
      background: linear-gradient(180deg, color-mix(in srgb, var(--accent) 92%, #fff 8%), var(--accent-alt));
      color: #fff; border-color: transparent;
    }
    @keyframes msgIn{ from{opacity:0; transform: translateY(12px) scale(.98)} to{opacity:1; transform:none} }
    .meta{ font-size:12px; color: var(--muted); margin-top: 6px; display:flex; gap:10px; }

    /* Markdown styles */
    .bubble h1{ font-size:24px; margin:0 0 8px }
    .bubble h2{ font-size:20px; margin:10px 0 6px }
    .bubble h3{ font-size:18px; margin:10px 0 6px }
    .bubble p{ margin:0 0 10px }
    .bubble ul,.bubble ol{ margin:8px 0 10px 22px }
    .bubble blockquote{
      border-left:3px solid var(--accent);
      margin:6px 0 10px; padding:6px 10px;
      background: color-mix(in srgb, var(--surface) 70%, var(--bg));
      border-radius: 10px;
    }
    .bubble code:not(pre code){
      background: color-mix(in srgb, var(--surface) 70%, var(--bg));
      border:1px solid var(--border); border-radius: 8px; padding: 2px 6px;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
      font-size:.92em;
    }
    pre{
      background:#0d1117; color:#e6edf3; border-radius: 12px; overflow:auto; padding:12px;
      border:1px solid #1f2328; position:relative; margin:8px 0 10px;
    }
    .code-actions{ position:absolute; top:8px; right:8px; display:flex; gap:6px; }
    .code-btn{
      border:1px solid rgba(255,255,255,.2); background:rgba(255,255,255,.08);
      color:#fff; font-size:12px; border-radius:8px; padding:4px 8px; cursor:pointer;
    }

    /* Typing */
    .typing{ display:inline-flex; gap:6px; align-items:center; }
    .dot{ width:6px; height:6px; border-radius:999px; background: var(--accent); animation: bounce 1.2s infinite ease-in-out; }
    .dot:nth-child(2){ animation-delay:.15s } .dot:nth-child(3){ animation-delay:.3s }
    @keyframes bounce{ 0%,80%,100%{ transform: translateY(0); opacity:.6 } 40%{ transform: translateY(-4px); opacity:1 } }

    /* Composer (fixed) */
    .composer{
      position: fixed; left:0; right:0; bottom:0; z-index: 30;
      background: color-mix(in srgb, var(--bg) 92%, transparent);
      backdrop-filter: saturate(120%) blur(8px);
      border-top: 1px solid var(--border);
      padding: 10px 12px calc(env(safe-area-inset-bottom, 0px) + 10px);
      display: grid; place-items: center;
    }
    .composer__inner{ width:100%; max-width: 780px; display:grid; gap:8px; }
    .composer__row{ display:grid; grid-template-columns: auto 1fr auto; gap:8px; align-items:end; }
    .textarea{
      border:1px solid var(--border); background: var(--bg); color: var(--text);
      border-radius: var(--radius); padding: 12px 14px; min-height:44px; max-height: 220px;
      resize: none; overflow: auto; line-height:1.5; font-size:15px;
    }
    .btn-icon{
      width: 42px; height: 42px; display:grid; place-items:center;
      border-radius: 12px; border:1px solid var(--border);
      background: color-mix(in srgb, var(--surface) 70%, var(--bg));
      color: var(--text); transition: background .15s ease, transform .06s ease;
    }
    .btn-icon:hover{ background: color-mix(in srgb, var(--surface) 80%, var(--bg)) }
    .btn-icon:active{ transform: translateY(1px) }
    .btn-send{
      background: linear-gradient(180deg, color-mix(in srgb, var(--accent) 94%, #fff 6%), var(--accent-alt));
      color:#fff; border: none;
    }

    /* Chips (preview upload) */
    .chips{ display:flex; gap:8px; flex-wrap: wrap; align-items:center; padding:0 4px 6px; }
    .chip{
      display:inline-flex; align-items:center; gap:8px;
      background: var(--surface); border:1px solid var(--border); color: var(--text);
      border-radius: 999px; padding:6px 10px; font-size: 13px;
    }
    .chip img{ width:22px; height:22px; border-radius:4px; object-fit:cover; border:1px solid var(--border); }
    .chip .rm{ border:none; background:transparent; color: var(--muted); cursor:pointer; }

    /* Price highlight (dipakai di JS) */
    .price-highlight{
      background: linear-gradient(135deg, #10b981, #059669);
      color: #fff; padding: 2px 8px; border-radius: 8px; font-weight: 600;
      box-shadow: 0 1px 3px rgba(16,185,129,.3);
    }

    /* Scrollbar */
    .stream::-webkit-scrollbar{ width:8px; height:8px } .stream::-webkit-scrollbar-thumb{ background: var(--border); border-radius:6px }
    .stream::-webkit-scrollbar-thumb:hover{ background: var(--muted) }

    /* Mobile tweaks */
    @media (max-width: 420px){
      .stream{ padding-left: 12px; padding-right: 12px; }
    }
  </style>
</head>
<body>
  <div class="app">
    <!-- Header -->
    <header class="appbar">
      <div class="max-w-[780px] mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="brand-dot" aria-hidden="true"></span>
          <div class="leading-tight">
            <h1 class="text-base sm:text-lg font-semibold">Customer Service AI</h1>
            <p class="text-xs text-[color:var(--muted)]">Cabang: {{ $branch->name }}</p>
          </div>
        </div>
        <button id="btn-theme" class="btn-ghost" aria-label="Ganti tema" title="Ganti tema">
          <!-- sun/moon swap via JS -->
          <svg id="icon-sun" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M12 3v2m0 14v2M3 12h2m14 0h2M5.6 5.6l1.4 1.4m10 10 1.4 1.4M18.4 5.6l-1.4 1.4m-10 10-1.4 1.4" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="12" r="4"></circle>
          </svg>
          <svg id="icon-moon" class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </div>
    </header>

    <!-- Stream -->
    <main id="stream" class="stream" role="log" aria-live="polite" aria-relevant="additions text" aria-atomic="false">
      <!-- Welcome (opsional, boleh dihapus kalau tidak ingin pesan awal) -->
      <div class="msg">
        <div class="avatar" aria-hidden="true">AI</div>
        <div class="flex-1">
          <div class="bubble">
            <p><strong>👋 Selamat datang!</strong> Tanyakan apa saja tentang produk & layanan kami. Kirim pesan atau unggah gambar agar kami bisa bantu lebih cepat.</p>
          </div>
          <div class="meta"><span>baru</span></div>
        </div>
      </div>
    </main>

    <!-- Composer -->
    <footer class="composer" aria-label="Bidang masukan">
      <div class="composer__inner">
        <div id="chips" class="chips" aria-label="Lampiran"></div>
        <div class="composer__row">
          <button id="btn-upload" class="btn-icon" aria-label="Unggah lampiran" title="Unggah">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M4 16l6-6 4 4 6-6" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M14 7h6v6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          <input id="file" type="file" accept="image/*" class="sr-only" aria-hidden="true"/>

          <label class="sr-only" for="message">Ketik pesan</label>
          <textarea id="message" class="textarea" rows="1" placeholder="Tulis pesan… (Enter = kirim, Shift+Enter = baris baru)"></textarea>

          <button id="btn-send" class="btn-icon btn-send" aria-label="Kirim pesan" title="Kirim">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M3 5l18 7-18 7 2-7 -2-7zm2 7h7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </div>
    </footer>
  </div>

  <!-- Pass data PHP ke JavaScript -->
  <script>
    // Konstanta yang dibutuhkan oleh chatai.js
    const BRANCH_ID = {{ $branch->id }};
  </script>
  <!-- JS utama Anda -->
  <script src="{{ asset('js/chatai.js') }}"></script>
</body>
</html>
