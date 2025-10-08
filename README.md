# File Hosting Service - Data Branch Storage

A PHP-based file hosting service that stores all data in SQLite databases managed in a Git data branch, with automatic hourly sync. Perfect for GitHub deployment without external dependencies.

## Features

- **Data Branch Storage**: All files and links stored in SQLite databases managed in a Git data branch
- **Automatic Sync**: Hourly Git commits and pushes to sync data changes
- **File Upload/Download**: Easy file management through web interface or API
- **Link Shortening**: Generate short URLs for easy sharing
- **API Access**: RESTful API for programmatic access
- **GitHub Actions Deployment**: Automated deployment workflow

## Requirements

- PHP 7.4+
- Git (required for data branch management)
- SQLite (for database storage)

## Installation

1. Clone or download this repository
2. Set up your web server to point to this directory
3. Run the data branch setup: `php setup_data_branch.php`
4. Set up your web server to handle URL rewriting (see `.htaccess` file)
5. Configure hourly sync (see below)

## Configuration

Update the `config.php` file with your specific settings:

```php
// SQLite database configuration for data branch storage
define('DATA_BRANCH_NAME', 'data');           // Branch for data storage
define('DATA_DIR', __DIR__ . '/data');        // Main data directory
define('FILES_DB_PATH', DATA_DIR . '/files.db');      // Files database
define('LINKS_DB_PATH', DATA_DIR . '/links.db');      // Links database
define('ACTIVITY_DB_PATH', DATA_DIR . '/activity.db'); // Activity logs

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

// Hourly sync configuration
define('SYNC_INTERVAL', 3600); // 1 hour in seconds
define('LAST_SYNC_FILE', DATA_DIR . '/last_sync.txt');
```

## Usage

### Web Interface

1. Visit the main page to upload files (Git storage is recommended for GitHub)
2. Navigate to the "Files" page to view and manage your files
3. Use the "Link Shortener" page to create short URLs

### API

The API provides endpoints for programmatic access:

- `GET /api/` - API information
- `GET /api/files` - List all files
- `GET /api/file/{id}` - Get specific file
- `POST /api/upload` - Upload a file
- `POST /api/shorten` - Shorten a URL
- `DELETE /api/file/{id}` - Delete a file

## Storage Backends

### Data Branch Storage (Primary & Only Method)
All data (files and links) is stored in SQLite databases that are managed in a Git branch. This approach combines the benefits of structured data storage with Git version control.

The system uses three SQLite databases:
- `files.db`: Stores uploaded files as BLOBs with metadata
- `links.db`: Stores URL shortening mappings
- `activity.db`: Stores system activity logs

All databases are stored in the `data/` directory and managed as part of a Git data branch.

## Data Branch Management

The system automatically syncs data to the Git repository every hour via a cron job. Here's how to set it up:

### Setting up Hourly Sync

1. **Run the cron setup script:**
   ```bash
   ./setup_cron.sh
   ```

2. **Or manually add to your crontab:**
   ```bash
   # Edit crontab
   crontab -e
   
   # Add this line (replace with your actual path):
   0 * * * * cd /path/to/your/cdn && php sync_data_branch.php
   ```

### Manual Sync

You can also trigger a sync manually:
```bash
php sync_data_branch.php
```

This will commit and push the data files to your Git repository if changes have occurred within the sync interval.

### GitHub Actions Workflow

The workflow in `.github/workflows/deploy.yml` provides automated deployment when code is pushed to the main branch. It includes:

- PHP environment setup
- SQLite database initialization
- Git repository setup
- Application deployment

## Security Considerations

- Validate file types and sizes
- Implement authentication for sensitive operations
- Use HTTPS in production
- For production, consider using environment variables for sensitive data
- Git storage is safe for public repositories as it stores only the files themselves

## License

This project is open source and available under the [MIT License](LICENSE).