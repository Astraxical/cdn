<?php
// File Deletion Page
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/file_operations.php';

$fileId = $_GET['id'] ?? null;
$storageType = $_GET['type'] ?? 'local';

if (!$fileId) {
    header('Location: files.php');
    exit;
}

$result = deleteFile($fileId, $storageType);

if ($result['success']) {
    $message = "File deleted successfully";
} else {
    $error = "Error deleting file: " . $result['error'];
}

// Redirect back to files page after a short delay
header('Refresh: 2; URL=files.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete File - File Hosting Service</title>
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
        <section class="delete-container" style="text-align: center; padding: 2rem;">
            <h2>Delete File</h2>
            
            <?php if (isset($message)): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <p>You will be redirected back to the files page shortly...</p>
            <a href="files.php" class="btn btn-primary">Go to Files Page Now</a>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2025 File Hosting Service. All rights reserved.</p>
    </footer>
</body>
</html>