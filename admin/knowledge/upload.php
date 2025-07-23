<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Upload - AI Assistant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts Inter - NOTE: Consider adding font files locally or use system fonts -->
    <!-- <link href="assets/fonts/inter.css" rel="stylesheet"> -->
    <link href="../../assets/css/common.css" rel="stylesheet">
    <style>
        /* Page-specific styles */

        .btn-primary.full-width {
            width: 100%;
            justify-content: center;
        }

        .upload-card {
            background: var(--geist-card);
            border: 1px solid var(--geist-border);
            border-radius: var(--geist-radius);
            padding: 32px;
            margin-bottom: 24px;
        }

        .upload-section {
            margin-bottom: 40px;
        }

        .upload-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 16px 0;
            color: var(--geist-foreground);
        }

        .section-subtitle {
            color: var(--geist-muted);
            font-size: 14px;
            margin: 0 0 20px 0;
        }

        .upload-area {
            border: 2px dashed var(--geist-border);
            border-radius: var(--geist-radius);
            padding: 48px 24px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: var(--geist-background);
        }

        .upload-area:hover {
            border-color: var(--geist-primary);
            background: rgba(0, 112, 243, 0.05);
        }

        .upload-area.dragover {
            border-color: var(--geist-primary);
            background: rgba(0, 112, 243, 0.1);
            transform: scale(1.02);
        }

        .upload-text {
            font-size: 16px;
            font-weight: 500;
            margin: 0 0 8px 0;
            color: var(--geist-foreground);
        }

        .upload-subtext {
            font-size: 14px;
            color: var(--geist-muted);
            margin: 0;
        }

        .file-input {
            display: none;
        }

        .file-info {
            margin-top: 16px;
            padding: 16px;
            background: rgba(0, 112, 243, 0.1);
            border: 1px solid rgba(0, 112, 243, 0.2);
            border-radius: var(--geist-radius);
            display: none;
        }

        .file-info.show {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .file-name {
            font-weight: 500;
            color: var(--geist-foreground);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 32px 0;
            text-align: center;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--geist-border);
        }

        .divider-text {
            padding: 0 16px;
            color: var(--geist-muted);
            font-size: 12px;
            font-weight: 500;
            background: var(--geist-card);
        }









        @media (max-width: 768px) {
            .upload-card {
                padding: 24px 20px;
            }
            
            .upload-area {
                padding: 32px 16px;
            }
        }
    </style>
</head>
<body>
    <?php include '../nav.php'; ?>

    <div class="main-container">
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-header-text">
                    <h1 class="page-title">Upload Document</h1>
                    <p class="page-subtitle">Add new documents to your knowledge base</p>
                </div>
                <div class="header-actions">
                    <a href="list.php" class="btn-secondary">
                        Back to Documents
                    </a>
                </div>
            </div>
        </div>

        <div id="alert-container"></div>

        <div class="upload-card">
            <div class="upload-section">
                <h2 class="section-title">File Upload</h2>
                <p class="section-subtitle">Upload PDF or TXT files from your device</p>
                
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <p class="upload-text">Click to select file or drag and drop</p>
                    <p class="upload-subtext">Supports PDF and TXT files</p>
                    <input type="file" id="fileInput" class="file-input" accept=".pdf,.txt">
                </div>
                
                <div id="fileInfo" class="file-info">
                    <span id="fileName" class="file-name"></span>
                </div>
            </div>

            <div class="divider">
                <div class="divider-text">OR</div>
            </div>

            <div class="upload-section">
                <h2 class="section-title">Text Content</h2>
                <p class="section-subtitle">Paste or type your content directly</p>
                
                <div class="form-group">
                    <textarea 
                        id="textContent" 
                        class="form-control" 
                        rows="8" 
                        placeholder="Paste your text content here..."></textarea>
                </div>
            </div>

            <button id="submitBtn" class="btn-primary btn-large full-width">
                <span id="submit-text">Upload & Process Document</span>
            </button>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const textContent = document.getElementById('textContent');
        const submitBtn = document.getElementById('submitBtn');
        const uploadArea = document.querySelector('.upload-area');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const alertContainer = document.getElementById('alert-container');
        const submitText = document.getElementById('submit-text');

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeUpload();
        });

        function initializeUpload() {
            // File input change handler
            fileInput.addEventListener('change', handleFileSelect);

            // Drag and drop handlers
            uploadArea.addEventListener('dragover', handleDragOver);
            uploadArea.addEventListener('dragleave', handleDragLeave);
            uploadArea.addEventListener('drop', handleDrop);

            // Text area handler
            textContent.addEventListener('input', handleTextInput);

            // Submit handler
            submitBtn.addEventListener('click', handleSubmit);

            // Prevent default drag behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                document.addEventListener(eventName, preventDefaults, false);
            });
        }

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function handleFileSelect() {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                showFileInfo(file);
                clearTextContent();
            } else {
                hideFileInfo();
            }
        }

        function handleDragOver(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        }

        function handleDragLeave(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        }

        function handleDrop(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                
                // Check file type
                if (file.type === 'application/pdf' || file.type === 'text/plain' || file.name.endsWith('.txt')) {
                    fileInput.files = files;
                    showFileInfo(file);
                    clearTextContent();
                } else {
                    showAlert('Please select a PDF or TXT file.', 'warning');
                }
            }
        }

        function handleTextInput() {
            if (textContent.value.trim()) {
                clearFileInput();
            }
        }

        function showFileInfo(file) {
            fileName.textContent = file.name;
            fileInfo.classList.add('show');
        }

        function hideFileInfo() {
            fileInfo.classList.remove('show');
        }

        function clearFileInput() {
            fileInput.value = '';
            hideFileInfo();
        }

        function clearTextContent() {
            textContent.value = '';
        }

        async function handleSubmit() {
            const hasFile = fileInput.files.length > 0;
            const hasText = textContent.value.trim();

            if (!hasFile && !hasText) {
                showAlert('Please select a file or enter text content.', 'warning');
                return;
            }

            try {
                setLoadingState(true);

                const formData = new FormData();
                if (hasFile) {
                    formData.append('doc', fileInput.files[0]);
                }
                if (hasText) {
                    formData.append('raw_text', hasText);
                }

                const response = await fetch('api/upload_doc.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.status === 'ok') {
                    showAlert(
                        `Document "${result.filename}" processed successfully! Created ${result.chunks} chunks.`, 
                        'success'
                    );
                    
                    // Clear form
                    clearFileInput();
                    clearTextContent();
                    
                    // Redirect after success
                    setTimeout(() => {
                        window.location.href = 'list.php';
                    }, 2000);
                } else {
                    throw new Error(result.error || 'Unknown error occurred');
                }

            } catch (error) {
                showAlert('Error: ' + error.message, 'danger');
            } finally {
                setLoadingState(false);
            }
        }

        function setLoadingState(isLoading) {
            submitBtn.disabled = isLoading;
            
            if (isLoading) {
                submitText.innerHTML = '<span class="loading-spinner"></span> Processing...';
            } else {
                submitText.textContent = 'Upload & Process Document';
            }
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            
            alertDiv.innerHTML = `
                <div class="alert-content">${message}</div>
                <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                    ×
                </button>
            `;
            
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alertDiv);
            
            // Auto-remove success and warning alerts after 5 seconds
            if (type !== 'danger') {
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 5000);
            }
            
            // Scroll to top to show alert
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>