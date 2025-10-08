<?php
// Hourly Sync Script for Data Branch
// This script should be run via cron every hour to sync data to the data branch

require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/file_operations.php';

echo "Starting hourly sync at " . date('Y-m-d H:i:s') . "\n";

// Check if sync is needed
if (isSyncNeeded()) {
    $result = performDataBranchSync();
    
    if ($result['success']) {
        echo "Sync completed successfully: " . $result['message'] . "\n";
    } else {
        echo "Sync failed: " . ($result['error'] ?? $result['message']) . "\n";
    }
} else {
    echo "Sync not needed yet. Last sync was less than " . SYNC_INTERVAL . " seconds ago.\n";
}

echo "Hourly sync completed at " . date('Y-m-d H:i:s') . "\n";
?>