<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Role Based System</title>
    <x-vite-assets :assets="['resources/css/app.css', 'resources/css/login.css', 'resources/js/app.js']" />
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
