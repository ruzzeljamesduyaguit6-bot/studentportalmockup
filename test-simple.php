<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $admin = \App\Models\User::where('email', 'admin@example.com')->first();
    
    if (!$admin) {
        echo "Admin user not found!\n";
        exit;
    }
    
    echo "Admin:\n";
    var_dump($admin);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
