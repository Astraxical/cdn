<?php
// File upload/download functionality
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/storage.php';

/**
 * Upload a file to specified storage
 * @param array $file File array from $_FILES
 * @param string $storageType Type of storage ('local', 'mongodb', 'git')
 * @return array Result of the upload operation
 */
function uploadFile($file, $storageType = 'local') {
    if (!validateFile($file)) {
        return [
            'success' => false,
            'error' => 'Invalid file'
        ];
    }
    
    $originalName = basename($file['name']);
    $contentType = mime_content_type($file['tmp_name']) ?: 'application/octet-stream';
    $content = file_get_contents($file['tmp_name']);
    
    switch ($storageType) {
        case 'mongodb':
            $storage = new MongoFileStorage();
            return $storage->storeFile($originalName, $content, $contentType);
            
        case 'git':
            $storage = new GitFileStorage();
            return $storage->addFile($originalName, $content);
            
        case 'local':
        default:
            $uploadDir = UPLOAD_DIR;
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $hashedName = hashFilename($originalName);
            $targetPath = $uploadDir . $hashedName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Store reference in MySQL
                $pdo = connectDatabase();
                if ($pdo) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO files (file_id, filename, storage_type) VALUES (?, ?, 'local')");
                        $stmt->execute([$hashedName, $originalName]);
                    } catch (PDOException $e) {
                        error_log("Failed to store file reference in MySQL: " . $e->getMessage());
                    }
                }
                
                return [
                    'success' => true,
                    'filename' => $originalName,
                    'hashedName' => $hashedName,
                    'url' => 'http://' . $_SERVER['HTTP_HOST'] . '/' . $targetPath
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to move uploaded file'
                ];
            }
    }
}

/**
 * Download a file from specified storage
 * @param string $fileId ID of the file to download
 * @param string $storageType Type of storage ('local', 'mongodb', 'git')
 * @return array File data or error
 */
function downloadFile($fileId, $storageType = 'local') {
    switch ($storageType) {
        case 'mongodb':
            $storage = new MongoFileStorage();
            return $storage->retrieveFile($fileId);
            
        case 'git':
            $storage = new GitFileStorage();
            return $storage->getFile($fileId);
            
        case 'local':
        default:
            $filePath = UPLOAD_DIR . $fileId;
            
            if (file_exists($filePath)) {
                return [
                    'success' => true,
                    'filename' => basename($filePath),
                    'content' => file_get_contents($filePath),
                    'contentType' => mime_content_type($filePath) ?: 'application/octet-stream',
                    'size' => filesize($filePath)
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'File not found'
                ];
            }
    }
}

/**
 * List all files from all storage types
 * @return array Combined list of files from all storage types
 */
function listAllFiles() {
    $files = [];
    
    // Get local files
    $uploadDir = UPLOAD_DIR;
    if (is_dir($uploadDir)) {
        $fileList = scandir($uploadDir);
        foreach ($fileList as $file) {
            if ($file !== '.' && $file !== '..') {
                $filePath = $uploadDir . $file;
                if (is_file($filePath)) {
                    $files[] = [
                        'id' => $file,
                        'filename' => $file,
                        'size' => filesize($filePath),
                        'date' => date('Y-m-d H:i:s', filemtime($filePath)),
                        'url' => 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000') . '/' . $filePath,
                        'storage_type' => 'local'
                    ];
                }
            }
        }
    }
    
    // Get MongoDB files
    try {
        $mongoStorage = new MongoFileStorage();
        $mongoResult = $mongoStorage->listFiles();
        if ($mongoResult['success']) {
            foreach ($mongoResult['files'] as $file) {
                $files[] = [
                    'id' => $file['id'],
                    'filename' => $file['filename'],
                    'size' => $file['size'],
                    'date' => $file['uploadedAt'] ? $file['uploadedAt']->toDateTime()->format('Y-m-d H:i:s') : null,
                    'storage_type' => 'mongodb'
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching MongoDB files: " . $e->getMessage());
    }
    
    // Get Git files
    try {
        $gitStorage = new GitFileStorage();
        $gitResult = $gitStorage->listFiles();
        if ($gitResult['success']) {
            foreach ($gitResult['files'] as $file) {
                $files[] = [
                    'id' => md5($file['filename']),
                    'filename' => $file['filename'],
                    'size' => $file['size'],
                    'date' => null, // Git doesn't provide easy file date access without additional commands
                    'storage_type' => 'git'
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching Git files: " . $e->getMessage());
    }
    
    return $files;
}

/**
 * Delete a file from specified storage
 * @param string $fileId ID of the file to delete
 * @param string $storageType Type of storage ('local', 'mongodb', 'git')
 * @return array Result of the delete operation
 */
function deleteFile($fileId, $storageType = 'local') {
    switch ($storageType) {
        case 'mongodb':
            $storage = new MongoFileStorage();
            return $storage->deleteFile($fileId);
            
        case 'git':
            // For Git, we'll just remove the file from the repo
            $repoPath = GIT_REPO_PATH;
            $filePath = $repoPath . '/' . $fileId;
            
            if (file_exists($filePath)) {
                // Remove from filesystem
                $unlinkResult = unlink($filePath);
                
                if ($unlinkResult) {
                    // Remove from git tracking
                    $gitResult = executeGitCommand("git rm " . escapeshellarg($fileId));
                    
                    if ($gitResult['return_code'] === 0) {
                        // Commit the removal
                        $commitResult = executeGitCommand("git commit -m \"Remove file: {$fileId}\"");
                        
                        if ($commitResult['return_code'] === 0 || strpos(implode(' ', $commitResult['output']), 'nothing to commit') !== false) {
                            // Remove reference from MySQL
                            $pdo = connectDatabase();
                            if ($pdo) {
                                try {
                                    $stmt = $pdo->prepare("DELETE FROM files WHERE filename = ? AND storage_type = 'git'");
                                    $stmt->execute([$fileId]);
                                } catch (PDOException $e) {
                                    error_log("Failed to remove file reference from MySQL: " . $e->getMessage());
                                }
                            }
                            
                            return [
                                'success' => true,
                                'message' => 'File removed from Git repository'
                            ];
                        } else {
                            return [
                                'success' => false,
                                'error' => 'Git commit failed after removal: ' . implode(' ', $commitResult['output'])
                            ];
                        }
                    } else {
                        return [
                            'success' => false,
                            'error' => 'Git rm failed: ' . implode(' ', $gitResult['output'])
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'error' => 'Failed to remove file from filesystem'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'File not found in Git repository'
                ];
            }
            
        case 'local':
        default:
            $filePath = UPLOAD_DIR . $fileId;
            
            if (file_exists($filePath)) {
                $result = unlink($filePath);
                
                if ($result) {
                    // Remove reference from MySQL
                    $pdo = connectDatabase();
                    if ($pdo) {
                        try {
                            $stmt = $pdo->prepare("DELETE FROM files WHERE file_id = ? AND storage_type = 'local'");
                            $stmt->execute([$fileId]);
                        } catch (PDOException $e) {
                            error_log("Failed to remove file reference from MySQL: " . $e->getMessage());
                        }
                    }
                    
                    return [
                        'success' => true,
                        'message' => 'File deleted successfully'
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'Failed to delete file'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'File not found'
                ];
            }
    }
}
?>