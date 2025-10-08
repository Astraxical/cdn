<?php
// Minimal API test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting API test...\n";

// Test config
echo "Testing config...\n";
require_once 'config.php';
echo "Config loaded. DATA_DIR: " . DATA_DIR . "\n";

// Test basic functions
echo "Testing functions...\n";
require_once 'includes/functions.php';
echo "Functions loaded\n";

// Test file operations
echo "Testing file operations...\n";
require_once 'includes/file_operations.php';
echo "File operations loaded\n";

echo "All includes successful\n";

// Just return a simple response to verify the basic functionality works
header('Content-Type: application/json');
echo json_encode(['status' => 'API test successful']);
?>