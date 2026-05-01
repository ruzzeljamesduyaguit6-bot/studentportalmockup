<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

echo "=== LOGIN TEST ===\n\n";

// Step 1: Find user
echo "Step 1: Finding admin user...\n";
$user = User::where('email', 'admin@example.com')->first();

if (!$user) {
    echo "❌ FAILED: Admin user not found\n";
    exit(1);
}

echo "✓ Found admin user: " . $user->name . "\n";
echo "  ID: " . $user->id . "\n";
echo "  Email: " . $user->email . "\n\n";

// Step 2: Test password verification
echo "Step 2: Testing password verification...\n";
$password = 'password';
$isMatch = Hash::check($password, $user->password);

echo "  Test password: " . $password . "\n";
echo "  Password hash (first 30 chars): " . substr($user->password, 0, 30) . "...\n";
echo "  Hash matches: " . ($isMatch ? "✓ YES" : "❌ NO") . "\n\n";

if (!$isMatch) {
    echo "❌ FAILED: Password does not match!\n";
    exit(1);
}

// Step 3: Generate token
echo "Step 3: Generating API token...\n";
$token = Str::random(80);
$hashedToken = hash('sha256', $token);

echo "  Raw token (first 30 chars): " . substr($token, 0, 30) . "...\n";
echo "  Hashed token (first 30 chars): " . substr($hashedToken, 0, 30) . "...\n\n";

// Step 4: Save token
echo "Step 4: Saving token to database...\n";
$user->update(['api_token' => $hashedToken]);
echo "  ✓ Token saved\n\n";

// Step 5: Verify token was saved
echo "Step 5: Verifying token was saved...\n";
$updatedUser = User::find($user->id);

if ($updatedUser->api_token !== $hashedToken) {
    echo "❌ FAILED: Token not saved correctly\n";
    exit(1);
}

echo "  ✓ Token verified in database\n\n";

// Step 6: Test token retrieval
echo "Step 6: Testing token retrieval...\n";
$retrievedUser = User::where('api_token', $hashedToken)->first();

if (!$retrievedUser) {
    echo "❌ FAILED: Could not retrieve user by token hash\n";
    exit(1);
}

echo "  ✓ User retrieved by token hash\n";
echo "  Retrieved user: " . $retrievedUser->name . "\n\n";

echo "=== LOGIN TEST SUCCESSFUL ===\n";
echo "\nLogin JSON response would be:\n";
echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'token' => $token,  // This is the raw token sent to client
    'user' => $retrievedUser->toArray(),
], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
