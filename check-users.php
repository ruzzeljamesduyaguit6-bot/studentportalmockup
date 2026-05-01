<?php
// Direct database query to check users
$host = 'localhost';
$port = 3307;
$username = 'root';
$password = '';
$database = 'role_based_system';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    
    $stmt = $pdo->query("SELECT id, name, email, user_type FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Users in Database:\n";
    echo str_repeat("=", 75) . "\n";
    printf("%-3s | %-20s | %-25s | %-10s\n", "ID", "Name", "Email", "Type");
    echo str_repeat("=", 75) . "\n";
    
    foreach ($users as $user) {
        printf("%-3d | %-20s | %-25s | %-10s\n", 
            $user['id'], 
            substr($user['name'], 0, 20),
            $user['email'], 
            $user['user_type']);
    }
    
    echo str_repeat("=", 75) . "\n";
    echo"\n✓ Total Users: " . count($users) . "\n";
    echo "\n📝 You can login with:\n";
    echo "  • admin@example.com / password (admin account)\n";
    echo "  • user@example.com / password (regular user account)\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}

