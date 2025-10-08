<?php
// Redirect handler for shortened links with Data Branch Storage
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/file_operations.php';

// Get the short code from the URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));

// The short code should be the first segment after the base path
$shortCode = isset($segments[0]) ? $segments[0] : null;

if (!$shortCode) {
    http_response_code(400);
    echo "Invalid short code";
    exit;
}

// Look up the short code in data branch storage
$link = retrieveLink($shortCode);

if ($link['success']) {
    // Redirect to the original URL (click count is already incremented in retrieveLink)
    $longUrl = $link['longUrl'];
    header("Location: $longUrl");
    exit;
} else {
    http_response_code(404);
    echo "Short link not found: " . $link['error'];
}
?>