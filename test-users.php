<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$users = \App\Models\User::all(['id', 'name', 'email', 'user_type']);
echo "Total Users: " . $users->count() . "\n";
foreach ($users as $user) {
    echo sprintf("ID: %d | Name: %s | Email: %s | Type: %s\n", $user->id, $user->name, $user->email, $user->user_type);
}
