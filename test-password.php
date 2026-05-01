<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$admin = User::where('email', 'admin@example.com')->first();

if (!$admin) {
    echo "Admin user not found!\n";
    exit(1);
}

echo "Admin Found:\n";
echo "  Name: " . $admin->name . "\n";
echo "  Email: " . $admin->email . "\n";
echo "  Stored Password Hash: " . substr($admin->password, 0, 20) . "...\n";

$testPassword = 'password';
$matches = Hash::check($testPassword, $admin->password);

echo "\nPassword Test:\n";
echo "  Test Password: " . $testPassword . "\n";
echo "  Matches: " . ($matches ? 'YES' : 'NO') . "\n";

if (!$matches) {
    // Try to hash the test password and compare
    $testHash = Hash::make($testPassword);
    echo "\n  Test Hash: " . substr($testHash, 0, 20) . "...\n";
    echo "  Stored Hash: " . substr($admin->password, 0, 20) . "...\n";
}
