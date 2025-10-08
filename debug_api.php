<?php
// Debug API to see what's happening with path parsing
header('Content-Type: application/json');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];

echo json_encode([
    'method' => $method,
    'request_uri' => $request,
    'parsed_path' => $request, // Just return as is for now
    'query_string' => $_SERVER['QUERY_STRING'] ?? '',
    'full_request' => $request
]);
?>