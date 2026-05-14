<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Portal</title>
    <x-vite-assets :assets="['resources/css/app.css', 'resources/css/login.css', 'resources/js/app.js']" />
    {{-- Inline critical CSS: ensures the page is usable even if the external stylesheet fails to load --}}
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px}
        .login-container{background:#fff;border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,.2);overflow:hidden;width:100%;max-width:450px}
        .login-header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:40px 20px;text-align:center;color:#fff}
        .login-header h1{font-size:1.8rem;margin-bottom:8px}
        .login-body{padding:30px}
        .form-group{margin-bottom:20px}
        .form-group label{display:block;margin-bottom:6px;font-weight:600;color:#333;font-size:.9rem}
        .form-group input{width:100%;padding:12px 15px;border:2px solid #e1e5e9;border-radius:8px;font-size:1rem;transition:border-color .3s}
        .form-group input:focus{outline:none;border-color:#667eea}
        .login-btn{width:100%;padding:14px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;margin-top:10px}
        .login-btn:hover{opacity:.9}
        .general-error{display:none;background:#fee;border:1px solid #fcc;color:#c33;padding:10px 15px;border-radius:8px;margin-bottom:15px;font-size:.9rem}
        .success-message{display:none;background:#efe;border:1px solid #cfc;color:#363;padding:10px 15px;border-radius:8px;margin-bottom:15px;font-size:.9rem}
        .forgot-password-row{text-align:right;margin-bottom:10px}
        .forgot-password-link{color:#667eea;font-size:.85rem;text-decoration:none}
        .remember-me{display:flex;align-items:center;gap:8px;margin-bottom:10px;font-size:.9rem}
        .login-footer{padding:15px;text-align:center;background:#f8f9fa;font-size:.85rem;color:#666}
        .login-footer a{color:#667eea;text-decoration:none}
        .error-message{color:#e53e3e;font-size:.8rem;margin-top:4px;display:none}
        .form-group.error input{border-color:#e53e3e}
        .form-group.error .error-message{display:block}
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Student Portal</h1>
            <p>Welcome Back</p>
        </div>

        <div class="login-body">
            <div class="success-message" id="successMessage" @if(session('status')) style="display: block;" @endif>
                {{ session('status') }}
            </div>
            <div class="general-error" id="generalError"></div>

            
            <form id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    <div class="error-message"></div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <div class="error-message"></div>
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>

                <div class="forgot-password-row">
                    <a href="{{ route('password.request') }}" class="forgot-password-link">Forgot Password?</a>
                </div>

                <button type="submit" class="login-btn">Login</button>
            </form>
        </div>

        <div class="login-footer">
            <p>Alrights Reserved | <a href="#">Need help?</a></p>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const successMessage = document.getElementById('successMessage');
        const generalError = document.getElementById('generalError');

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Clear previous messages
            successMessage.style.display = 'none';
            generalError.style.display = 'none';

            // Reset form errors
            document.querySelectorAll('.form-group').forEach(group => {
                group.classList.remove('error');
            });

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const loginBtn = loginForm.querySelector('.login-btn');

            // Show loading state
            loginBtn.classList.add('loading');
            loginBtn.textContent = 'Logging in...';

            try {
                console.log('[login] Sending request to /api/auth/login for:', email);
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password
                    })
                });

                console.log('[login] Response status:', response.status, response.statusText);
                const rawText = await response.text();
                console.log('[login] Raw response body:', rawText);

                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (parseErr) {
                    console.error('[login] Failed to parse JSON response:', parseErr);
                    generalError.textContent = '✕ Server returned an unexpected response. Check the console for details.';
                    generalError.style.display = 'block';
                    return;
                }
                console.log('[login] Parsed response data:', data);

                if (response.ok) {
                    // Store token
                    localStorage.setItem('api_token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user));

                    // Show success message
                    successMessage.textContent = '✓ Login successful! Redirecting...';
                    successMessage.style.display = 'block';

                    // Redirect after 1.5 seconds
                    setTimeout(() => {
                        if (data.user?.user_type === 'professor') {
                            window.location.href = '/professors/dashboard';
                        } else {
                            window.location.href = '/dashboard';
                        }
                    }, 1500);
                } else {
                    // Show field errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const fieldElement = document.getElementById(field);
                            if (fieldElement) {
                                const fieldGroup = fieldElement.closest('.form-group');
                                fieldGroup.classList.add('error');
                                fieldGroup.querySelector('.error-message').textContent = data.errors[field][0];
                            }
                        });
                    }

                    // Show general error
                    if (data.message) {
                        generalError.textContent = '✕ ' + data.message;
                        generalError.style.display = 'block';
                    } else {
                        generalError.textContent = '✕ Login failed. Please try again.';
                        generalError.style.display = 'block';
                    }
                }
            } catch (error) {
                console.error('Login error:', error);
                generalError.textContent = '✕ Network error. Please try again.';
                generalError.style.display = 'block';
            } finally {
                // Reset button
                loginBtn.classList.remove('loading');
                loginBtn.textContent = 'Login';
            }
        });

        // Show demo credentials section for 5 seconds on page load
        setTimeout(() => {
            const demoCreds = document.querySelector('.demo-credentials');
            if (demoCreds) {
                demoCreds.style.opacity = '0.5';
            }
        }, 5000);
    </script>
</body>
</html>
