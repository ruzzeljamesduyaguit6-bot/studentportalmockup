<?php

namespace App\Http\Controllers;

use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ProfileController extends Controller
{
    private function getAuthenticatedUser(Request $request): ?User
    {
        $token = $request->bearerToken();

        if (!$token) {
            return null;
        }

        $hashedToken = hash('sha256', $token);

        return User::where('api_token', $hashedToken)->first();
    }

    private function unauthorizedResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 401);
    }

    public function getProfile(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $pendingRequest = DB::table('profile_change_requests')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        return response()->json([
            'success' => true,
            'user' => $this->mapUser($user),
            'progress' => $this->calculateProfileCompletion($user),
            'pendingProfileRequest' => $this->mapProfileRequest($pendingRequest),
        ]);
    }

    public function getProfileOptions(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        return response()->json([
            'success' => true,
            'designations' => DB::table('designations')->orderBy('name')->pluck('name')->toArray(),
            'departments' => DB::table('departments')->orderBy('name')->pluck('name')->toArray(),
            'courses' => DB::table('courses')->orderBy('name')->pluck('name')->toArray(),
            'subjects' => DB::table('subjects')->orderBy('name')->pluck('name')->toArray(),
            'yearLevels' => ['1st Year', '2nd Year', '3rd Year', '4th Year'],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'birthday' => ['nullable', 'date', 'before:today'],
            'contact' => ['nullable', 'string', 'max:50'],
        ]);

        $immediateFields = ['name', 'email', 'birthday', 'contact'];

        $updates = [];
        foreach ($immediateFields as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] !== $user->{$field}) {
                $updates[$field] = $validated[$field];
            }
        }

        $messages = [];

        if (!empty($updates)) {
            if (array_key_exists('email', $updates) && $updates['email'] !== $user->email) {
                $updates['email_verified_at'] = null;
                $messages[] = 'Email changed. Please verify your new email address.';
            }

            $user->update($updates);
            $messages[] = 'Basic profile details updated.';
        }

        if (empty($updates)) {
            $messages[] = 'No profile changes detected.';
        }

        $freshUser = User::find($user->id);

        $pendingRequest = DB::table('profile_change_requests')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        return response()->json([
            'success' => true,
            'message' => implode(' ', $messages),
            'user' => $this->mapUser($freshUser),
            'progress' => $this->calculateProfileCompletion($freshUser),
            'pendingProfileRequest' => $this->mapProfileRequest($pendingRequest),
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        $this->notifyUsers(
            [$user->id],
            'Password Updated',
            'Your password was successfully changed.',
            'security'
        );

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    public function sendEmailVerificationCode(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        if (!empty($user->email_verified_at)) {
            return response()->json([
                'success' => true,
                'message' => 'Email is already verified.',
            ]);
        }

        $activeRecord = DB::table('email_verification_codes')
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if ($activeRecord && !empty($activeRecord->locked_until) && now()->lt($activeRecord->locked_until)) {
            $retryAfter = now()->diffInSeconds($activeRecord->locked_until, false);

            return response()->json([
                'success' => false,
                'message' => 'Too many attempts. Please wait before requesting another code.',
                'retry_after_seconds' => max(1, (int) $retryAfter),
            ], 429);
        }

        $carryAttempts = 0;
        if ($activeRecord && now()->lt($activeRecord->expires_at)) {
            $carryAttempts = max(0, (int) ($activeRecord->attempts ?? 0));
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = hash('sha256', $code);

        DB::table('email_verification_codes')
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->delete();

        DB::table('email_verification_codes')->insert([
            'user_id' => $user->id,
            'code_hash' => $codeHash,
            'attempts' => $carryAttempts,
            'expires_at' => now()->addMinutes(10),
            'locked_until' => null,
            'verified_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $this->sendEmailViaBrevo(
                recipientEmail: $user->email,
                recipientName: $user->name,
                subject: 'Student Portal Email Verification Code',
                htmlContent: '<p>Hello '.e($user->name).',</p>'
                    . '<p>Your Student Portal verification code is:</p>'
                    . '<p style="font-size:28px;font-weight:700;letter-spacing:4px;">'.e($code).'</p>'
                    . '<p>This code expires in 10 minutes.</p>'
            );
        } catch (\Throwable $exception) {
            Log::error('Failed to send email verification code.', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send verification code right now. Please try again later.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to your email.',
            'remaining_attempts' => max(0, 3 - $carryAttempts),
        ]);
    }

    public function verifyEmailCode(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $record = DB::table('email_verification_codes')
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'No active verification code found. Please request a new code.',
            ], 422);
        }

        if (now()->greaterThan($record->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code expired. Please request a new code.',
            ], 422);
        }

        if (!empty($record->locked_until) && now()->lt($record->locked_until)) {
            $retryAfter = now()->diffInSeconds($record->locked_until, false);

            return response()->json([
                'success' => false,
                'message' => 'Too many invalid attempts. Please wait before trying again.',
                'retry_after_seconds' => max(1, (int) $retryAfter),
            ], 429);
        }

        $submittedHash = hash('sha256', $validated['code']);
        if (!hash_equals($record->code_hash, $submittedHash)) {
            $attempts = ((int) ($record->attempts ?? 0)) + 1;

            if ($attempts >= 3) {
                $lockSeconds = 10;

                DB::table('email_verification_codes')
                    ->where('id', $record->id)
                    ->update([
                        'attempts' => 0,
                        'locked_until' => now()->addSeconds($lockSeconds),
                        'updated_at' => now(),
                    ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Too many invalid attempts. Wait 10 seconds before trying again.',
                    'retry_after_seconds' => $lockSeconds,
                ], 429);
            }

            $remainingAttempts = 3 - $attempts;

            DB::table('email_verification_codes')
                ->where('id', $record->id)
                ->update([
                    'attempts' => $attempts,
                    'locked_until' => null,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code.',
                'remaining_attempts' => $remainingAttempts,
            ], 422);
        }

        DB::table('email_verification_codes')
            ->where('id', $record->id)
            ->update([
                'verified_at' => now(),
                'attempts' => 0,
                'locked_until' => null,
                'updated_at' => now(),
            ]);

        $user->update([
            'email_verified_at' => now(),
        ]);

        $this->notifyUsers(
            [$user->id],
            'Email Verified',
            'Your email address was verified successfully.',
            'verification'
        );

        $freshUser = User::find($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
            'user' => $this->mapUser($freshUser),
            'progress' => $this->calculateProfileCompletion($freshUser),
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:3072'],
        ]);

        $uploaded = Cloudinary::uploadApi()->upload(
            $validated['photo']->getRealPath(),
            ['folder' => 'profile-photos', 'resource_type' => 'image']
        );

        $url = (string) ($uploaded['secure_url'] ?? '');
        if ($url === '') {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload profile photo.',
            ], 500);
        }

        $user->update([
            'profile_photo_url' => $url,
        ]);

        $freshUser = User::find($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Profile photo updated successfully.',
            'photo_url' => $url,
            'user' => $this->mapUser($freshUser),
            'progress' => $this->calculateProfileCompletion($freshUser),
        ]);
    }

    public function getNotifications(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $notifications = DB::table('user_notifications')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(120)
            ->get()
            ->map(function ($row) {
                $row->data = $row->data ? json_decode($row->data, true) : null;
                return $row;
            })
            ->values();

        $pendingRequests = collect();
        if ($user->user_type === 'admin') {
            $pendingRequests = DB::table('profile_change_requests as pcr')
                ->join('users as u', 'u.id', '=', 'pcr.user_id')
                ->where('pcr.status', 'pending')
                ->orderByDesc('pcr.id')
                ->select(
                    'pcr.id',
                    'pcr.user_id',
                    'pcr.requested_changes',
                    'pcr.created_at',
                    'u.name as user_name',
                    'u.user_code',
                    'u.user_type'
                )
                ->get()
                ->map(function ($row) {
                    $row->requested_changes = json_decode($row->requested_changes, true) ?? [];
                    return $row;
                })
                ->values();
        }

        $unreadCount = DB::table('user_notifications')
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'pendingProfileRequests' => $pendingRequests,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markNotificationRead(Request $request, int $id)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $notification = DB::table('user_notifications')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        DB::table('user_notifications')
            ->where('id', $id)
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    public function approveProfileRequest(Request $request, int $id)
    {
        $admin = $this->getAuthenticatedUser($request);
        if (!$admin) {
            return $this->unauthorizedResponse();
        }

        if ($admin->user_type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Admin access required',
            ], 403);
        }

        $profileRequest = DB::table('profile_change_requests')->where('id', $id)->first();
        if (!$profileRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Profile change request not found.',
            ], 404);
        }

        if ($profileRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be approved.',
            ], 422);
        }

        $targetUser = User::find($profileRequest->user_id);
        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'Target user not found.',
            ], 404);
        }

        $changes = json_decode($profileRequest->requested_changes, true) ?? [];
        $allowedFields = ['designation', 'department', 'course', 'subject', 'year_level'];

        $sanitized = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $changes)) {
                $sanitized[$field] = $this->normalizeNullableString($changes[$field]);
            }
        }

        $targetUser->update($sanitized);

        DB::table('profile_change_requests')
            ->where('id', $profileRequest->id)
            ->update([
                'status' => 'approved',
                'approved_by' => $admin->id,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

        $this->notifyUsers(
            [$targetUser->id],
            'Profile Update Approved',
            'Your pending profile dropdown updates were approved and applied.',
            'approval-approved'
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile change request approved.',
        ]);
    }

    public function rejectProfileRequest(Request $request, int $id)
    {
        $admin = $this->getAuthenticatedUser($request);
        if (!$admin) {
            return $this->unauthorizedResponse();
        }

        if ($admin->user_type !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Admin access required',
            ], 403);
        }

        $profileRequest = DB::table('profile_change_requests')->where('id', $id)->first();
        if (!$profileRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Profile change request not found.',
            ], 404);
        }

        if ($profileRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be rejected.',
            ], 422);
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::table('profile_change_requests')
            ->where('id', $profileRequest->id)
            ->update([
                'status' => 'rejected',
                'approved_by' => $admin->id,
                'reviewed_at' => now(),
                'reviewer_note' => $validated['note'] ?? null,
                'updated_at' => now(),
            ]);

        $message = 'Your pending profile dropdown updates were rejected.';
        if (!empty($validated['note'])) {
            $message .= ' Note: '.trim($validated['note']);
        }

        $this->notifyUsers(
            [$profileRequest->user_id],
            'Profile Update Rejected',
            $message,
            'approval-rejected'
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile change request rejected.',
        ]);
    }

    private function mapUser(?User $user): array
    {
        if (!$user) {
            return [];
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'birthday' => optional($user->birthday)->format('Y-m-d') ?? ($user->birthday ? (string) $user->birthday : null),
            'contact' => $user->contact,
            'designation' => $user->designation,
            'department' => $user->department,
            'course' => $user->course,
            'subject' => $user->subject,
            'year_level' => $user->year_level,
            'user_type' => $user->user_type,
            'user_code' => $user->user_code,
            'profile_photo_url' => $user->profile_photo_url,
            'email_verified_at' => $user->email_verified_at,
            'email_verified' => !empty($user->email_verified_at),
            'created_at' => $user->created_at,
        ];
    }

    private function mapProfileRequest($profileRequest): ?array
    {
        if (!$profileRequest) {
            return null;
        }

        return [
            'id' => $profileRequest->id,
            'status' => $profileRequest->status,
            'requested_changes' => json_decode($profileRequest->requested_changes, true) ?? [],
            'created_at' => $profileRequest->created_at,
            'reviewed_at' => $profileRequest->reviewed_at,
            'reviewer_note' => $profileRequest->reviewer_note,
        ];
    }

    private function calculateProfileCompletion(User $user): array
    {
        $items = [
            ['key' => 'name', 'label' => 'Full Name', 'done' => !empty(trim((string) $user->name))],
            ['key' => 'email', 'label' => 'Email', 'done' => !empty(trim((string) $user->email))],
            ['key' => 'birthday', 'label' => 'Birthday', 'done' => !empty($user->birthday)],
            ['key' => 'contact', 'label' => 'Contact Number', 'done' => !empty(trim((string) $user->contact))],
            ['key' => 'email_verification', 'label' => 'Email Verification', 'done' => !empty($user->email_verified_at)],
            ['key' => 'photo', 'label' => 'Profile Photo', 'done' => !empty(trim((string) $user->profile_photo_url))],
        ];

        if ($user->user_type === 'student') {
            $items[] = ['key' => 'course', 'label' => 'Course', 'done' => !empty(trim((string) $user->course))];
            $items[] = ['key' => 'year_level', 'label' => 'Year Level', 'done' => !empty(trim((string) $user->year_level))];
        }

        if ($user->user_type === 'professor') {
            $items[] = ['key' => 'designation', 'label' => 'Designation', 'done' => !empty(trim((string) $user->designation))];
            $items[] = ['key' => 'department', 'label' => 'Department', 'done' => !empty(trim((string) $user->department))];
        }

        $total = count($items);
        $completed = count(array_filter($items, fn ($item) => $item['done']));
        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $percent,
            'items' => $items,
        ];
    }

    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function notifyAdminsForProfileRequest(User $requester, array $changes): void
    {
        $adminIds = User::query()
            ->where('user_type', 'admin')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($adminIds)) {
            return;
        }

        $changedFields = implode(', ', array_keys($changes));
        $title = 'Profile Approval Required';
        $message = $requester->name.' requested updates for: '.$changedFields.'.';

        $this->notifyUsers($adminIds, $title, $message, 'approval-review', [
            'requester_id' => $requester->id,
            'requester_name' => $requester->name,
            'changes' => $changes,
        ]);
    }

    private function notifyUsers(array $userIds, string $title, string $message, string $type = 'info', ?array $data = null): void
    {
        $cleanIds = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($cleanIds)) {
            return;
        }

        $rows = array_map(function ($userId) use ($title, $message, $type, $data) {
            return [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'data' => $data ? json_encode($data) : null,
                'is_read' => false,
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $cleanIds);

        DB::table('user_notifications')->insert($rows);
    }

    private function sendEmailViaBrevo(string $recipientEmail, string $recipientName, string $subject, string $htmlContent): void
    {
        $brevoApiKey = (string) config('services.brevo.api_key');

        if ($brevoApiKey === '') {
            throw new \RuntimeException('BREVO_API_KEY is not configured.');
        }

        $fromAddress = (string) config('mail.from.address', 'no-reply@example.com');
        $fromName = (string) config('mail.from.name', 'Student Portal');

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

        if (!$response->successful()) {
            throw new \RuntimeException('Brevo API request failed with status '.$response->status());
        }
    }
}
