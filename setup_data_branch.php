<?php
// Data Branch Setup for GitHub Deployment
require_once 'config.php';

echo "Setting up Data Branch Storage for GitHub Deployment...\n";

// Create the data directory if it doesn't exist
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
    echo "Created data directory: " . DATA_DIR . "\n";
}

// Initialize the files database
try {
    $filesDb = new PDO("sqlite:" . FILES_DB_PATH);
    $filesDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $filesDb->exec("CREATE TABLE IF NOT EXISTS files (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        filename TEXT NOT NULL,
        content BLOB,
        content_type TEXT,
        size INTEGER,
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        original_name TEXT
    )");
    
    echo "Files database initialized at: " . FILES_DB_PATH . "\n";
    echo "Files table created\n";
} catch (PDOException $e) {
    echo "Error initializing files database: " . $e->getMessage() . "\n";
}

// Initialize the links database
try {
    $linksDb = new PDO("sqlite:" . LINKS_DB_PATH);
    $linksDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $linksDb->exec("CREATE TABLE IF NOT EXISTS links (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        short_code TEXT UNIQUE NOT NULL,
        long_url TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_access DATETIME,
        clicks INTEGER DEFAULT 0,
        title TEXT
    )");
    
    echo "Links database initialized at: " . LINKS_DB_PATH . "\n";
    echo "Links table created\n";
} catch (PDOException $e) {
    echo "Error initializing links database: " . $e->getMessage() . "\n";
}

// Initialize the activity database
try {
    $activityDb = new PDO("sqlite:" . ACTIVITY_DB_PATH);
    $activityDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $activityDb->exec("CREATE TABLE IF NOT EXISTS activity (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        action TEXT NOT NULL,
        entity_type TEXT,
        entity_id INTEGER,
        details TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "Activity database initialized at: " . ACTIVITY_DB_PATH . "\n";
    echo "Activity table created\n";
} catch (PDOException $e) {
    echo "Error initializing activity database: " . $e->getMessage() . "\n";
}

// Create .gitignore to ignore everything except the data directory to be used with separate branch
$gitignoreContent = "*.tmp\n*.temp\n.DS_Store\nThumbs.db\ncomposer.lock\n*.log\n.env\nconfig.php\n# For data branch approach, you may want to ignore the entire data directory initially\n# and then selectively add it to a separate branch\n";
file_put_contents('.gitignore', $gitignoreContent, FILE_APPEND | LOCK_EX);

echo "\nData branch setup complete!\n";
echo "\nDatabase locations:\n";
echo "- Files: " . FILES_DB_PATH . "\n";
echo "- Links: " . LINKS_DB_PATH . "\n";
echo "- Activity: " . ACTIVITY_DB_PATH . "\n";
echo "\nFor GitHub deployment with separate data branch:\n";
echo "1. The SQLite databases are created in the '" . DATA_DIR . "' directory\n";
echo "2. You can manage this directory in a separate Git branch\n";
echo "3. The sync script runs hourly to commit and push changes\n";
echo "4. This keeps the data separate from the main code while maintaining version control.\n";
?>