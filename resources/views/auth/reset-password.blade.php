<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Role Based System</title>
    <x-vite-assets :assets="['resources/css/app.css', 'resources/css/login.css', 'resources/js/app.js']" />
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px}
        .login-container{background:#fff;border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,.2);overflow:hidden;width:100%;max-width:450px}
        .login-header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:40px 20px;text-align:center;color:#fff}
        .login-header h1{font-size:1.8rem;margin-bottom:8px}
        .login-body{padding:30px}
        .form-group{margin-bottom:20px}
        .form-group label{display:block;margin-bottom:6px;font-weight:600;color:#333;font-size:.9rem}
        .form-group input{width:100%;padding:12px 15px;border:2px solid #e1e5e9;border-radius:8px;font-size:1rem}
        .form-group input:focus{outline:none;border-color:#667eea}
        .login-btn{width:100%;padding:14px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;margin-top:10px}
        .auth-helper{background:#f0f4ff;border-left:4px solid #667eea;padding:10px 15px;margin-bottom:20px;font-size:.9rem;color:#444;border-radius:0 6px 6px 0}
        .form-note{font-size:.8rem;color:#888;margin-top:4px}
        .error-message{color:#e53e3e;font-size:.8rem;margin-top:4px}
        .login-footer{padding:15px;text-align:center;background:#f8f9fa;font-size:.85rem;color:#666}
        .login-footer a{color:#667eea;text-decoration:none}
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Set New Password</h1>
            <p>Create a secure password for your account</p>
        </div>

        <div class="login-body">
            <div class="auth-helper">
                Enter your email and your new password to complete the reset process.
            </div>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group @error('email') error @enderror">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $email) }}"
                        placeholder="Enter your email"
                        required
                        autofocus
                    >
                    <div class="error-message" @error('email') style="display:block;" @enderror>
                        @error('email') {{ $message }} @enderror
                    </div>
                </div>

                <div class="form-group @error('password') error @enderror">
                    <label for="password">New Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your new password"
                        required
                    >
                    <div class="error-message" @error('password') style="display:block;" @enderror>
                        @error('password') {{ $message }} @enderror
                    </div>
                    <div class="form-note">Minimum 8 characters.</div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Confirm your new password"
                        required
                    >
                </div>

                <button type="submit" class="login-btn">Update Password</button>
            </form>
        </div>

        <div class="login-footer">
            <p><a href="{{ route('login') }}">Back to login</a></p>
        </div>
    </div>
</body>
</html>
