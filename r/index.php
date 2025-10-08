<?php
// Redirect handler for shortened links
require_once 'config.php';
require_once 'includes/functions.php';

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

// Connect to database and look up the short code
$pdo = connectDatabase();

if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT long_url FROM links WHERE short_code = ?");
        $stmt->execute([$shortCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Redirect to the original URL and increment click count
            $longUrl = $result['long_url'];
            
            // Update click count
            $updateStmt = $pdo->prepare("UPDATE links SET clicks = clicks + 1, last_access = NOW() WHERE short_code = ?");
            $updateStmt->execute([$shortCode]);
            
            header("Location: $longUrl");
            exit;
        } else {
            http_response_code(404);
            echo "Short link not found";
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo "Database error: " . $e->getMessage();
    }
} else {
    http_response_code(500);
    echo "Database connection failed";
}
?>