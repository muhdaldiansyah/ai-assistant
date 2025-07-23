<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../../admin/knowledge/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - AI Assistant</title>
    <style>
        /* All your original CSS is correct and goes here */
        :root { color-scheme: light dark; } body { font-family: system-ui, -apple-system, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f5f5f5; } @media (prefers-color-scheme: dark) { body { background: #1a1a1a; } } .register-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; } @media (prefers-color-scheme: dark) { .register-container { background: #2a2a2a; box-shadow: 0 2px 10px rgba(0,0,0,0.3); } } h1 { text-align: center; color: #333; margin-bottom: 2rem; } @media (prefers-color-scheme: dark) { h1 { color: #f0f0f0; } } .form-group { margin-bottom: 1.5rem; } label { display: block; margin-bottom: 0.5rem; color: #555; font-weight: 500; } @media (prefers-color-scheme: dark) { label { color: #ccc; } } input[type="text"], input[type="password"] { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; box-sizing: border-box; background: white; color: #333; } @media (prefers-color-scheme: dark) { input[type="text"], input[type="password"] { background: #3a3a3a; border-color: #555; color: #f0f0f0; } } input:focus { outline: none; border-color: #4CAF50; } button { width: 100%; padding: 0.75rem; background: #4CAF50; color: white; border: none; border-radius: 4px; font-size: 1rem; font-weight: 500; cursor: pointer; transition: background 0.2s; } button:hover { background: #45a049; } button:disabled { background: #ccc; cursor: not-allowed; } .error-message { background: #fee; color: #c33; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; display: none; } @media (prefers-color-scheme: dark) { .error-message { background: #4a2a2a; color: #faa; } } .success-message { background: #efe; color: #3c3; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; display: none; } @media (prefers-color-scheme: dark) { .success-message { background: #2a4a2a; color: #afa; } } .info-text { text-align: center; margin-top: 1rem; color: #666; font-size: 0.9rem; } @media (prefers-color-scheme: dark) { .info-text { color: #aaa; } } .info-text a { color: #4CAF50; text-decoration: none; } .info-text a:hover { text-decoration: underline; } .password-hint { font-size: 0.85rem; color: #666; margin-top: 0.25rem; } @media (prefers-color-scheme: dark) { .password-hint { color: #999; } } .loading { display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite; margin-left: 10px; vertical-align: middle; } @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>Create Account</h1>
        <div class="error-message" id="errorMessage"></div>
        <div class="success-message" id="successMessage"></div>
        <form id="registerForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="your_username" autocomplete="username" minlength="3">
                <div class="password-hint">Minimum 3 characters</div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="new-password" minlength="6">
                <div class="password-hint">Minimum 6 characters</div>
            </div>
            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required placeholder="••••••••" autocomplete="new-password">
            </div>
            <button type="submit" id="submitBtn">
                <span id="btnText">Create Account</span>
                <span class="loading" id="loading" style="display: none;"></span>
            </button>
        </form>
        <div class="info-text">
            Already have an account? <a href="../login">Login here</a>
        </div>
    </div>
    
    <script>
        // Your JavaScript is correct and does not need any changes.
        const form = document.getElementById('registerForm');
        const errorDiv = document.getElementById('errorMessage');
        const successDiv = document.getElementById('successMessage');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const loading = document.getElementById('loading');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';
            
            if (passwordInput.value !== confirmPasswordInput.value) {
                errorDiv.textContent = 'Passwords do not match';
                errorDiv.style.display = 'block';
                return;
            }
            
            submitBtn.disabled = true;
            btnText.textContent = 'Creating account';
            loading.style.display = 'inline-block';
            
            const data = {
                username: document.getElementById('username').value,
                password: passwordInput.value
            };
            
            try {
                const endpoint = '../api/register.php'; // Main registration endpoint
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    successDiv.textContent = result.message || 'Account created successfully! Redirecting to login...';
                    successDiv.style.display = 'block';
                    form.reset();
                    setTimeout(() => { window.location.href = '../app/list.php'; }, 2000);
                } else {
                    errorDiv.textContent = result.error || 'Registration failed. Please try again.';
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                errorDiv.textContent = 'A network error occurred. Please check your connection.';
                errorDiv.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = 'Create Account';
                loading.style.display = 'none';
            }
        });
        
        document.getElementById('username').focus();
    </script>
</body>
</html>