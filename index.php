<?php
// Router for PHP built-in server

// Get the request URI
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = ltrim($request, '/'); // Remove leading slash

// If no specific request, redirect to index.html
if (empty($request) || $request === '' || $request === 'index.php') {
    header("Location: index.html");
    exit;
}

// Handle API routes
if (strpos($request, 'api.php') === 0) {
    // Extract the path after api.php
    $path = substr($request, strlen('api.php'));
    
    // Store the original REQUEST_URI for reference and modify to allow proper API routing
    $original_request_uri = $_SERVER['REQUEST_URI'];
    $_SERVER['REQUEST_URI'] = '/api.php' . $path;
    
    require 'api.php';
    exit;
}

// Handle redirect routes
if (strpos($request, 'r/') === 0) {
    require 'r/index.php';
    exit;
}

// Handle other PHP files as needed
$phpFiles = ['upload.php', 'files.php', 'shortener.php', 'info.php', 'setup_git.php', 
             'setup_sqlite.php', 'setup_data_branch.php', 'sync_data_branch.php',
             'test_functionality.php', 'test_sqlite.php', 'test_data_branch.php', 
             'final_test.php', 'final_data_branch_test.php', 'test_api_components.php'];

foreach ($phpFiles as $file) {
    if ($request === $file || strpos($request, $file . '/') === 0) {
        require $file;
        exit;
    }
}

// For any other request, serve index.html (404 handling)
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/index.html')) {
    header("HTTP/1.0 200 OK");
    readfile('index.html');
} else {
    http_response_code(404);
    echo "Page not found";
}
?>