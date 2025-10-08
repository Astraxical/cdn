<?php
// File Hosting Website - Main Page
require_once 'config.php';
require_once 'includes/functions.php';

// Initialize database
initializeDatabase();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = 'uploads/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = basename($_FILES['file']['name']);
    $targetPath = $uploadDir . $fileName;
    
    // Move uploaded file to target directory
    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        $message = "File uploaded successfully: " . $fileName;
    } else {
        $message = "Error uploading file.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Hosting Service</title>
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
        <section class="hero">
            <h2>Host and Share Your Files</h2>
            <p>Upload files to our secure platform and get shareable links instantly.</p>
            
            <div class="upload-section">
                <form action="index.php" method="post" enctype="multipart/form-data">
                    <label for="file">Choose file to upload:</label>
                    <input type="file" name="file" id="file" required>
                    <input type="submit" value="Upload File" name="submit">
                </form>
                
                <?php if (isset($message)): ?>
                    <div class="message"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
            </div>
        </section>
        
        <section class="features">
            <h3>Features</h3>
            <div class="feature-grid">
                <div class="feature">
                    <h4>Git Integration</h4>
                    <p>Connect to Git repositories for file management</p>
                </div>
                <div class="feature">
                    <h4>MongoDB Storage</h4>
                    <p>Secure and scalable file storage</p>
                </div>
                <div class="feature">
                    <h4>API Access</h4>
                    <p>Programmatic access to your files</p>
                </div>
                <div class="feature">
                    <h4>Link Shortening</h4>
                    <p>Shorten and track your shared links</p>
                </div>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2025 File Hosting Service. All rights reserved.</p>
    </footer>
</body>
</html>