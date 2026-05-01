<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $status = Password::sendResetLink(
                ['email' => $validated['email']],
                function ($user, string $token): void {
                    $recipientEmail = method_exists($user, 'getEmailForPasswordReset')
                        ? $user->getEmailForPasswordReset()
                        : (string) data_get($user, 'email', '');

                    if ($recipientEmail === '') {
                        throw new \RuntimeException('Recipient email is missing for password reset.');
                    }

                    $recipientName = (string) data_get($user, 'name', 'User');
                    $resetUrl = url('/reset-password/'.$token).'?email='.urlencode($recipientEmail);

                    $this->sendResetEmailViaBrevo(
                        recipientEmail: $recipientEmail,
                        recipientName: $recipientName,
                        resetUrl: $resetUrl
                    );
                }
            );
        } catch (\Throwable $exception) {
            Log::error('Failed to send reset email through Brevo.', [
                'email' => $validated['email'],
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'email' => 'Unable to send reset email right now. Please try again later.',
                ]);
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors([
                'email' => __($status),
            ]);
        }

        return back()->with('status', 'If an account exists for that email, a reset link has been sent.');
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $validated['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    private function sendResetEmailViaBrevo(string $recipientEmail, string $recipientName, string $resetUrl): void
    {
        $brevoApiKey = (string) config('services.brevo.api_key');

        if ($brevoApiKey === '') {
            throw new \RuntimeException('BREVO_API_KEY is not configured.');
        }

        $appName = (string) config('app.name', 'Laravel');
        $fromAddress = (string) config('mail.from.address', 'no-reply@example.com');
        $fromName = (string) config('mail.from.name', $appName);
        $subject = 'Student Portal Reset Password';

        $safeName = e($recipientName);
        $safeAppName = e($appName);
        $safeResetUrl = e($resetUrl);

        $htmlContent = "<p>Hello {$safeName},</p>"
            . "<p>We received a request to reset your {$safeAppName} password.</p>"
            . "<p><a href=\"{$safeResetUrl}\" style=\"display:inline-block;padding:12px 20px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;\">Reset Password</a></p>"
            . "<p>If you did not request this, you can safely ignore this email.</p>";

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $brevoApiKey,
            'content-type' => 'application/json',
        ])->timeout(20)->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => $fromName,
                'email' => $fromAddress,
            ],
            'to' => [
                [
                    'email' => $recipientEmail,
                    'name' => $recipientName,
                ],
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Brevo API request failed with status '.$response->status());
        }
    }
}
