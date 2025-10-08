<?php
// Simple test to see what the request URI looks like
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Path info: " . (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : 'Not set') . "\n";
echo "Query String: " . (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : 'Not set') . "\n";

// Now try the API path parsing logic
$request = $_SERVER['REQUEST_URI'];
$basePath = '/api.php';
if (strpos($request, $basePath) === 0) {
    $path = substr($request, strlen($basePath));
    echo "Base path matched. Path extracted: '$path'\n";
} else {
    $basePath = '/api';
    if (strpos($request, $basePath) === 0) {
        $path = substr($request, strlen($basePath));
        echo "Alternative base path matched. Path extracted: '$path'\n";
    } else {
        echo "No base path matched. Using full request: '$request'\n";
        $path = $request;
    }
}
echo "Final path: '$path'\n";
?>