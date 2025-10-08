<?php
// Configuration file for File Hosting Service
// Optimized for GitHub deployment with Git as primary storage

// Database configuration (optional for link shortening)
define('DB_HOST', 'localhost');
define('DB_NAME', 'filehosting');
define('DB_USER', 'root');
define('DB_PASS', '');

// MongoDB configuration (optional)
define('MONGODB_URI', 'mongodb://localhost:27017');
define('MONGODB_DB', 'filehosting');

// Git configuration - PRIMARY storage for GitHub deployment
define('GIT_REPO_PATH', __DIR__ . '/git-repo');

// File storage configuration
define('UPLOAD_DIR', 'uploads/');  // Fallback storage
define('MAX_FILE_SIZE', 10485760); // 10MB in bytes

// Security
define('SECRET_KEY', 'your-secret-key-here');

// API configuration
define('API_BASE_URL', 'https://localhost/api');

// Storage preference: 'git', 'local', or 'mongodb'
// For GitHub deployment, Git is recommended as it persists in the repository
define('DEFAULT_STORAGE_TYPE', 'git');

// Create git repo directory if it doesn't exist
if (!is_dir(GIT_REPO_PATH)) {
    mkdir(GIT_REPO_PATH, 0755, true);
}

// Create upload directory if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
?>