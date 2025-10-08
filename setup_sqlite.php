<?php
// SQLite Setup for GitHub Deployment (Branch approach explained in documentation)
require_once 'config.php';

echo "Setting up SQLite for GitHub Deployment...\n";

$dataDir = dirname(SQLITE_DB_PATH);

// Create the data directory if it doesn't exist
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
    echo "Created data directory: $dataDir\n";
}

// Initialize or open the SQLite database
try {
    $pdo = new PDO("sqlite:" . SQLITE_DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create files table
    $pdo->exec("CREATE TABLE IF NOT EXISTS files (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        filename TEXT NOT NULL,
        content BLOB,
        content_type TEXT,
        size INTEGER,
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create links table for URL shortening
    $pdo->exec("CREATE TABLE IF NOT EXISTS links (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        short_code TEXT UNIQUE NOT NULL,
        long_url TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_access DATETIME,
        clicks INTEGER DEFAULT 0
    )");
    
    echo "SQLite database initialized at: " . SQLITE_DB_PATH . "\n";
    echo "Tables created: files, links\n";
} catch (PDOException $e) {
    echo "Error initializing SQLite database: " . $e->getMessage() . "\n";
    exit(1);
}

// Create .gitignore for data directory to be used with separate branch
$gitignoreContent = "*.tmp\n*.temp\n.DS_Store\nThumbs.db\ncomposer.lock\n*.log\n.env\nconfig.php\n# Data directory should be managed in a separate branch\ndata/\n";
file_put_contents('.gitignore', $gitignoreContent, FILE_APPEND | LOCK_EX);

echo "\nSQLite setup complete!\n";
echo "Database location: " . SQLITE_DB_PATH . "\n";
echo "\nFor GitHub deployment with separate data branch:\n";
echo "1. The SQLite database is created at: " . SQLITE_DB_PATH . "\n";
echo "2. Add '/data/' to .gitignore to prevent it from being committed to main branch\n";
echo "3. To use a separate data branch, you can manually create one with:\n";
echo "   git subtree split --prefix=data -b data\n";
echo "   Or use git worktrees for separate data management\n";
echo "4. This keeps the data separate from the main code while maintaining version control.\n";

// Create a data management script for documentation
$dataScript = '<?php
// Data branch management script
// This would be run by deployment system to sync data between branches

$mainDir = __DIR__;
$dataDir = $mainDir . "/data";
$dataBranch = "data";

// This is a conceptual script - actual implementation would depend on your git setup
echo "Data management script\\n";
echo "This would handle sync operations between the main branch and data branch\\n";
echo "In a real deployment, this would: \\n";
echo "1. Checkout the data branch \\n";
echo "2. Copy the SQLite database to the data branch \\n";
echo "3. Commit and push the data branch \\n";
echo "4. Optionally merge or sync changes back to main as needed \\n";
?>';

file_put_contents('data_management.php', $dataScript);
echo "Created data_management.php for reference implementation\n";
?>