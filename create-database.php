<?php

// Create MySQL database for role-based system
$host = 'localhost';
$port = 3307;
$username = 'root';
$password = '';
$database = 'role_based_system';

// Connect to MySQL without specifying a database
$conn = new mysqli($host, $username, $password, '', $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "✓ Database '$database' created successfully or already exists.\n";
} else {
    echo "✗ Error creating database: " . $conn->error . "\n";
}

$conn->close();
