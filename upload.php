<?php
// File Upload Page
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/file_operations.php';

$message = '';
$error = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $storageType = $_POST['storage_type'] ?? DEFAULT_STORAGE_TYPE; // Default to Git for GitHub
    
    $uploadResult = uploadFile($_FILES['file'], $storageType);
    
    if ($uploadResult['success']) {
        $message = "File uploaded successfully: " . $uploadResult['filename'];
        $fileUrl = $uploadResult['url'] ?? null;
    } else {
        $error = "Error uploading file: " . $uploadResult['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Files - File Hosting Service</title>
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
        <section class="upload-container">
            <h2>Upload File</h2>
            
            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
                <?php if (isset($fileUrl)): ?>
                    <div class="file-url">
                        <p>Your file is available at: <a href="<?php echo $fileUrl; ?>" target="_blank"><?php echo $fileUrl; ?></a></p>
                        <p>Direct link: <code><?php echo $fileUrl; ?></code></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form action="upload.php" method="post" enctype="multipart/form-data">
                <div class="file-input-container">
                    <label for="file">Choose file to upload:</label>
                    <input type="file" name="file" id="file" required>
                </div>
                
                <div class="form-group">
                    <label for="storage_type">Storage Type:</label>
                    <select name="storage_type" id="storage_type">
                        <option value="git" selected>Git Repository (Recommended for GitHub)</option>
                        <option value="sqlite">SQLite Database (Separate data branch)</option>
                        <option value="local">Local Storage</option>
                        <option value="mongodb">MongoDB (Requires credentials)</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <input type="submit" value="Upload File" name="submit">
                </div>
            </form>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2025 File Hosting Service. All rights reserved.</p>
    </footer>
</body>
</html>