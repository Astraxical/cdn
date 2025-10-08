<?php
// Files Listing Page
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/file_operations.php';

// Fetch files from all storage types
$files = listAllFiles();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Files - File Hosting Service</title>
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
        <section class="files-container">
            <h2>Your Files</h2>
            
            <?php if (count($files) > 0): ?>
                <div class="files-list">
                    <table>
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Date</th>
                                <th>Storage</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($file['filename'] ?? $file['name']); ?></td>
                                    <td><?php echo formatFileSize($file['size']); ?></td>
                                    <td><?php echo $file['date']; ?></td>
                                    <td><?php echo strtoupper($file['storage_type']); ?></td>
                                    <td>
                                        <?php if (isset($file['url'])): ?>
                                            <a href="<?php echo $file['url']; ?>" target="_blank" class="btn btn-view">View</a>
                                            <a href="shortener.php?url=<?php echo urlencode($file['url']); ?>" class="btn btn-shorten">Shorten Link</a>
                                        <?php else: ?>
                                            <span>N/A</span>
                                        <?php endif; ?>
                                        <a href="delete_file.php?id=<?php echo urlencode($file['id']); ?>&type=<?php echo urlencode($file['storage_type']); ?>" 
                                           class="btn btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete this file?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-files">
                    <p>No files uploaded yet.</p>
                    <a href="upload.php" class="btn btn-primary">Upload a File</a>
                </div>
            <?php endif; ?>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2025 File Hosting Service. All rights reserved.</p>
    </footer>
</body>
</html>

<?php
// Helper function to format file size
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>