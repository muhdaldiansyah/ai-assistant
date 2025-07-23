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
    
    <!-- Bootstrap CSS - NOTE: Add bootstrap.min.css to assets/css/ -->
    <!-- <link href="assets/css/bootstrap.min.css" rel="stylesheet"> -->
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --blenstrive-primary: #C8102E;
            --blenstrive-primary-hover: #A00D24;
            --blenstrive-primary-light: #FFEBEE;
            --surface-color: #f8f9fa;
            --border-color: #dee2e6;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
            --max-width: 52rem;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background-color: #ffffff;
            height: 100vh;
            overflow: hidden;
        }
        
        
        .btn-secondary {
            background: white;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-secondary:hover {
            background-color: var(--surface-color);
            border-color: var(--blenstrive-primary);
            color: var(--blenstrive-primary);
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
            background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
        }
        
        /* Header */
        .app-header {
            background-color: #ffffff;
            box-shadow: var(--shadow-sm);
            position: relative;
            z-index: 100;
        }
        
        .header-content {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .app-branding {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .app-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }
        
        .app-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }
        
        .app-title span {
            color: var(--blenstrive-primary);
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
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .welcome-content {
            max-width: var(--max-width);
            width: 100%;
            text-align: center;
        }
        
        
        .welcome-title {
            font-size: 2.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--blenstrive-primary) 0%, #E91E63 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .welcome-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            font-weight: 400;
        }
        
        .suggestion-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .suggestion-card {
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: left;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            box-shadow: var(--shadow-sm);
        }
        
        .suggestion-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--blenstrive-primary);
        }
        
        
        .suggestion-content {
            flex: 1;
        }
        
        .suggestion-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .suggestion-example {
            font-size: 0.875rem;
            color: var(--text-secondary);
            line-height: 1.4;
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
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message-wrapper.user-message {
            background-color: transparent;
        }
        
        .message-wrapper.assistant-message {
            background-color: var(--surface-color);
            margin-left: -100vw;
            margin-right: -100vw;
            padding-left: 100vw;
            padding-right: 100vw;
        }
        
        .message {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 1.75rem 1.5rem;
            display: flex;
            gap: 1rem;
        }
        
        .message-avatar {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            box-shadow: var(--shadow-sm);
        }
        
        .user-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .assistant-avatar {
            background: linear-gradient(135deg, var(--blenstrive-primary) 0%, var(--blenstrive-primary-hover) 100%);
            color: white;
        }
        
        .message-content {
            flex: 1;
            line-height: 1.75;
            font-size: 0.95rem;
            color: var(--text-primary);
        }
        
        .message-content p {
            margin-bottom: 1rem;
        }
        
        .message-content p:last-child {
            margin-bottom: 0;
        }
        
        .message-content pre {
            background-color: #1e1e1e;
            color: #d4d4d4;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin: 1rem 0;
            font-size: 0.875rem;
        }
        
        .message-content code {
            background-color: rgba(200, 16, 46, 0.1);
            color: var(--blenstrive-primary);
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-size: 0.875em;
            font-family: 'Consolas', 'Monaco', monospace;
        }
        
        /* Input Area */
        .input-section {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, #ffffff 60%, transparent);
            padding: 1.5rem 0 2rem;
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
            background-color: #ffffff;
            border: 2px solid var(--border-color);
            border-radius: 1.5rem;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        
        .input-wrapper:focus-within {
            border-color: var(--blenstrive-primary);
            transform: translateY(-1px);
            box-shadow: 0 12px 32px rgba(200, 16, 46, 0.15);
        }
        
        .message-textarea {
            width: 100%;
            border: none;
            outline: none;
            resize: none;
            padding: 1rem 3.5rem 1rem 1.25rem;
            font-size: 0.95rem;
            font-family: inherit;
            line-height: 1.5;
            max-height: 150px;
            background: transparent;
            color: var(--text-primary);
        }
        
        .message-textarea::placeholder {
            color: var(--text-secondary);
        }
        
        .send-button {
            position: absolute;
            bottom: 0.75rem;
            right: 0.75rem;
            background: linear-gradient(135deg, var(--blenstrive-primary) 0%, var(--blenstrive-primary-hover) 100%);
            color: white;
            border: none;
            border-radius: 0.75rem;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1.125rem;
        }
        
        .send-button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: scale(0.95);
        }
        
        .send-button:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(200, 16, 46, 0.3);
        }
        
        .send-button:active:not(:disabled) {
            transform: scale(0.95);
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
            background-color: var(--blenstrive-primary);
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
        .input-helper {
            text-align: center;
            margin-top: 0.75rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        /* University Badge */
        .university-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--blenstrive-primary-light);
            color: var(--blenstrive-primary);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 1rem;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .welcome-title {
                font-size: 2rem;
            }
            
            .welcome-subtitle {
                font-size: 1rem;
            }
            
            .suggestion-grid {
                grid-template-columns: 1fr;
            }
            
            .message {
                gap: 0.75rem;
                padding: 1.25rem 1rem;
            }
            
            .input-section {
                padding: 1rem 0 1.5rem;
            }
            
            .app-logo {
                width: 32px;
                height: 32px;
            }
            
            .app-title {
                font-size: 1.125rem;
            }
        }
        
        /* Loading State */
        .message-content.loading {
            opacity: 0.7;
        }
        
        /* Smooth Scrollbar */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .main-content::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .main-content::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb:hover {
            background-color: rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>

    <div class="app-container">
        <!-- Header -->
        <header class="app-header">
            <div class="header-content">
                <div class="app-branding">
                    <h1 class="app-title">AI Assistant<span>Bot</span></h1>
                </div>
                <button onclick="startNewChat()" class="btn-secondary" id="newChatBtn" style="padding: 0.5rem 1rem; font-size: 0.875rem; display: none;">
                    + New Chat
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Welcome Screen -->
            <div class="welcome-container" id="welcomeScreen">
                <div class="welcome-content">
                    <h2 class="welcome-title">Welcome to AI Assistant</h2>
                    <p class="welcome-subtitle">Your intelligent AI assistant</p>
                    
                    <div class="suggestion-grid">
                        <div class="suggestion-card" data-prompt="What can you help me with?">
                            <div class="suggestion-content">
                                <div class="suggestion-title">General Help</div>
                                <div class="suggestion-example">
                                    Get help with questions, tasks, and general information
                                </div>
                            </div>
                        </div>
                        
                        <div class="suggestion-card" data-prompt="How can I get started?">
                            <div class="suggestion-content">
                                <div class="suggestion-title">Getting Started</div>
                                <div class="suggestion-example">
                                    Discover how to begin using the AI assistant effectively
                                </div>
                            </div>
                        </div>
                        
                        <div class="suggestion-card" data-prompt="Tell me about your capabilities">
                            <div class="suggestion-content">
                                <div class="suggestion-title">Capabilities</div>
                                <div class="suggestion-example">
                                    Learn about what the AI assistant can do for you
                                </div>
                            </div>
                        </div>
                        
                        <div class="suggestion-card" data-prompt="What services do you provide?">
                            <div class="suggestion-content">
                                <div class="suggestion-title">Services</div>
                                <div class="suggestion-example">
                                    Explore the range of assistance and support available
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="university-badge">
                        AI Assistant
                    </div>
                </div>
            </div>

            <!-- Chat Container -->
            <div class="chat-container" id="chatContainer">
                <div class="messages-list" id="messagesList">
                    <!-- Messages will be inserted here -->
                </div>
            </div>
        </main>

        <!-- Input Section -->
        <div class="input-section">
            <div class="input-container">
                <div class="input-wrapper">
                    <textarea 
                        class="message-textarea" 
                        id="messageInput" 
                        placeholder="Ask me anything or get help with your questions..."
                        rows="1"
                    ></textarea>
                    <button class="send-button" id="sendButton">
Send
                    </button>
                </div>
                <div class="input-helper">
                    Press Enter to send • Shift+Enter for new line
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS - NOTE: Add bootstrap.bundle.min.js to assets/js/ -->
    <!-- <script src="assets/js/bootstrap.bundle.min.js"></script> -->
    
    <script src="/assets/js/common.js"></script>
    <script>
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
        
        // Check if there's a thread_id in URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const urlThreadId = urlParams.get('thread_id');
        
        // Initialize
        function init() {
            initializeThread();
            messageInput.focus();
            updateSendButton();
            document.getElementById('newChatBtn').style.display = 'block';
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
                    
                    // Enable chat for existing thread
                    document.getElementById('newChatBtn').style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading thread:', error);
                // Continue with new thread if loading fails
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
        }
        
        // Switch to chat view
        function showChatView() {
            welcomeScreen.style.display = 'none';
            chatContainer.style.display = 'block';
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
                return `<pre><code class="${lang || ''}">${escapeHtml(code.trim())}</code></pre>`;
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
            
            const avatar = document.createElement('div');
            avatar.className = `message-avatar ${role}-avatar`;
            
            if (role === 'user') {
                avatar.textContent = 'U';
            } else {
                avatar.textContent = 'AI';
            }
            
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
            
            message.appendChild(avatar);
            message.appendChild(messageContent);
            messageWrapper.appendChild(message);
            messagesList.appendChild(messageWrapper);
            
            scrollToBottom();
            
            return { element: messageContent, id: streamingMessageId };
        }
        
        // Update streaming message
        function updateStreamingContent(content, messageId) {
            const streamingElement = document.getElementById(`streaming-content-${messageId}`);
            if (streamingElement) {
                streamingElement.classList.remove('loading');
                streamingElement.innerHTML = formatMessageContent(content);
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
        function startNewChat() {
            if (confirm('Start a new chat? Current conversation will be saved in history.')) {
                sessionStorage.removeItem('current_thread_id');
                window.location.href = 'index.php';
            }
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
        
        // Suggestion cards
        document.querySelectorAll('.suggestion-card').forEach(card => {
            card.addEventListener('click', () => {
                messageInput.value = card.dataset.prompt;
                autoResizeTextarea();
                updateSendButton();
                messageInput.focus();
                sendMessage();
            });
        });
        
        // Initialize
        init();
    </script>
</body>
</html>