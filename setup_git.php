<?php
// Git Repository Setup for GitHub Deployment
require_once 'config.php';

echo "Setting up Git repository for GitHub deployment...\n";

$repoPath = GIT_REPO_PATH;

// Initialize git repo if not already initialized
if (!file_exists($repoPath . '/.git')) {
    echo "Initializing Git repository...\n";
    
    $result = executeGitCommand("git init");
    
    if ($result['return_code'] !== 0) {
        echo "Error initializing Git repository: " . implode(' ', $result['output']) . "\n";
        exit(1);
    }
    
    echo "Git repository initialized.\n";
    
    // Set git config
    executeGitCommand("git config user.name 'GitHub File Hosting'");
    executeGitCommand("git config user.email 'github@filehosting.example.com'");
    
    // Create .gitignore
    $gitignoreContent = "*.tmp\n*.temp\n.DS_Store\nThumbs.db\ncomposer.lock\n*.log\n.env\nconfig.php\n";
    file_put_contents($repoPath . '/.gitignore', $gitignoreContent);
    
    echo "Created .gitignore file.\n";
} else {
    echo "Git repository already exists.\n";
}

// Create a README for the Git storage
$gitReadme = "# File Storage\n\nThis directory contains files uploaded to the file hosting service.\n\nFiles are stored here for GitHub deployment.\n";
$readmePath = $repoPath . '/README.md';
if (!file_exists($readmePath)) {
    file_put_contents($readmePath, $gitReadme);
    
    executeGitCommand("git add README.md");
    executeGitCommand("git commit -m \"Add README for file storage\"");
    
    echo "Created README.md in Git repository.\n";
}

echo "\nGit repository setup complete!\n";
echo "Files uploaded with Git storage will be saved to: " . $repoPath . "\n";
echo "This allows GitHub deployment to persist files in the repository.\n";

// Git command execution function (needed here before includes)
function executeGitCommand($command) {
    global $repoPath;
    $output = [];
    $return_code = 0;
    
    $full_command = 'cd ' . $repoPath . ' && ' . $command;
    exec($full_command, $output, $return_code);
    
    return [
        'output' => $output,
        'return_code' => $return_code
    ];
}
?>