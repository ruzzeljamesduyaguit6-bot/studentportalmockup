<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Portal</title>
    <x-vite-assets :assets="['resources/css/app.css', 'resources/css/login.css', 'resources/js/app.js']" />
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

                const data = await response.json();
                console.log('Login response:', data);
                console.log('Response status:', response.status);

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
