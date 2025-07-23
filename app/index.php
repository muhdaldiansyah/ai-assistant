<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant</title>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            /* Primary colors */
            --primary: #10A37F;
            --primary-hover: #0E8E6F;
            --primary-light: rgba(16, 163, 127, 0.1);
            
            /* Neutral palette - ChatGPT style */
            --background: #FFFFFF;
            --sidebar-bg: #F7F7F8;
            --surface: #F7F7F8;
            --surface-secondary: #ECECEC;
            --border: #D9D9E3;
            --border-light: #E5E5E5;
            
            /* Text colors */
            --text-primary: #202123;
            --text-secondary: #565869;
            --text-tertiary: #8E8EA0;
            --text-quaternary: #ACACBE;
            
            /* Chat specific */
            --user-bubble-bg: #F7F7F8;
            --hover-bg: #ECECEC;
            
            /* System colors */
            --separator: rgba(0, 0, 0, 0.1);
            --overlay: rgba(0, 0, 0, 0.05);
            
            /* Shadows - softer and more subtle */
            --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1);
            
            /* Layout */
            --max-width: 48rem;
            
            /* Transitions */
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 200ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Display", "Helvetica Neue", Arial, sans-serif;
            line-height: 1.5;
            color: var(--text-primary);
            background-color: var(--background);
            height: 100vh;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }
        
        
        .btn-secondary {
            background: var(--surface);
            border: 1px solid var(--border-light);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all var(--transition-fast);
            box-shadow: var(--shadow-xs);
        }
        
        .btn-secondary:hover {
            background-color: var(--surface-secondary);
            border-color: var(--border);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-secondary:active {
            transform: translateY(0);
            box-shadow: none;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Main Layout */
        .app-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--background);
        }
        
        /* Sidebar */
        .main-wrapper {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            transition: margin-left var(--transition-slow);
        }
        
        .sidebar.hidden {
            margin-left: -260px;
        }
        
        .sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid var(--separator);
        }
        
        .new-chat-btn {
            width: 100%;
            padding: 0.75rem 1rem;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 0.375rem;
            font-size: 0.875rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 0.75rem;
            transition: all var(--transition-base);
            font-weight: 400;
            color: var(--text-primary);
        }
        
        .new-chat-btn:hover {
            background-color: var(--hover-bg);
        }
        
        .threads-list {
            flex: 1;
            overflow-y: auto;
            padding: 0.5rem;
        }
        

        
        .thread-item {
            padding: 0.75rem 1rem;
            margin: 0 0.25rem 0.125rem;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all var(--transition-fast);
            font-size: 0.875rem;
            color: var(--text-primary);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .thread-item:hover {
            background-color: var(--hover-bg);
        }
        
        .thread-item.active {
            background-color: var(--surface-secondary);
        }
        
        .thread-content {
            flex: 1;
            overflow: hidden;
        }
        
        .thread-actions {
            display: none;
            position: relative;
        }
        
        .thread-item:hover .thread-actions {
            display: block;
        }
        
        .thread-menu-btn {
            background: transparent;
            border: none;
            padding: 0.25rem;
            border-radius: 0.25rem;
            cursor: pointer;
            color: var(--text-tertiary);
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .thread-menu-btn:hover {
            background-color: var(--surface-secondary);
            color: var(--text-primary);
        }
        
        .thread-menu {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.25rem;
            background: var(--background);
            border: 1px solid var(--border);
            border-radius: 0.375rem;
            box-shadow: var(--shadow-lg);
            min-width: 160px;
            z-index: 1000;
            display: none;
        }
        
        .thread-menu.show {
            display: block;
        }
        
        .thread-menu-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: background-color var(--transition-fast);
            font-size: 0.875rem;
            color: var(--text-primary);
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .thread-menu-item i {
            width: 16px;
            text-align: center;
        }
        
        .thread-menu-item:hover {
            background-color: var(--hover-bg);
        }
        
        .thread-menu-item.delete {
            color: #EF4444;
        }
        
        .thread-title {
            font-weight: 500;
            margin-bottom: 0.125rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .thread-date {
            font-size: 0.75rem;
            color: var(--text-tertiary);
        }
        
        .toggle-sidebar-btn {
            display: none;
            background: transparent;
            border: 1px solid transparent;
            padding: 0.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all var(--transition-fast);
            color: var(--text-primary);
            font-size: 1.25rem;
        }
        
        .toggle-sidebar-btn:hover {
            background-color: var(--surface);
            border-color: var(--border-light);
        }
        
        .toggle-sidebar-btn:active {
            background-color: var(--surface-secondary);
        }
        
        .content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* Header */
        .app-header {
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-light);
            position: relative;
            z-index: 100;
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.03);
        }
        
        .header-content {
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .app-branding {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        
        .app-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            opacity: 0.9;
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            overflow-y: auto;
            position: relative;
            scroll-behavior: smooth;
        }
        
        /* Welcome Screen */
        .welcome-container {
            min-height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            animation: fadeIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            width: 100%;
        }
        
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(20px) scale(0.98);
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1);
            }
        }
        
        .welcome-content {
            max-width: var(--max-width);
            width: 100%;
            text-align: center;
        }
        
        
        .welcome-title {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .welcome-subtitle {
            font-size: 1.125rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            font-weight: 400;
        }
        
        /* Chat Messages Container */
        .chat-container {
            display: none;
            min-height: 100%;
            padding-bottom: 10rem;
        }
        
        .messages-list {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 2rem 0;
        }
        
        /* Message Styles */
        .message-wrapper {
            animation: messageSlide 0.3s ease;
        }
        
        @keyframes messageSlide {
            from { 
                opacity: 0; 
                transform: translateY(10px);
            }
            to { 
                opacity: 1; 
                transform: translateY(0);
            }
        }
        
        .message-wrapper.user-message {
            background-color: transparent;
            display: flex;
            justify-content: flex-end;
            padding: 0.75rem 0;
        }
        
        .message-wrapper.assistant-message {
            background-color: transparent;
            padding: 0.75rem 0;
        }
        
        .message {
            max-width: var(--max-width);
            width: 100%;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        .user-message .message {
            display: flex;
            justify-content: flex-end;
        }
        
        .user-bubble {
            background-color: var(--user-bubble-bg);
            color: var(--text-primary);
            padding: 0.75rem 1rem;
            border-radius: 1.125rem;
            max-width: 70%;
            word-wrap: break-word;
        }
        
        .assistant-content {
            color: var(--text-primary);
        }
        
        .message-actions {
            display: flex;
            gap: 0.25rem;
            margin-top: 0.75rem;
            align-items: center;
        }
        
        .action-btn {
            background: transparent;
            border: none;
            padding: 0.375rem;
            border-radius: 0.375rem;
            cursor: pointer;
            color: var(--text-tertiary);
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-btn:hover {
            background-color: var(--hover-bg);
            color: var(--text-primary);
        }
        
        .message-content {
            flex: 1;
            line-height: 1.6;
            font-size: 0.9375rem;
            color: var(--text-primary);
        }
        
        .message-content p {
            margin-bottom: 1rem;
        }
        
        .message-content p:last-child {
            margin-bottom: 0;
        }
        
        .message-content pre {
            background-color: var(--surface);
            color: var(--text-primary);
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin: 1rem 0;
            font-size: 0.875rem;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-xs);
            transition: all var(--transition-fast);
        }
        
        .message-content pre:hover {
            box-shadow: var(--shadow-sm);
            border-color: var(--border);
        }
        
        .message-content code {
            background-color: var(--surface);
            color: var(--text-primary);
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-size: 0.875em;
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Fira Code', monospace;
            border: 1px solid var(--border-light);
        }
        
        /* Input Area */
        .input-section {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, var(--background) 85%, transparent);
            padding: 1rem 0 1.5rem;
            pointer-events: none;
        }
        
        .input-container {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 1.5rem;
            pointer-events: all;
        }
        
        .input-wrapper {
            position: relative;
            background-color: var(--background);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            box-shadow: var(--shadow-md);
            transition: all var(--transition-base);
            overflow: hidden;
        }
        
        .input-wrapper:focus-within {
            border-color: var(--border);
            box-shadow: var(--shadow-lg);
        }
        
        .message-textarea {
            width: 100%;
            border: none;
            outline: none;
            resize: none;
            padding: 1rem 3.5rem 1rem 1rem;
            font-size: 1rem;
            font-family: inherit;
            line-height: 1.5;
            max-height: 200px;
            background: transparent;
            color: var(--text-primary);
        }
        
        .message-textarea::placeholder {
            color: var(--text-secondary);
        }
        
        .send-button {
            position: absolute;
            bottom: 0.875rem;
            right: 0.875rem;
            background: transparent;
            color: var(--text-tertiary);
            border: none;
            border-radius: 0.375rem;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-base);
            font-size: 1rem;
        }
        
        .send-button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        
        .send-button:hover:not(:disabled) {
            color: var(--text-primary);
            background: var(--hover-bg);
        }
        
        .send-button.active {
            color: var(--primary);
        }
        
        /* Font Awesome icon adjustments */
        .action-btn i {
            font-size: 14px;
        }
        
        .thread-menu-btn i {
            font-size: 14px;
        }
        
        .new-chat-btn i {
            font-size: 14px;
        }
        
        .send-button i {
            font-size: 14px;
        }
        
        .toggle-sidebar-btn i {
            font-size: 18px;
        }
        
        /* Typing Indicator */
        .typing-indicator {
            display: flex;
            gap: 0.3rem;
            padding: 0.5rem 0;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            background-color: var(--text-secondary);
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }
        
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.5;
            }
            30% {
                transform: translateY(-15px);
                opacity: 1;
            }
        }
        
        /* Helper Text */
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                z-index: 200;
                box-shadow: var(--shadow-lg);
                transform: translateX(-100%);
            }
            
            .sidebar:not(.hidden) {
                transform: translateX(0);
            }
            
            .toggle-sidebar-btn {
                display: block;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
            
            .welcome-subtitle {
                font-size: 1rem;
            }
            
            .message {
                gap: 0.75rem;
                padding: 1.25rem 1rem;
            }
            
            .input-section {
                padding: 1rem 0 1.5rem;
            }
            
            
            .app-title {
                font-size: 1.125rem;
            }
        }
        
        /* Sidebar overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 150;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        /* Loading State */
        .message-content.loading {
            opacity: 0.7;
        }
        
        /* Skeleton Loading Styles */
        .skeleton-container {
            padding: 0.5rem;
        }
        
        .skeleton-item {
            padding: 0.75rem;
            margin: 0 0.5rem 0.5rem;
            border-radius: 0.5rem;
            background: var(--background);
        }
        
        .skeleton-line {
            height: 0.875rem;
            background: linear-gradient(
                90deg,
                var(--border-light) 0%,
                var(--surface-secondary) 50%,
                var(--border-light) 100%
            );
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.5s ease-in-out infinite;
            border-radius: 0.25rem;
        }
        
        .skeleton-title {
            width: 75%;
            margin-bottom: 0.5rem;
        }
        
        .skeleton-date {
            width: 40%;
            height: 0.75rem;
            opacity: 0.6;
        }
        
        @keyframes skeleton-shimmer {
            0% {
                background-position: 200% center;
            }
            100% {
                background-position: -200% center;
            }
        }
        
        /* Scrollbar Styles */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        
        ::-webkit-scrollbar-thumb {
            background-color: var(--text-quaternary);
            border-radius: 4px;
            transition: background-color var(--transition-fast);
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background-color: var(--text-tertiary);
        }
        
        /* Empty state styles */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.5s ease;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>

    <div class="app-container">
        <!-- Header -->
        <header class="app-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="toggle-sidebar-btn" id="toggleSidebar" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="app-branding">
                        <h1 class="app-title">AI Assistant</h1>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main wrapper with sidebar -->
        <div class="main-wrapper">
            <!-- Sidebar -->
            <aside class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <button class="new-chat-btn" onclick="startNewChat()">
                        <i class="fas fa-plus"></i> New Chat
                    </button>
                </div>
                <div class="threads-list" id="threadsList">
                    <!-- Threads will be loaded here -->
                </div>
            </aside>
            
            <!-- Sidebar overlay for mobile -->
            <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

            <!-- Content area -->
            <div class="content-area">
                <!-- Main Content -->
                <main class="main-content" id="mainContent">
            <!-- Welcome Screen -->
            <div class="welcome-container" id="welcomeScreen">
                <div class="welcome-content">
                    <h2 class="welcome-title">AI Assistant</h2>
                    <p class="welcome-subtitle">How can I help you today?</p>
                </div>
            </div>

            <!-- Chat Container -->
            <div class="chat-container" id="chatContainer">
                <div class="messages-list" id="messagesList">
                    <!-- Messages will be inserted here -->
                </div>
            </div>
        </main>
            </div>
        </div>

        <!-- Input Section -->
        <div class="input-section">
            <div class="input-container">
                <div class="input-wrapper">
                    <textarea 
                        class="message-textarea" 
                        id="messageInput" 
                        placeholder="Send a message"
                        rows="1"
                    ></textarea>
                    <button class="send-button" id="sendButton" title="Send message">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Common JS if needed -->
    <!-- <script src="/assets/js/common.js"></script> -->
    <script>
    $(document).ready(function() {
        // Elements
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const mainContent = document.getElementById('mainContent');
        const welcomeScreen = document.getElementById('welcomeScreen');
        const chatContainer = document.getElementById('chatContainer');
        const messagesList = document.getElementById('messagesList');
        
        // State
        let threadId = null;
        let isProcessing = false;
        let streamingMessageId = 0;
        let userInfo = { full_name: 'Guest', email: '', phone_number: '', nationality: '' };
        
        // Helper function to escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
        
        // Check if there's a thread_id in URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const urlThreadId = urlParams.get('thread_id');
        
        // Initialize
        function init() {
            // Hide sidebar on mobile by default
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.add('hidden');
            }
            
            initializeThread();
            loadThreadsList();
            messageInput.focus();
            updateSendButton();
        }
        
        // Toggle sidebar
        window.toggleSidebar = function() {
            $('#sidebar').toggleClass('hidden');
            if (window.innerWidth <= 768) {
                $('#sidebarOverlay').toggleClass('active');
            }
        }
        
        // Load threads list (only called on initial load)
        async function loadThreadsList() {
            const $threadsList = $('#threadsList');
            
            // Show skeleton loading state
            $threadsList.html(`
                <div class="skeleton-container">
                    ${[1, 2, 3, 4].map(() => `
                        <div class="skeleton-item">
                            <div class="skeleton-line skeleton-title"></div>
                            <div class="skeleton-line skeleton-date"></div>
                        </div>
                    `).join('')}
                </div>
            `);
            
            try {
                const response = await fetch('api/get_threads.php');
                const data = await response.json();
                
                if (data.success && data.threads) {
                    $threadsList.empty();
                    
                    if (data.threads.length === 0) {
                        $threadsList.html(`
                            <div class="empty-state">
                                <i class="far fa-comments" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                <p style="margin: 0; font-weight: 500; color: var(--text-secondary);">No chats yet</p>
                                <p style="margin: 0.25rem 0 0; font-size: 0.813rem; color: var(--text-tertiary);">Start a new conversation!</p>
                            </div>
                        `);
                        return;
                    }
                    
                    data.threads.forEach(thread => {
                        const $threadItem = $(`
                            <div class="thread-item" data-thread-id="${thread.id}">
                                <div class="thread-content">
                                    <div class="thread-title">${escapeHtml(thread.title)}</div>
                                    <div class="thread-date">${formatDate(thread.updated_at)}</div>
                                </div>
                                <div class="thread-actions">
                                    <button class="thread-menu-btn" onclick="toggleThreadMenu(event, '${thread.id}')">
                                    <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <div class="thread-menu" id="menu-${thread.id}">
                                    <button class="thread-menu-item" onclick="renameThread('${thread.id}')">
                                        <i class="fas fa-pen"></i>
                                            Rename
                                        </button>
                                    <button class="thread-menu-item delete" onclick="deleteThread('${thread.id}')">
                                    <i class="fas fa-trash"></i>
                                    Delete
                                    </button>
                                    </div>
                                </div>
                            </div>
                        `);
                        
                        if (thread.id === threadId) {
                            $threadItem.addClass('active');
                        }
                        
                        $threadsList.append($threadItem);
                    });
                    
                    // Bind click handlers using delegation
                    $threadsList.off('click', '.thread-item');
                    $threadsList.on('click', '.thread-item', function(e) {
                        // Don't trigger if clicking on menu or actions
                        if ($(e.target).closest('.thread-actions').length > 0) {
                            return;
                        }
                        const clickedThreadId = $(this).data('thread-id');
                        loadThread(clickedThreadId);
                    });
                }
            } catch (error) {
                console.error('Error loading threads:', error);
                $threadsList.html(`
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p style="margin: 0; font-weight: 500; color: var(--text-secondary);">Error loading chats</p>
                    <p style="margin: 0.25rem 0 0; font-size: 0.813rem; color: var(--text-tertiary);">Please try again later</p>
                </div>
            `);
            }
        }
        
        // Add or update thread in sidebar
        function updateThreadInSidebar() {
            const $existingThread = $(`.thread-item[data-thread-id="${threadId}"]`);
            
            if ($existingThread.length === 0) {
                // New thread - add to top
                const firstMessage = messagesList.querySelector('.user-message .message-content')?.textContent || 'New Chat';
                const title = firstMessage.substring(0, 50) + (firstMessage.length > 50 ? '...' : '');
                
                const $newThread = $(`
                    <div class="thread-item active" data-thread-id="${threadId}">
                        <div class="thread-content">
                            <div class="thread-title">${escapeHtml(title)}</div>
                            <div class="thread-date">Just now</div>
                        </div>
                        <div class="thread-actions">
                            <button class="thread-menu-btn" onclick="toggleThreadMenu(event, '${threadId}')">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            <div class="thread-menu" id="menu-${threadId}">
                                <button class="thread-menu-item" onclick="renameThread('${threadId}')">
                                    <i class="fas fa-pen"></i>
                                    Rename
                                </button>
                                <button class="thread-menu-item delete" onclick="deleteThread('${threadId}')">
                                    <i class="fas fa-trash"></i>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                `);
                
                // Remove empty state if exists
                $('#threadsList .empty-state').remove();
                
                // Add to top of list
                $('#threadsList').prepend($newThread);
                
                // Update all other threads to inactive
                $('.thread-item').not($newThread).removeClass('active');
            } else {
                // Existing thread - move to top and update date
                $existingThread.find('.thread-date').text('Just now');
                $existingThread.detach().prependTo('#threadsList');
                
                // Update active state
                $('.thread-item').removeClass('active');
                $existingThread.addClass('active');
            }
        }
        
        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffTime = now - date;
            const diffMinutes = Math.floor(diffTime / (1000 * 60));
            const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffMinutes < 1) {
                return 'Just now';
            } else if (diffMinutes < 60) {
                return `${diffMinutes} minutes ago`;
            } else if (diffHours < 24) {
                return `${diffHours} hours ago`;
            } else if (diffDays === 0) {
                return 'Today';
            } else if (diffDays === 1) {
                return 'Yesterday';
            } else if (diffDays < 7) {
                return `${diffDays} days ago`;
            } else {
                return date.toLocaleDateString();
            }
        }
        
        // Load thread without reloading list
        async function loadThread(loadThreadId) {
            // Close sidebar on mobile
            if (window.innerWidth <= 768) {
                toggleSidebar();
            }
            
            // Clear current messages
            $('#messagesList').empty();
            
            // Update thread ID
            threadId = loadThreadId;
            sessionStorage.setItem('current_thread_id', threadId);
            
            // Update active thread in sidebar immediately
            $('.thread-item').removeClass('active');
            $(`.thread-item[data-thread-id="${threadId}"]`).addClass('active');
            
            // Load thread messages
            await loadExistingThread(threadId);
        }
        
        // Initialize thread
        async function initializeThread() {
            // Check if there's a thread_id in URL parameters
            if (urlThreadId) {
                threadId = urlThreadId;
                sessionStorage.setItem('current_thread_id', threadId);
                await loadExistingThread(threadId);
            } else {
                // Check if thread already exists in sessionStorage
                const existingThreadId = sessionStorage.getItem('current_thread_id');
                if (existingThreadId) {
                    // Reuse existing thread
                    threadId = existingThreadId;
                } else {
                    // Generate new thread ID
                    threadId = 'thread_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                    sessionStorage.setItem('current_thread_id', threadId);
                }
            }
            
            // Create thread in database
            try {
                const response = await fetch('api/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'create_thread',
                        thread_id: threadId,
                        user_info: userInfo
                    })
                });
                
                const result = await response.json();
                if (!response.ok || !result.success) {
                    console.error('Failed to create thread:', result.error);
                }
            } catch (error) {
                console.error('Error creating thread:', error);
            }
        }
        
        
        // Load existing thread messages
        async function loadExistingThread(threadId) {
            try {
                const response = await fetch(`api/get_thread_messages.php?thread_id=${encodeURIComponent(threadId)}`);
                const data = await response.json();
                
                if (data.success && data.messages && data.messages.length > 0) {
                    showChatView();
                    
                    // Clear messages first
                    $('#messagesList').empty();
                    
                    // Load previous messages
                    data.messages.forEach(msg => {
                        addMessage(msg.role, msg.message, false);
                    });
                    
                    // Update page title if thread has a title
                    if (data.thread && data.thread.title) {
                        document.title = `${data.thread.title} - AI Assistant`;
                    }
                    
                    // Load user info from thread if available
                    if (data.thread) {
                        userInfo = {
                            full_name: data.thread.full_name || 'Guest',
                            email: data.thread.email || '',
                            phone_number: data.thread.phone_number || '',
                            nationality: data.thread.nationality || ''
                        };
                    }
                    
                    // Clean up URL without refreshing
                    window.history.replaceState({}, document.title, window.location.pathname);
                } else {
                    // No messages, show welcome screen
                    $('#welcomeScreen').css('display', 'flex');
                    $('#chatContainer').hide();
                }
            } catch (error) {
                console.error('Error loading thread:', error);
                // Show welcome screen on error
                $('#welcomeScreen').css('display', 'flex');
                $('#chatContainer').hide();
            }
        }
        
        // Auto-resize textarea
        function autoResizeTextarea() {
            messageInput.style.height = 'auto';
            const newHeight = Math.min(messageInput.scrollHeight, 150);
            messageInput.style.height = newHeight + 'px';
        }
        
        // Update send button state
        function updateSendButton() {
            const hasText = messageInput.value.trim().length > 0;
            sendButton.disabled = !hasText || isProcessing;
            if (hasText && !isProcessing) {
                sendButton.classList.add('active');
            } else {
                sendButton.classList.remove('active');
            }
        }
        
        // Switch to chat view
        function showChatView() {
            $('#welcomeScreen').hide();
            $('#chatContainer').show();
        }
        
        // Scroll to bottom
        function scrollToBottom() {
            setTimeout(() => {
                mainContent.scrollTop = mainContent.scrollHeight;
            }, 50);
        }
        
        // Format message content
        function formatMessageContent(text) {
            // Convert line breaks
            text = text.replace(/\n/g, '<br>');
            
            // Format code blocks
            text = text.replace(/```(\w+)?\n([\s\S]*?)```/g, (match, lang, code) => {
                return `<pre><code class="${lang || ''}">${code.trim()}</code></pre>`;
            });
            
            // Format inline code
            text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
            
            // Format bold
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            
            // Format italic
            text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
            
            return text;
        }
        
        // Add message to chat
        function addMessage(role, content, isStreaming = false) {
            showChatView();
            
            const messageWrapper = document.createElement('div');
            messageWrapper.className = `message-wrapper ${role}-message`;
            
            const message = document.createElement('div');
            message.className = 'message';
            
            if (role === 'user') {
                const userBubble = document.createElement('div');
                userBubble.className = 'user-bubble';
                userBubble.innerHTML = formatMessageContent(content);
                message.appendChild(userBubble);
            } else {
                const assistantContent = document.createElement('div');
                assistantContent.className = 'assistant-content';
                
                const messageContent = document.createElement('div');
                messageContent.className = 'message-content';
                if (isStreaming) {
                    messageContent.classList.add('loading');
                    streamingMessageId++;
                    messageContent.id = `streaming-content-${streamingMessageId}`;
                }
                
                if (content) {
                    messageContent.innerHTML = formatMessageContent(content);
                } else {
                    messageContent.innerHTML = '<div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>';
                }
                
                assistantContent.appendChild(messageContent);
                
                // Add action buttons for assistant messages
                if (content && !isStreaming) {
                    const actions = document.createElement('div');
                    actions.className = 'message-actions';
                    actions.innerHTML = `
                        <button class="action-btn" onclick="copyMessage(this)" title="Copy">
                            <i class="far fa-copy"></i>
                        </button>
                        <button class="action-btn" title="Good response">
                            <i class="far fa-thumbs-up"></i>
                        </button>
                        <button class="action-btn" title="Bad response">
                            <i class="far fa-thumbs-down"></i>
                        </button>
                    `;
                    assistantContent.appendChild(actions);
                }
                
                message.appendChild(assistantContent);
            }
            
            messageWrapper.appendChild(message);
            messagesList.appendChild(messageWrapper);
            
            scrollToBottom();
            
            return { element: role === 'assistant' ? message.querySelector('.message-content') : null, id: streamingMessageId };
        }
        
        // Update streaming message
        function updateStreamingContent(content, messageId) {
            const streamingElement = document.getElementById(`streaming-content-${messageId}`);
            if (streamingElement) {
                streamingElement.classList.remove('loading');
                streamingElement.innerHTML = formatMessageContent(content);
                
                // Add action buttons after streaming is complete
                const assistantContent = streamingElement.closest('.assistant-content');
                if (assistantContent && !assistantContent.querySelector('.message-actions')) {
                    const actions = document.createElement('div');
                    actions.className = 'message-actions';
                    actions.innerHTML = `
                        <button class="action-btn" onclick="copyMessage(this)" title="Copy">
                            <i class="far fa-copy"></i>
                        </button>
                        <button class="action-btn" title="Good response">
                            <i class="far fa-thumbs-up"></i>
                        </button>
                        <button class="action-btn" title="Bad response">
                            <i class="far fa-thumbs-down"></i>
                        </button>
                    `;
                    assistantContent.appendChild(actions);
                }
                
                scrollToBottom();
            }
        }
        
        // Send message
        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!message || isProcessing) return;
            
            // Ensure thread exists before sending message
            if (!threadId) {
                console.error('No thread ID available');
                alert('Error: No chat session available. Please refresh the page.');
                return;
            }
            
            isProcessing = true;
            updateSendButton();
            
            // Double-check thread creation
            try {
                const threadCheck = await fetch('api/api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_thread',
                        thread_id: threadId,
                        user_info: userInfo
                    })
                });
                
                if (!threadCheck.ok) {
                    console.error('Failed to ensure thread exists');
                    isProcessing = false;
                    updateSendButton();
                    alert('Error: Failed to create chat session. Please try again.');
                    return;
                }
            } catch (error) {
                console.error('Thread check error:', error);
                isProcessing = false;
                updateSendButton();
                return;
            }
            
            // Add user message
            addMessage('user', message);
            
            // Clear input
            messageInput.value = '';
            autoResizeTextarea();
            updateSendButton();
            
            // Add assistant message placeholder and get its ID
            const assistantMessage = addMessage('assistant', '', true);
            const currentMessageId = assistantMessage.id;
            
            try {
                console.log('Sending message with user info:', userInfo);
                
                // Create EventSource for streaming with properly encoded user_info
                const params = new URLSearchParams({
                    action: 'stream',
                    thread_id: threadId,
                    message: message,
                    user_info: JSON.stringify(userInfo) // Properly encode as JSON string
                });
                
                console.log('Request URL:', `api/api.php?${params}`);
                
                const eventSource = new EventSource(`api/api.php?${params}`);
                
                let fullResponse = '';
                let hasError = false;
                
                eventSource.addEventListener('chunk', (e) => {
                    fullResponse += e.data;
                    updateStreamingContent(fullResponse, currentMessageId);
                });
                
                eventSource.addEventListener('done', () => {
                    eventSource.close();
                    isProcessing = false;
                    updateSendButton();
                    messageInput.focus();
                    
                    // Remove the streaming ID after completion
                    const element = document.getElementById(`streaming-content-${currentMessageId}`);
                    if (element) {
                        element.removeAttribute('id');
                    }
                    
                    // Add or update thread in sidebar
                    updateThreadInSidebar();
                });
                
                eventSource.addEventListener('error', (e) => {
                    hasError = true;
                    eventSource.close();
                    isProcessing = false;
                    updateSendButton();
                    
                    if (!fullResponse) {
                        updateStreamingContent('I apologize, but I encountered an error. Please try again or refresh the page if the issue persists.', currentMessageId);
                    }
                    
                    // Remove the streaming ID after error
                    const element = document.getElementById(`streaming-content-${currentMessageId}`);
                    if (element) {
                        element.removeAttribute('id');
                    }
                });
                
                // Timeout fallback
                setTimeout(() => {
                    if (isProcessing && !fullResponse && !hasError) {
                        eventSource.close();
                        isProcessing = false;
                        updateSendButton();
                        updateStreamingContent('The request timed out. Please try again.', currentMessageId);
                        
                        // Remove the streaming ID after timeout
                        const element = document.getElementById(`streaming-content-${currentMessageId}`);
                        if (element) {
                            element.removeAttribute('id');
                        }
                    }
                }, 30000);
                
            } catch (error) {
                console.error('Error:', error);
                isProcessing = false;
                updateSendButton();
                updateStreamingContent('I apologize for the technical difficulty. Please try again.', currentMessageId);
                
                // Remove the streaming ID after error
                const element = document.getElementById(`streaming-content-${currentMessageId}`);
                if (element) {
                    element.removeAttribute('id');
                }
            }
        }
        
        // Start new chat
        window.startNewChat = function() {
            // Clear current thread
            sessionStorage.removeItem('current_thread_id');
            threadId = null;
            
            // Clear messages
            $('#messagesList').empty();
            
            // Show welcome screen
            $('#welcomeScreen').css('display', 'flex');
            $('#chatContainer').hide();
            
            // Update active state in sidebar
            $('.thread-item').removeClass('active');
            
            // Initialize new thread
            initializeThread();
            
            // Close sidebar on mobile
            if (window.innerWidth <= 768) {
                toggleSidebar();
            }
            
            // Focus on input
            messageInput.focus();
        }
        
        // Event Listeners
        messageInput.addEventListener('input', () => {
            autoResizeTextarea();
            updateSendButton();
        });
        
        messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        sendButton.addEventListener('click', sendMessage);
        
        
        // Thread menu functions
        window.toggleThreadMenu = function(event, threadId) {
            event.stopPropagation();
            const menu = document.getElementById(`menu-${threadId}`);
            const allMenus = document.querySelectorAll('.thread-menu');
            
            // Close all other menus
            allMenus.forEach(m => {
                if (m !== menu) {
                    m.classList.remove('show');
                }
            });
            
            // Toggle current menu
            menu.classList.toggle('show');
            
            // Close menu when clicking outside
            setTimeout(() => {
                document.addEventListener('click', function closeMenu(e) {
                    if (!menu.contains(e.target)) {
                        menu.classList.remove('show');
                        document.removeEventListener('click', closeMenu);
                    }
                });
            }, 0);
        }
        
        window.renameThread = async function(threadId) {
            const threadItem = $(`.thread-item[data-thread-id="${threadId}"]`);
            const currentTitle = threadItem.find('.thread-title').text();
            const newTitle = prompt('Enter new name:', currentTitle);
            
            if (newTitle && newTitle !== currentTitle) {
                try {
                    const response = await fetch('api/rename_thread.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ thread_id: threadId, title: newTitle })
                    });
                    
                    if (response.ok) {
                        threadItem.find('.thread-title').text(newTitle);
                    }
                } catch (error) {
                    console.error('Error renaming thread:', error);
                }
            }
            
            // Hide menu
            document.getElementById(`menu-${threadId}`).classList.remove('show');
        }
        
        window.deleteThread = async function(threadId) {
            if (confirm('Are you sure you want to delete this chat?')) {
                try {
                    const response = await fetch('api/delete_thread.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ thread_id: threadId })
                    });
                    
                    if (response.ok) {
                        // Remove from sidebar
                        $(`.thread-item[data-thread-id="${threadId}"]`).remove();
                        
                        // If it was the active thread, start new chat
                        const currentThreadId = sessionStorage.getItem('current_thread_id');
                        if (threadId === currentThreadId) {
                            startNewChat();
                        }
                    }
                } catch (error) {
                    console.error('Error deleting thread:', error);
                }
            }
            
            // Hide menu
            document.getElementById(`menu-${threadId}`).classList.remove('show');
        }
        
        // Copy message function
        window.copyMessage = function(btn) {
            const messageContent = btn.closest('.assistant-content').querySelector('.message-content').innerText;
            navigator.clipboard.writeText(messageContent).then(() => {
                // Show feedback
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                }, 2000);
            });
        }
        
        // Initialize
        init();
    }); // End of jQuery document ready
    </script>
</body>
</html>