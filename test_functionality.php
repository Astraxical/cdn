<?php
// Test script to verify basic functionality
echo "File Hosting Service - Basic Functionality Test\n";
echo "==============================================\n\n";

// Test configuration loading
echo "1. Loading configuration... ";
require_once 'config.php';
echo "OK\n";

// Test if required directories exist
echo "2. Checking directories... ";
if (!is_dir(UPLOAD_DIR)) {
    echo "Creating upload directory... ";
    mkdir(UPLOAD_DIR, 0755, true);
}
if (!is_dir(GIT_REPO_PATH)) {
    echo "Creating Git repo directory... ";
    mkdir(GIT_REPO_PATH, 0755, true);
}
echo "OK\n";

// Test functions loading
echo "3. Loading functions... ";
require_once 'includes/functions.php';
echo "OK\n";

// Test file operations loading
echo "4. Loading file operations... ";
require_once 'includes/file_operations.php';
echo "OK\n";

// Test basic file upload functionality
echo "5. Testing file upload functions... ";
$testFile = UPLOAD_DIR . 'test.txt';
file_put_contents($testFile, 'This is a test file for the hosting service.');
if (file_exists($testFile)) {
    echo "OK (created test file)\n";
} else {
    echo "FAILED\n";
}

// Test basic file listing
echo "6. Testing file listing... ";
$files = listAllFiles();
echo "Found " . count($files) . " files\n";

echo "\nBasic functionality test completed!\n";

// Show project structure
echo "\nProject Structure:\n";
echo "- Main pages: index.php, upload.php, files.php, shortener.php, api.php\n";
echo "- Configuration: config.php\n";
echo "- Includes: functions.php, file_operations.php, storage.php\n";
echo "- Assets: CSS in assets/css/\n";
echo "- Uploaded files stored in: " . UPLOAD_DIR . "\n";
echo "- Git repository: " . GIT_REPO_PATH . "\n";
echo "- GitHub Actions workflow: .github/workflows/deploy.yml\n";
echo "- API endpoints available at: /api/\n";
echo "- Link shortener redirects at: /r/\n";
?>