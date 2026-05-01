<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Role Based System</title>
    @vite(['resources/css/app.css', 'resources/css/login.css', 'resources/js/app.js'])
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Forgot Password</h1>
            <p>Reset your account access</p>
        </div>

        <div class="login-body">
            @if (session('status'))
                <div class="success-message" style="display: block;">{{ session('status') }}</div>
            @endif

            <div class="auth-helper">
                Enter your email address. We will send a secure reset link through Brevo.
            </div>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="form-group @error('email') error @enderror">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter your email"
                        required
                        autofocus
                    >
                    <div class="error-message" @error('email') style="display:block;" @enderror>
                        @error('email') {{ $message }} @enderror
                    </div>
                    <div class="form-note">Use the same email you use to log in.</div>
                </div>

                <button type="submit" class="login-btn">Send Reset Link</button>
            </form>
        </div>

        <div class="login-footer">
            <p><a href="{{ route('login') }}">Back to login</a></p>
        </div>
    </div>
</body>
</html>
