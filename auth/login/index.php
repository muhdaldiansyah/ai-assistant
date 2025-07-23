<?php
session_start();

// If the user is already logged in, redirect them to the admin chat page.
if (isset($_SESSION['user_id'])) {
    header('Location: ../../admin/chat/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AI Assistant</title>
    <style>
        /* All your CSS from the original post goes here. It is correct. */
        :root{color-scheme:light dark}body{font-family:system-ui,-apple-system,sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#f5f5f5}@media (prefers-color-scheme:dark){body{background:#1a1a1a}}.login-container{background:white;padding:2rem;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1);width:100%;max-width:400px}@media (prefers-color-scheme:dark){.login-container{background:#2a2a2a;box-shadow:0 2px 10px rgba(0,0,0,.3)}}h1{text-align:center;color:#333;margin-bottom:2rem}@media (prefers-color-scheme:dark){h1{color:#f0f0f0}}.form-group{margin-bottom:1.5rem}label{display:block;margin-bottom:.5rem;color:#555;font-weight:500}@media (prefers-color-scheme:dark){label{color:#ccc}}input[type=text],input[type=password]{width:100%;padding:.75rem;border:1px solid #ddd;border-radius:4px;font-size:1rem;box-sizing:border-box;background:white;color:#333}@media (prefers-color-scheme:dark){input[type=text],input[type=password]{background:#3a3a3a;border-color:#555;color:#f0f0f0}}input[type=text]:focus,input[type=password]:focus{outline:none;border-color:#4CAF50}button{width:100%;padding:.75rem;background:#4CAF50;color:white;border:none;border-radius:4px;font-size:1rem;font-weight:500;cursor:pointer;transition:background .2s}button:hover{background:#45a049}button:disabled{background:#ccc;cursor:not-allowed}.error-message{background:#fee;color:#c33;padding:.75rem;border-radius:4px;margin-bottom:1rem;display:none}@media (prefers-color-scheme:dark){.error-message{background:#4a2a2a;color:#faa}}.success-message{background:#efe;color:#3c3;padding:.75rem;border-radius:4px;margin-bottom:1rem;display:none}@media (prefers-color-scheme:dark){.success-message{background:#2a4a2a;color:#afa}}.info-text{text-align:center;margin-top:1rem;color:#666;font-size:.9rem}@media (prefers-color-scheme:dark){.info-text{color:#aaa}}.loading{display:inline-block;width:20px;height:20px;border:3px solid #f3f3f3;border-top:3px solid #3498db;border-radius:50%;animation:spin 1s linear infinite;margin-left:10px;vertical-align:middle}@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
    </style>
</head>
<body>
    <div class="login-container">
        <h1>AI Assistant Login</h1>
        
        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>
        
        <form id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="your_username" autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="current-password">
            </div>
            
            <button type="submit" id="submitBtn">
                <span id="btnText">Login</span>
                <span class="loading" id="loading" style="display: none;"></span>
            </button>
        </form>
        
        <div class="info-text">
            Don't have an account? <a href="../register" style="color: #4CAF50; text-decoration: none;">Register here</a>
        </div>
    </div>
    
    <script>
        const form = document.getElementById('loginForm');
        const errorDiv = document.getElementById('errorMessage');
        const successDiv = document.getElementById('successMessage');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const loading = document.getElementById('loading');
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Reset UI state
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            submitBtn.disabled = true;
            btnText.textContent = 'Logging in';
            loading.style.display = 'inline-block';
            
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            try {
                // This sends the form data to our backend API script
                const response = await fetch('../api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    // Success! Show a message and redirect.
                    successDiv.textContent = 'Login successful! Redirecting...';
                    successDiv.style.display = 'block';
                    setTimeout(() => {
                        window.location.href = result.redirect || '../../admin/chat/';
                    }, 1000);
                } else {
                    // Failure: show the error from the server and re-enable the form.
                    errorDiv.textContent = result.error || 'Login failed. Please check your credentials.';
                    errorDiv.style.display = 'block';
                    submitBtn.disabled = false;
                    btnText.textContent = 'Login';
                    loading.style.display = 'none';
                }
            } catch (error) {
                // Network or other critical error
                console.error('Fetch Error:', error);
                errorDiv.textContent = 'A network error occurred. Please check the console (F12) for details.';
                errorDiv.style.display = 'block';
                submitBtn.disabled = false;
                btnText.textContent = 'Login';
                loading.style.display = 'none';
            }
        });
        
        document.getElementById('username').focus();
    </script>
</body>
</html>