<?php
// Debug version of API to see what's happening
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];

// Parse URL to get API path - debug version
$basePath = '/api.php';
if (strpos($request, $basePath) === 0) {
    $path = substr($request, strlen($basePath));
    echo json_encode(['debug' => "Matched api.php, path: $path", 'request' => $request]);
} else {
    // For compatibility with routing systems that use /api/ format
    $basePath = '/api';
    if (strpos($request, $basePath) === 0) {
        $path = substr($request, strlen($basePath));
        echo json_encode(['debug' => "Matched /api, path: $path", 'request' => $request]);
    } else {
        echo json_encode(['debug' => "No match", 'request' => $request]);
    }
}

// Don't continue to the full API for now
exit;
?>