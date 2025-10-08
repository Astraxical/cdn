<?php
// Simple test script
echo "Testing API components...\n";

require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/file_operations.php';

echo "All includes loaded successfully\n";

// Test if the data branch storage works
$storage = getDataBranchStorage();
if ($storage) {
    echo "Data branch storage loaded successfully\n";
    
    // Try to get links
    $links = $storage->listLinks();
    echo "Links result: ";
    var_dump($links['success']);
} else {
    echo "Failed to load data branch storage\n";
}
?>