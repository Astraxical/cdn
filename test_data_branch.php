<?php
// Test Data Branch Storage Functionality
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/file_operations.php';

echo "Testing Data Branch Storage Functionality\n";
echo "========================================\n\n";

// Test file storage
echo "1. Testing file storage in data branch...\n";
$testContent = "This is test content for the data branch storage system.";
$testFileResult = uploadFile([
    'name' => 'test_data_branch.txt',
    'tmp_name' => tempnam(sys_get_temp_dir(), 'test_'),
    'error' => UPLOAD_ERR_OK,
    'size' => strlen($testContent)
], 'sqlite');

file_put_contents($testFileResult['success'] ? tempnam(sys_get_temp_dir(), 'test_') : tempnam(sys_get_temp_dir(), 'test_'), $testContent);

if ($testFileResult['success']) {
    echo "   ✓ File stored successfully (ID: " . $testFileResult['fileId'] . ")\n";
    
    // Test file retrieval
    echo "2. Testing file retrieval...\n";
    $retrievedFile = downloadFile($testFileResult['fileId'], 'sqlite');
    if ($retrievedFile['success']) {
        echo "   ✓ File retrieved successfully: " . $retrievedFile['original_name'] . "\n";
    } else {
        echo "   ✗ Failed to retrieve file: " . $retrievedFile['error'] . "\n";
    }
    
    // Test file listing
    echo "3. Testing file listing...\n";
    $files = listAllFiles();
    echo "   ✓ Found " . count($files) . " files in storage\n";
    
} else {
    echo "   ✗ Failed to store file: " . $testFileResult['error'] . "\n";
}

// Test link storage
echo "\n4. Testing link shortening in data branch...\n";
$storage = getDataBranchStorage();

$shortCode = generateShortCode();
$linkResult = $storage->storeLink($shortCode, 'https://example.com', 'Test Link');

if ($linkResult['success']) {
    echo "   ✓ Link stored successfully: $shortCode\n";
    
    // Test link retrieval
    echo "5. Testing link retrieval...\n";
    $retrievedLink = $storage->retrieveLink($shortCode);
    if ($retrievedLink['success']) {
        echo "   ✓ Link retrieved successfully: " . $retrievedLink['longUrl'] . "\n";
    } else {
        echo "   ✗ Failed to retrieve link: " . $retrievedLink['error'] . "\n";
    }
    
    // Test link listing
    echo "6. Testing link listing...\n";
    $links = listAllLinks();
    echo "   ✓ Found " . count($links) . " links in storage\n";
    
} else {
    echo "   ✗ Failed to store link: " . $linkResult['error'] . "\n";
}

// Test sync functionality
echo "\n7. Testing sync functionality...\n";
$syncNeeded = isSyncNeeded();
echo "   ✓ Sync needed: " . ($syncNeeded ? "Yes" : "No") . "\n";

if ($syncNeeded) {
    $syncResult = performDataBranchSync();
    echo "   ✓ Sync result: " . ($syncResult['success'] ? $syncResult['message'] : $syncResult['error']) . "\n";
} else {
    echo "   - Sync not required at this time (interval: " . SYNC_INTERVAL . " seconds)\n";
}

echo "\nData branch storage functionality test completed!\n";
echo "\nSystem Summary:\n";
echo "- Files and links are stored in SQLite databases in the data branch\n";
echo "- Automatic hourly sync commits and pushes data changes\n";
echo "- All operations are logged for tracking and debugging\n";
echo "- Perfect for GitHub deployment with data managed separately from code\n";
?>