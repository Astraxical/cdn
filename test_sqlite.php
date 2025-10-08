<?php
// Test SQLite functionality
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/storage.php';
require_once 'includes/file_operations.php';

echo "Testing SQLite Storage Functionality\n";
echo "==================================\n\n";

// Test SQLite connection
echo "1. Testing SQLite connection... ";
$pdo = connectSqlite();
if ($pdo) {
    echo "OK\n";
    
    // Test creating a file in SQLite
    echo "2. Testing file storage in SQLite... ";
    $sqliteStorage = new SqliteFileStorage();
    $result = $sqliteStorage->storeFile('test_sqlite.txt', 'This is test content for SQLite storage', 'text/plain');
    
    if ($result['success']) {
        echo "OK (File ID: " . $result['fileId'] . ")\n";
        
        // Test retrieving the file
        echo "3. Testing file retrieval from SQLite... ";
        $retrieved = $sqliteStorage->retrieveFile($result['fileId']);
        if ($retrieved['success']) {
            echo "OK (Retrieved file: " . $retrieved['filename'] . ")\n";
            
            // Test listing files
            echo "4. Testing file listing from SQLite... ";
            $files = $sqliteStorage->listFiles();
            if ($files['success']) {
                echo "OK (Found " . count($files['files']) . " files)\n";
                
                // Test deleting the file
                echo "5. Testing file deletion from SQLite... ";
                $deleteResult = $sqliteStorage->deleteFile($result['fileId']);
                if ($deleteResult['success']) {
                    echo "OK\n";
                } else {
                    echo "FAILED: " . $deleteResult['error'] . "\n";
                }
            } else {
                echo "FAILED: " . $files['error'] . "\n";
            }
        } else {
            echo "FAILED: " . $retrieved['error'] . "\n";
        }
    } else {
        echo "FAILED: " . $result['error'] . "\n";
    }
} else {
    echo "FAILED\n";
}

echo "\nSQLite functionality test completed!\n";
?>