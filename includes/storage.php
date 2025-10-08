<?php
// MongoDB integration for File Hosting Service
require_once 'config.php';
require_once 'includes/functions.php';

class MongoFileStorage {
    private $collection;
    
    public function __construct() {
        $db = connectMongoDB();
        if ($db) {
            $this->collection = $db->selectCollection('files');
        } else {
            // Don't throw exception here, just set collection to null
            $this->collection = null;
            error_log('Failed to connect to MongoDB');
        }
    }
    
    /**
     * Store a file in MongoDB
     * @param string $filename Name of the file
     * @param string $content File content
     * @param string $contentType MIME type of the file
     * @return array Result with file ID or error
     */
    public function storeFile($filename, $content, $contentType = 'application/octet-stream') {
        if (!$this->collection) {
            return [
                'success' => false,
                'error' => 'MongoDB connection not available'
            ];
        }
        
        try {
            $fileData = [
                'filename' => $filename,
                'content' => base64_encode($content), // Store as base64
                'contentType' => $contentType,
                'uploadedAt' => new MongoDB\BSON\UTCDateTime(time() * 1000),
                'size' => strlen($content)
            ];
            
            $result = $this->collection->insertOne($fileData);
            
            // Also store reference in MySQL
            $pdo = connectDatabase();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO files (file_id, filename, storage_type) VALUES (?, ?, 'mongodb')");
                    $stmt->execute([(string)$result->getInsertedId(), $filename]);
                } catch (PDOException $e) {
                    error_log("Failed to store file reference in MySQL: " . $e->getMessage());
                }
            }
            
            return [
                'success' => true,
                'fileId' => (string)$result->getInsertedId(),
                'filename' => $filename
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Retrieve a file from MongoDB
     * @param string $fileId ID of the file to retrieve
     * @return array File data or error
     */
    public function retrieveFile($fileId) {
        if (!$this->collection) {
            return [
                'success' => false,
                'error' => 'MongoDB connection not available'
            ];
        }
        
        try {
            $result = $this->collection->findOne(['_id' => new MongoDB\BSON\ObjectId($fileId)]);
            
            if ($result) {
                return [
                    'success' => true,
                    'filename' => $result['filename'],
                    'content' => base64_decode($result['content']),
                    'contentType' => $result['contentType'] ?? 'application/octet-stream',
                    'size' => $result['size'] ?? null,
                    'uploadedAt' => $result['uploadedAt'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'File not found'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * List all files in MongoDB
     * @return array List of files
     */
    public function listFiles() {
        if (!$this->collection) {
            return [
                'success' => false,
                'error' => 'MongoDB connection not available'
            ];
        }
        
        try {
            $cursor = $this->collection->find([], [
                'projection' => [
                    'filename' => 1,
                    'size' => 1,
                    'uploadedAt' => 1,
                    '_id' => 1
                ]
            ]);
            
            $files = [];
            foreach ($cursor as $file) {
                $files[] = [
                    'id' => (string)$file['_id'],
                    'filename' => $file['filename'],
                    'size' => $file['size'],
                    'uploadedAt' => $file['uploadedAt']
                ];
            }
            
            return [
                'success' => true,
                'files' => $files
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete a file from MongoDB
     * @param string $fileId ID of the file to delete
     * @return array Result of the operation
     */
    public function deleteFile($fileId) {
        if (!$this->collection) {
            return [
                'success' => false,
                'error' => 'MongoDB connection not available'
            ];
        }
        
        try {
            $result = $this->collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($fileId)]);
            
            // Also remove reference from MySQL
            $pdo = connectDatabase();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM files WHERE file_id = ?");
                    $stmt->execute([$fileId]);
                } catch (PDOException $e) {
                    error_log("Failed to remove file reference from MySQL: " . $e->getMessage());
                }
            }
            
            if ($result->getDeletedCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'File deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'File not found'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// Git integration class
class GitFileStorage {
    private $repoPath;
    
    public function __construct($repoPath = null) {
        $this->repoPath = $repoPath ?: GIT_REPO_PATH;
        
        // Initialize git repo if not already initialized
        if (!file_exists($this->repoPath . '/.git')) {
            $this->initGitRepo();
        }
    }
    
    /**
     * Initialize a git repository
     */
    private function initGitRepo() {
        if (!is_dir($this->repoPath)) {
            mkdir($this->repoPath, 0755, true);
        }
        
        // Initialize git repo
        $result = executeGitCommand("git init");
        
        // Set default user config for git commits
        executeGitCommand("git config user.name 'File Hosting Service'");
        executeGitCommand("git config user.email 'no-reply@filehosting.com'");
        
        // Create a .gitignore file
        file_put_contents($this->repoPath . '/.gitignore', "*.tmp\n*.temp\n.DS_Store\nThumbs.db");
    }
    
    /**
     * Add a file to the Git repository
     * @param string $filename Name of the file
     * @param string $content File content
     * @return array Result of the operation
     */
    public function addFile($filename, $content) {
        $filePath = $this->repoPath . '/' . $filename;
        
        // Write content to file
        $result = file_put_contents($filePath, $content);
        
        if ($result === false) {
            return [
                'success' => false,
                'error' => 'Failed to write file to Git repository'
            ];
        }
        
        // Add file to Git staging
        $addResult = executeGitCommand("git add {$filename}");
        
        if ($addResult['return_code'] !== 0) {
            return [
                'success' => false,
                'error' => 'Git add failed: ' . implode(' ', $addResult['output'])
            ];
        }
        
        // Commit the file
        $commitResult = executeGitCommand("git commit -m \"Add file: {$filename}\"");
        
        // Only return error if git commit failed due to no changes (which may happen if a file was already added)
        if ($commitResult['return_code'] !== 0) {
            if (strpos(implode(' ', $commitResult['output']), 'nothing to commit') === false) {
                return [
                    'success' => false,
                    'error' => 'Git commit failed: ' . implode(' ', $commitResult['output'])
                ];
            }
        }
        
        // Store reference in MySQL
        $pdo = connectDatabase();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO files (file_id, filename, storage_type) VALUES (?, ?, 'git')");
                $stmt->execute([md5($filename), $filename]); // Using MD5 as the ID for git files
            } catch (PDOException $e) {
                error_log("Failed to store file reference in MySQL: " . $e->getMessage());
            }
        }
        
        return [
            'success' => true,
            'message' => 'File added to Git repository',
            'filePath' => $filePath,
            'gitOutput' => [
                'add' => $addResult['output'],
                'commit' => $commitResult['output']
            ]
        ];
    }
    
    /**
     * Get file from Git repository
     * @param string $filename Name of the file to retrieve
     * @return array File content or error
     */
    public function getFile($filename) {
        $filePath = $this->repoPath . '/' . $filename;
        
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => 'File does not exist in Git repository'
            ];
        }
        
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            return [
                'success' => false,
                'error' => 'Failed to read file from Git repository'
            ];
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'content' => $content,
            'size' => filesize($filePath)
        ];
    }
    
    /**
     * List files in Git repository
     * @return array List of files
     */
    public function listFiles() {
        $listResult = executeGitCommand("git ls-files");
        
        if ($listResult['return_code'] !== 0) {
            return [
                'success' => false,
                'error' => 'Failed to list Git files: ' . implode(' ', $listResult['output'])
            ];
        }
        
        $files = [];
        foreach ($listResult['output'] as $file) {
            if (file_exists($this->repoPath . '/' . $file)) {
                $files[] = [
                    'filename' => $file,
                    'size' => filesize($this->repoPath . '/' . $file),
                    'path' => $this->repoPath . '/' . $file
                ];
            }
        }
        
        return [
            'success' => true,
            'files' => $files
        ];
    }
}
?>