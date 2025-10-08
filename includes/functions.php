<?php
// Utility functions for File Hosting Service

/**
 * Generate a unique short code for link shortening
 * @param int $length Length of the short code
 * @return string Generated short code
 */
function generateShortCode($length = 6) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $code;
}

/**
 * Validate file upload
 * @param array $file File array from $_FILES
 * @return bool True if file is valid, false otherwise
 */
function validateFile($file) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    return true;
}

/**
 * Hash the filename to prevent conflicts
 * @param string $filename Original filename
 * @return string Hashed filename
 */
function hashFilename($filename) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $name = pathinfo($filename, PATHINFO_FILENAME);
    return md5($name . time()) . '.' . $ext;
}

/**
 * Generate file URL
 * @param string $filename Name of the uploaded file
 * @return string Full URL to the file
 */
function generateFileUrl($filename) {
    return $_SERVER['HTTP_HOST'] . UPLOAD_DIR . $filename;
}

/**
 * Connect to MongoDB
 * @return MongoDB\Client Connected MongoDB client or null on failure
 */
function connectMongoDB() {
    if (!extension_loaded('mongodb')) {
        error_log("MongoDB extension not loaded");
        return null;
    }
    
    try {
        $client = new MongoDB\Client(MONGODB_URI);
        $db = $client->selectDatabase(MONGODB_DB);
        return $db;
    } catch (Exception $e) {
        error_log("MongoDB connection failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Connect to MySQL database
 * @return PDO Connected database object or null on failure
 */
function connectDatabase() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Connect to files SQLite database
 * @return PDO Connected database object or null on failure
 */
function connectFilesDb() {
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
 * Connect to links SQLite database
 * @return PDO Connected database object or null on failure
 */
function connectLinksDb() {
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
 * Execute Git command
 * @param string $command Git command to execute
 * @return array Result of the command execution
 */
function executeGitCommand($command) {
    $output = [];
    $return_code = 0;
    
    $full_command = 'cd ' . MAIN_REPO_PATH . ' && ' . $command;
    exec($full_command, $output, $return_code);
    
    return [
        'output' => $output,
        'return_code' => $return_code
    ];
}

/**
 * Initialize the database tables if they don't exist
 */
function initializeDatabase() {
    $pdo = connectDatabase();
    
    if (!$pdo) {
        return false;
    }
    
    try {
        // Create links table for URL shortening
        $sql = "CREATE TABLE IF NOT EXISTS links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            short_code VARCHAR(20) UNIQUE NOT NULL,
            long_url TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_access TIMESTAMP NULL,
            clicks INT DEFAULT 0
        )";
        
        $pdo->exec($sql);
        
        // Create files table to track stored files
        $sql = "CREATE TABLE IF NOT EXISTS files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_id VARCHAR(24) NOT NULL, -- For MongoDB object IDs
            filename VARCHAR(255) NOT NULL,
            storage_type ENUM('local', 'mongodb', 'git') DEFAULT 'local',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        
        return true;
    } catch (PDOException $e) {
        error_log("Database initialization error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get the data branch storage instance
 */
function getDataBranchStorage() {
    require_once 'includes/data_branch_storage.php';
    return new DataBranchStorage();
}
?>