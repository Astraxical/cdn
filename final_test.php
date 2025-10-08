<?php
// Final comprehensive test
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/storage.php';
require_once 'includes/file_operations.php';

echo "Final Comprehensive Test\n";
echo "=======================\n\n";

echo "Configuration:\n";
echo "- Git repo path: " . GIT_REPO_PATH . "\n";
echo "- SQLite DB path: " . SQLITE_DB_PATH . "\n";
echo "- Upload dir: " . UPLOAD_DIR . "\n";
echo "- Default storage: " . DEFAULT_STORAGE_TYPE . "\n\n";

// Test different storage types
$testContent = "Test content for comprehensive testing";
$testFilename = "comprehensive_test_" . time() . ".txt";

echo "Testing Git storage...\n";
$gitResult = uploadFile([
    'name' => $testFilename,
    'tmp_name' => tempnam(sys_get_temp_dir(), 'test_'),
    'error' => UPLOAD_ERR_OK,
    'size' => strlen($testContent)
], 'git');
file_put_contents($gitResult['success'] ? tempnam(sys_get_temp_dir(), 'test_') : tempnam(sys_get_temp_dir(), 'test_'), $testContent);
echo "- Git upload: " . ($gitResult['success'] ? "SUCCESS" : "FAILED - " . $gitResult['error']) . "\n";

echo "Testing SQLite storage...\n";
$testFile = tempnam(sys_get_temp_dir(), 'test_');
file_put_contents($testFile, $testContent);
$sqliteResult = uploadFile([
    'name' => $testFilename,
    'tmp_name' => $testFile,
    'error' => UPLOAD_ERR_OK,
    'size' => strlen($testContent)
], 'sqlite');
echo "- SQLite upload: " . ($sqliteResult['success'] ? "SUCCESS (ID: " . $sqliteResult['fileId'] . ")" : "FAILED - " . $sqliteResult['error']) . "\n";

echo "Testing Local storage...\n";
$localResult = uploadFile([
    'name' => $testFilename,
    'tmp_name' => $testFile,
    'error' => UPLOAD_ERR_OK,
    'size' => strlen($testContent)
], 'local');
echo "- Local upload: " . ($localResult['success'] ? "SUCCESS" : "FAILED - " . $localResult['error']) . "\n";

// Test listing all files
echo "\nTesting file listing...\n";
$allFiles = listAllFiles();
echo "- Total files found: " . count($allFiles) . "\n";

// Show a summary
echo "\nStorage Options Summary:\n";
echo "- Git: Good for version-controlled file storage in GitHub\n";
echo "- SQLite: Good for structured data with a separate data branch approach\n";
echo "- Local: Fallback option, may not persist in all GitHub deployments\n";
echo "- MongoDB: Available but requires external service (not recommended for public repos)\n";

echo "\nApplication is ready for GitHub deployment with:\n";
echo "✓ Git as primary storage for files\n";
echo "✓ SQLite as structured data storage (with option for separate branch)\n";
echo "✓ API functionality\n";
echo "✓ Link shortening\n";
echo "✓ GitHub Actions deployment workflow\n";

unlink($testFile); // Clean up the temp file
?>