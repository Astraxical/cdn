<?php
// Data Branch Storage Implementation
require_once 'config.php';
require_once 'includes/functions.php';

class DataBranchStorage {
    private $filesDb;
    private $linksDb;
    private $activityDb;
    
    public function __construct() {
        $this->filesDb = $this->connectFilesDb();
        $this->linksDb = $this->connectLinksDb();
        $this->activityDb = $this->connectActivityDb();
        
        if ($this->filesDb) $this->initFilesDb();
        if ($this->linksDb) $this->initLinksDb();
        if ($this->activityDb) $this->initActivityDb();
    }
    
    /**
     * Connect to files database
     */
    private function connectFilesDb() {
        try {
            $pdo = new PDO("sqlite:" . FILES_DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            error_log("Files SQLite connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Connect to links database
     */
    private function connectLinksDb() {
        try {
            $pdo = new PDO("sqlite:" . LINKS_DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            error_log("Links SQLite connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Connect to activity database
     */
    private function connectActivityDb() {
        try {
            $pdo = new PDO("sqlite:" . ACTIVITY_DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            error_log("Activity SQLite connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Initialize files database
     */
    private function initFilesDb() {
        try {
            $this->filesDb->exec("CREATE TABLE IF NOT EXISTS files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename TEXT NOT NULL,
                content BLOB,
                content_type TEXT,
                size INTEGER,
                uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                original_name TEXT
            )");
        } catch (PDOException $e) {
            error_log("Files database initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Initialize links database
     */
    private function initLinksDb() {
        try {
            $this->linksDb->exec("CREATE TABLE IF NOT EXISTS links (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                short_code TEXT UNIQUE NOT NULL,
                long_url TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_access DATETIME,
                clicks INTEGER DEFAULT 0,
                title TEXT
            )");
        } catch (PDOException $e) {
            error_log("Links database initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Initialize activity database
     */
    private function initActivityDb() {
        try {
            $this->activityDb->exec("CREATE TABLE IF NOT EXISTS activity (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                action TEXT NOT NULL,
                entity_type TEXT,
                entity_id INTEGER,
                details TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        } catch (PDOException $e) {
            error_log("Activity database initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Store a file in the data branch SQLite
     */
    public function storeFile($filename, $content, $contentType = 'application/octet-stream', $originalName = null) {
        if (!$this->filesDb) {
            return [
                'success' => false,
                'error' => 'Files database connection not available'
            ];
        }
        
        try {
            $stmt = $this->filesDb->prepare("INSERT INTO files (filename, content, content_type, size, original_name) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([$filename, $content, $contentType, strlen($content), $originalName]);
            
            if ($result) {
                $fileId = $this->filesDb->lastInsertId();
                
                // Log activity
                $this->logActivity('file_upload', 'file', $fileId, "Uploaded file: $originalName");
                
                // Schedule sync
                $this->scheduleSync();
                
                return [
                    'success' => true,
                    'fileId' => $fileId,
                    'filename' => $filename
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to store file in SQLite'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Retrieve a file from the data branch SQLite
     */
    public function retrieveFile($fileId) {
        if (!$this->filesDb) {
            return [
                'success' => false,
                'error' => 'Files database connection not available'
            ];
        }
        
        try {
            $stmt = $this->filesDb->prepare("SELECT filename, content, content_type, size, uploaded_at, original_name FROM files WHERE id = ?");
            $stmt->execute([$fileId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'success' => true,
                    'filename' => $result['filename'],
                    'original_name' => $result['original_name'],
                    'content' => $result['content'],
                    'contentType' => $result['content_type'],
                    'size' => $result['size'],
                    'uploadedAt' => $result['uploaded_at']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'File not found'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * List all files
     */
    public function listFiles() {
        if (!$this->filesDb) {
            return [
                'success' => false,
                'error' => 'Files database connection not available'
            ];
        }
        
        try {
            $stmt = $this->filesDb->query("SELECT id, filename, original_name, size, uploaded_at FROM files ORDER BY uploaded_at DESC");
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'files' => $files
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete a file
     */
    public function deleteFile($fileId) {
        if (!$this->filesDb) {
            return [
                'success' => false,
                'error' => 'Files database connection not available'
            ];
        }
        
        try {
            $stmt = $this->filesDb->prepare("DELETE FROM files WHERE id = ?");
            $result = $stmt->execute([$fileId]);
            
            if ($stmt->rowCount() > 0) {
                // Log activity
                $this->logActivity('file_delete', 'file', $fileId, "Deleted file ID: $fileId");
                
                // Schedule sync
                $this->scheduleSync();
                
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
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Store a shortened URL
     */
    public function storeLink($shortCode, $longUrl, $title = null) {
        if (!$this->linksDb) {
            return [
                'success' => false,
                'error' => 'Links database connection not available'
            ];
        }
        
        try {
            $stmt = $this->linksDb->prepare("INSERT INTO links (short_code, long_url, title) VALUES (?, ?, ?)");
            $result = $stmt->execute([$shortCode, $longUrl, $title]);
            
            if ($result) {
                // Log activity
                $this->logActivity('link_create', 'link', $this->linksDb->lastInsertId(), "Created short link: $shortCode -> $longUrl");
                
                // Schedule sync
                $this->scheduleSync();
                
                return [
                    'success' => true,
                    'shortCode' => $shortCode
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to store link in SQLite'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Retrieve a shortened URL
     */
    public function retrieveLink($shortCode) {
        if (!$this->linksDb) {
            return [
                'success' => false,
                'error' => 'Links database connection not available'
            ];
        }
        
        try {
            $stmt = $this->linksDb->prepare("SELECT short_code, long_url, title, clicks FROM links WHERE short_code = ?");
            $stmt->execute([$shortCode]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Update click count
                $this->linksDb->prepare("UPDATE links SET clicks = clicks + 1, last_access = datetime('now') WHERE short_code = ?")->execute([$shortCode]);
                
                return [
                    'success' => true,
                    'shortCode' => $result['short_code'],
                    'longUrl' => $result['long_url'],
                    'title' => $result['title'],
                    'clicks' => $result['clicks']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Link not found'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * List all links
     */
    public function listLinks() {
        if (!$this->linksDb) {
            return [
                'success' => false,
                'error' => 'Links database connection not available'
            ];
        }
        
        try {
            $stmt = $this->linksDb->query("SELECT id, short_code, long_url, title, clicks, created_at FROM links ORDER BY created_at DESC");
            $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'links' => $links
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Log activity
     */
    private function logActivity($action, $entityType, $entityId, $details) {
        if (!$this->activityDb) {
            return false;
        }
        
        try {
            $stmt = $this->activityDb->prepare("INSERT INTO activity (action, entity_type, entity_id, details) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$action, $entityType, $entityId, $details]);
        } catch (PDOException $e) {
            error_log("Activity logging failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recent activity
     */
    public function getRecentActivity($limit = 10) {
        if (!$this->activityDb) {
            return [
                'success' => false,
                'error' => 'Activity database connection not available'
            ];
        }
        
        try {
            $stmt = $this->activityDb->query("SELECT action, entity_type, entity_id, details, timestamp FROM activity ORDER BY timestamp DESC LIMIT $limit");
            $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'activity' => $activity
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Schedule sync to data branch
     */
    public function scheduleSync() {
        // Update the last sync timestamp
        file_put_contents(LAST_SYNC_FILE, time());
    }
    
    /**
     * Check if sync is needed
     */
    public function isSyncNeeded() {
        if (!file_exists(LAST_SYNC_FILE)) {
            return true;
        }
        
        $lastSync = (int)file_get_contents(LAST_SYNC_FILE);
        return (time() - $lastSync) >= SYNC_INTERVAL;
    }
    
    /**
     * Perform sync to data branch (called by cron/scheduler)
     */
    public function performSync() {
        if (!$this->isSyncNeeded()) {
            return ['success' => false, 'message' => 'Sync not needed yet'];
        }
        
        try {
            // Add and commit the data files to Git
            $output = [];
            $returnCode = 0;
            
            // Change to the repository directory
            $originalDir = getcwd();
            chdir(MAIN_REPO_PATH);
            
            // Add data files
            exec('git add ' . escapeshellarg(DATA_DIR), $output, $returnCode);
            
            if ($returnCode !== 0) {
                chdir($originalDir);
                return ['success' => false, 'error' => 'Git add failed: ' . implode(' ', $output)];
            }
            
            // Check if there are changes to commit
            exec('git status --porcelain', $statusOutput, $returnCode);
            
            if (empty($statusOutput)) {
                chdir($originalDir);
                return ['success' => false, 'message' => 'No changes to commit'];
            }
            
            // Commit changes
            $commitMessage = 'Auto-sync: Update data files at ' . date('Y-m-d H:i:s');
            exec('git commit -m ' . escapeshellarg($commitMessage), $output, $returnCode);
            
            if ($returnCode !== 0) {
                chdir($originalDir);
                return ['success' => false, 'error' => 'Git commit failed: ' . implode(' ', $output)];
            }
            
            // Push changes
            exec('git push origin main', $output, $returnCode);
            
            if ($returnCode === 0) {
                // Update the last sync timestamp
                file_put_contents(LAST_SYNC_FILE, time());
                
                // Log the sync activity
                $this->logActivity('sync_complete', 'system', null, 'Data sync completed successfully');
                
                chdir($originalDir);
                return ['success' => true, 'message' => 'Sync completed successfully'];
            } else {
                chdir($originalDir);
                return ['success' => false, 'error' => 'Git push failed: ' . implode(' ', $output)];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>