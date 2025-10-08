<?php
// Link Shortener Page
require_once 'config.php';
require_once 'includes/functions.php';

$message = '';
$error = '';
$shortenedUrl = '';

// Handle link shortening
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['long_url'])) {
    $longUrl = filter_var($_POST['long_url'], FILTER_SANITIZE_URL);
    
    if (filter_var($longUrl, FILTER_VALIDATE_URL)) {
        $pdo = connectDatabase();
        
        if ($pdo) {
            try {
                // Generate short code
                $shortCode = generateShortCode();
                
                // Check if short code already exists
                $stmt = $pdo->prepare("SELECT short_code FROM links WHERE short_code = ?");
                $stmt->execute([$shortCode]);
                
                // Regenerate if exists (unlikely but possible)
                while ($stmt->rowCount() > 0) {
                    $shortCode = generateShortCode();
                    $stmt = $pdo->prepare("SELECT short_code FROM links WHERE short_code = ?");
                    $stmt->execute([$shortCode]);
                }
                
                // Insert the new link
                $stmt = $pdo->prepare("INSERT INTO links (short_code, long_url, created_at) VALUES (?, ?, NOW())");
                $result = $stmt->execute([$shortCode, $longUrl]);
                
                if ($result) {
                    $shortenedUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/r/' . $shortCode;
                    $message = "Link shortened successfully!";
                } else {
                    $error = "Error creating shortened link.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Could not connect to database.";
        }
    } else {
        $error = "Invalid URL provided.";
    }
}

// If URL is passed via query parameter (for direct shortening from files page)
if (isset($_GET['url'])) {
    $passedUrl = filter_var($_GET['url'], FILTER_SANITIZE_URL);
    if (filter_var($passedUrl, FILTER_VALIDATE_URL)) {
        $_POST['long_url'] = $passedUrl;
        $longUrl = $passedUrl;
        
        $pdo = connectDatabase();
        
        if ($pdo) {
            try {
                // Generate short code
                $shortCode = generateShortCode();
                
                // Check if short code already exists
                $stmt = $pdo->prepare("SELECT short_code FROM links WHERE short_code = ?");
                $stmt->execute([$shortCode]);
                
                // Regenerate if exists
                while ($stmt->rowCount() > 0) {
                    $shortCode = generateShortCode();
                    $stmt = $pdo->prepare("SELECT short_code FROM links WHERE short_code = ?");
                    $stmt->execute([$shortCode]);
                }
                
                // Insert the new link
                $stmt = $pdo->prepare("INSERT INTO links (short_code, long_url, created_at) VALUES (?, ?, NOW())");
                $result = $stmt->execute([$shortCode, $longUrl]);
                
                if ($result) {
                    $shortenedUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/r/' . $shortCode;
                    $message = "Link shortened successfully!";
                } else {
                    $error = "Error creating shortened link.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Could not connect to database.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Shortener - File Hosting Service</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <h1>File Hosting Service</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="upload.php">Upload</a></li>
                <li><a href="files.php">Files</a></li>
                <li><a href="shortener.php">Link Shortener</a></li>
                <li><a href="api.php">API</a></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <section class="shortener-container">
            <h2>Link Shortener</h2>
            
            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($shortenedUrl): ?>
                <div class="result-container">
                    <p>Your shortened link:</p>
                    <div class="shortened-link">
                        <a href="<?php echo $shortenedUrl; ?>" target="_blank"><?php echo $shortenedUrl; ?></a>
                        <button class="copy-btn" onclick="copyToClipboard('<?php echo $shortenedUrl; ?>')">Copy</button>
                    </div>
                </div>
            <?php endif; ?>
            
            <form action="shortener.php" method="post">
                <div class="form-group">
                    <label for="long_url">Enter URL to shorten:</label>
                    <input 
                        type="url" 
                        id="long_url" 
                        name="long_url" 
                        placeholder="https://example.com/very/long/url" 
                        value="<?php echo isset($_POST['long_url']) ? htmlspecialchars($_POST['long_url']) : ''; ?>"
                        required
                    >
                </div>
                
                <div class="form-actions">
                    <input type="submit" value="Shorten Link" name="submit">
                </div>
            </form>
            
            <div class="how-it-works">
                <h3>How it works</h3>
                <p>Enter any long URL and our service will generate a short, easy-to-share link. The shortened link will redirect to the original URL when clicked.</p>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2025 File Hosting Service. All rights reserved.</p>
    </footer>
    
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Link copied to clipboard!');
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        }
    </script>
</body>
</html>