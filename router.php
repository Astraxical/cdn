<?php
// Router for PHP built-in server
// This handles the routes that would normally be handled by .htaccess

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = ltrim($request, '/'); // Remove leading slash

// Handle API routes
if (strpos($request, 'api.php') === 0) {
    // Extract the path after api.php
    $path = substr($request, strlen('api.php'));
    
    // Create a simulated API path by replacing 'api.php' with '/api'
    $simulated_request_uri = '/api' . $path;
    
    // Store the original REQUEST_URI for reference
    $original_request_uri = $_SERVER['REQUEST_URI'];
    
    // Temporarily modify REQUEST_URI to match what the API expects
    $_SERVER['REQUEST_URI'] = $simulated_request_uri;
    
    require 'api.php';
    return; // Important: return to prevent further execution
}

// Handle redirect routes
if (strpos($request, 'r/') === 0) {
    require 'r/index.php';
    return;
}

// Handle specific PHP files
$phpFiles = ['upload.php', 'files.php', 'shortener.php', 'info.php', 'setup_git.php', 
             'setup_sqlite.php', 'setup_data_branch.php', 'sync_data_branch.php',
             'test_functionality.php', 'test_sqlite.php', 'test_data_branch.php', 
             'final_test.php', 'final_data_branch_test.php', 'test_api_components.php'];

foreach ($phpFiles as $file) {
    if ($request === $file) {
        require $file;
        return;
    }
    
    // Handle files with query parameters
    if (strpos($request, $file) === 0) {
        require $file;
        return;
    }
}

// For 404 errors, redirect to index.html
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/index.html')) {
    return false; // This tells PHP built-in server to serve the static file
} else {
    // If index.html doesn't exist, show a simple message
    http_response_code(404);
    echo "Page not found. Redirecting to home...";
    header("Refresh: 2; url=index.html");
}
?>