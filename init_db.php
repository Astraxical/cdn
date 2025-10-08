<?php
// Database initialization script
require_once 'config.php';
require_once 'includes/functions.php';

echo "Initializing database...\n";

$pdo = connectDatabase();

if (!$pdo) {
    die("Failed to connect to database\n");
}

try {
    // Create links table for URL shortening
    $sql = "CREATE TABLE IF NOT EXISTS links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        short_code VARCHAR(20) UNIQUE NOT NULL,
        long_url TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_access TIMESTAMP NULL,
        clicks INT DEFAULT 0
    )";
    
    $pdo->exec($sql);
    echo "Links table created successfully\n";
    
    // Create files table to track stored files
    $sql = "CREATE TABLE IF NOT EXISTS files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_id VARCHAR(24) NOT NULL, -- For MongoDB object IDs
        filename VARCHAR(255) NOT NULL,
        storage_type ENUM('local', 'mongodb', 'git') DEFAULT 'local',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "Files table created successfully\n";
    
    echo "Database initialization completed!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>