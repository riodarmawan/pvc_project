<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Chat - Cabang: {{ $branch->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* === ROOT VARIABLES & THEME SYSTEM === */
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #e0e7ff;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-chat: #f3f4f6;
            --border-color: #e5e7eb;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --gradient-start: #667eea;
            --gradient-end: #764ba2;
        }

        [data-theme="dark"] {
            --primary: #6366f1;
            --primary-hover: #5b21b6;
            --primary-light: #312e81;
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --bg-primary: #111827;
            --bg-secondary: #1f2937;
            --bg-chat: #374151;
            --border-color: #4b5563;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.3), 0 1px 2px -1px rgb(0 0 0 / 0.3);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.2), 0 4px 6px -4px rgb(0 0 0 / 0.2);
            --gradient-start: #4c1d95;
            --gradient-end: #7c3aed;
        }

        /* === BASE STYLES === */
        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
            font-feature-settings: "cv02", "cv03", "cv04", "cv11";
        }

        /* === LAYOUT COMPONENTS === */
        .chat-container {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
        }

        .header-gradient {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            position: relative;
            overflow: hidden;
        }

        .header-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* === THEME TOGGLE === */
        .theme-toggle {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        /* === CHAT MESSAGES === */
        .message-animation {
            animation: messageSlideIn 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .user-message {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: white;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .ai-message {
            background: var(--bg-chat);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            box-shadow: var(--shadow);
        }

        .avatar {
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .avatar:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-lg);
        }

        /* === ENHANCED LOADING ANIMATION === */
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 16px 0;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary);
            animation: typingBounce 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        .typing-dot:nth-child(3) { animation-delay: 0s; }

        @keyframes typingBounce {
            0%, 80%, 100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            40% {
                transform: scale(1.2);
                opacity: 1;
            }
        }

        /* === INPUT FORM === */
        .input-container {
            background: var(--bg-primary);
            border-top: 1px solid var(--border-color);
            backdrop-filter: blur(10px);
        }

        .input-field {
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            color: var(--text-primary);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-field:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: var(--bg-primary);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            color: white;
            box-shadow: var(--shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--bg-chat);
            color: var(--text-primary);
            transform: scale(1.05);
        }

        /* === IMAGE PREVIEW === */
        .image-preview-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .remove-image-btn {
            background: #ef4444;
            color: white;
            transition: all 0.3s ease;
        }

        .remove-image-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        /* === TYPOGRAPHY === */
        .prose {
            line-height: 1.7;
        }

        .prose p {
            margin-bottom: 1em;
            color: var(--text-primary);
        }

        .prose strong {
            color: var(--text-primary);
            font-weight: 600;
        }

        .prose em {
            color: var(--text-secondary);
            font-style: italic;
        }

        .prose ul, .prose ol {
            margin: 1em 0;
            padding-left: 1.5em;
        }

        .prose li {
            margin-bottom: 0.5em;
            color: var(--text-primary);
        }

        .prose code {
            background: var(--bg-secondary);
            color: var(--primary);
            padding: 2px 6px;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            font-size: 0.9em;
        }

        .price-highlight {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(16, 185, 129, 0.3);
        }

        /* === SCROLLBAR === */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }

        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            .chat-container {
                border-radius: 0;
                height: 100vh;
            }
            
            .header-gradient {
                padding: 1rem;
            }
            
            .message-content {
                max-width: 85vw;
            }
        }

        /* === UTILITY ANIMATIONS === */
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .slide-up {
            animation: slideUp 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="chat-container w-full max-w-4xl mx-auto flex flex-col h-[90vh] rounded-2xl overflow-hidden fade-in">
        <!-- Enhanced Header -->
        <header class="header-gradient text-white p-6 relative">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight">AI Assistant</h1>
                        <p class="text-sm opacity-90">Cabang: {{ $branch->name }}</p>
                    </div>
                </div>
                
                <!-- Theme Toggle -->
                <button id="theme-toggle" class="theme-toggle p-3 rounded-xl">
                    <svg id="sun-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <svg id="moon-icon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </button>
            </div>
            <div class="mt-2">
                <p class="text-xs opacity-75 font-medium">Powered by Google Gemini AI</p>
            </div>
        </header>

        <!-- Enhanced Chat Area -->
        <main id="chat-container" class="flex-1 p-6 overflow-y-auto space-y-6 custom-scrollbar">
            <!-- Welcome Message -->
            <div class="flex items-start gap-4 message-animation">
                <div class="avatar flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-sm font-semibold">
                    AI
                </div>
                <div class="ai-message flex-1 rounded-2xl p-4 max-w-2xl">
                    <div class="prose prose-sm max-w-none">
                        <p class="mb-0">
                            👋 <strong>Selamat datang!</strong> Saya siap membantu Anda mencari informasi produk di cabang <span class="price-highlight">{{ $branch->name }}</span>. 
                            Tanyakan tentang stok, harga, atau upload gambar produk yang Anda cari!
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Enhanced Input Form -->
        <footer class="input-container p-6">
            <!-- Image Preview -->
            <div id="image-preview-container" class="image-preview-container hidden mb-4 ml-14 relative max-w-xs slide-up">
                <img id="image-preview" src="" alt="Preview" class="w-full h-auto rounded-xl"/>
                <button id="remove-image-btn" class="remove-image-btn absolute -top-2 -right-2 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shadow-lg">
                    ×
                </button>
            </div>

            <form id="chat-form" class="flex items-end gap-4">
                <!-- Upload Button -->
                <label for="image-upload" class="btn-secondary p-3 rounded-xl cursor-pointer flex-shrink-0" title="Upload gambar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <input type="file" id="image-upload" class="hidden" accept="image/*">
                </label>
                
                <!-- Text Input -->
                <div class="flex-1">
                    <input type="text" id="message-input" 
                           class="input-field w-full rounded-xl py-3 px-4 text-sm placeholder-gray-400 focus:outline-none resize-none" 
                           placeholder="Tanya tentang produk, stok, harga, atau upload gambar..." 
                           autocomplete="off">
                </div>
                
                <!-- Send Button -->
                <button type="submit" class="btn-primary p-3 rounded-xl flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </form>
        </footer>
    </div>

    <!-- Pass data PHP ke JavaScript -->
    <script>
        const BRANCH_ID = {{ $branch->id }};
    </script>
    <script src="{{ asset('js/chatai.js') }}"></script>
</body>
</html>
