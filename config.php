<?php
// Configuration file for File Hosting Service
// Optimized for GitHub deployment with SQLite in data branch

// SQLite database configuration for data branch storage
define('DATA_BRANCH_NAME', 'data');           // Branch for data storage
define('DATA_DIR', __DIR__ . '/data');        // Main data directory
define('FILES_DB_PATH', DATA_DIR . '/files.db');      // Files database
define('LINKS_DB_PATH', DATA_DIR . '/links.db');      // Links database
define('ACTIVITY_DB_PATH', DATA_DIR . '/activity.db'); // Activity logs

// Database configuration (optional, kept for backward compatibility)
define('DB_HOST', 'localhost');
define('DB_NAME', 'filehosting');
define('DB_USER', 'root');
define('DB_PASS', '');

// MongoDB configuration (optional)
define('MONGODB_URI', 'mongodb://localhost:27017');
define('MONGODB_DB', 'filehosting');

// Git configuration for main repository
define('MAIN_REPO_PATH', __DIR__);  // Main code repository

// File storage configuration
define('MAX_FILE_SIZE', 10485760); // 10MB in bytes

// Security
define('SECRET_KEY', 'your-secret-key-here');

// API configuration
define('API_BASE_URL', 'https://localhost/api');

// Storage preference: Only 'sqlite' for this implementation
define('DEFAULT_STORAGE_TYPE', 'sqlite');

// Create data directory if it doesn't exist
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// Hourly sync configuration
define('SYNC_INTERVAL', 3600); // 1 hour in seconds
define('LAST_SYNC_FILE', DATA_DIR . '/last_sync.txt');
?>