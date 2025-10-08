# File Hosting Service

A PHP-based file hosting service that leverages Git as the primary storage backend for GitHub deployment, with optional local storage, API functionality, and link shortening capabilities.

## Features

- **Git Storage**: Primary storage in Git repository for GitHub deployment
- **File Upload/Download**: Easy file management through web interface or API
- **Link Shortening**: Generate short URLs for easy sharing
- **API Access**: RESTful API for programmatic access
- **GitHub Actions Deployment**: Automated deployment workflow

## Requirements

- PHP 7.4+
- Git (for Git storage)
- MySQL/MariaDB (optional, for link shortening)

## Installation

1. Clone or download this repository
2. Set up your web server to point to this directory
3. (Optional) Configure your database settings in `config.php` for link shortening
4. Run the Git repository setup: `php setup_git.php`
5. Set up your web server to handle URL rewriting (see `.htaccess` file)

## Configuration

Update the `config.php` file with your specific settings:

```php
// Database configuration (optional, for link shortening)
define('DB_HOST', 'localhost');
define('DB_NAME', 'filehosting');
define('DB_USER', 'root');
define('DB_PASS', '');

// Git configuration - PRIMARY storage for GitHub deployment
define('GIT_REPO_PATH', __DIR__ . '/git-repo');

// File storage configuration
define('UPLOAD_DIR', 'uploads/');  // Fallback storage
define('MAX_FILE_SIZE', 10485760); // 10MB in bytes

// Storage preference: 'git', 'local', or 'mongodb'
// For GitHub deployment, Git is recommended as it persists in the repository
define('DEFAULT_STORAGE_TYPE', 'git');
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

### Git Repository (Primary for GitHub)
Files are stored in a Git repository, which persists in your GitHub repository when deployed. This is the recommended approach for GitHub deployment as files are version-controlled and persist with the codebase.

### Local Storage
Files are stored in the `uploads/` directory as configured in `config.php`. (May not persist in GitHub deployment depending on provider)

### MongoDB
Files are stored in MongoDB as binary data with metadata. (Requires external database and credentials not suitable for public repositories)

## GitHub Actions Deployment

The workflow in `.github/workflows/deploy.yml` provides automated deployment when code is pushed to the main branch. It includes:

- PHP environment setup
- Database initialization (optional)
- Git repository setup for file storage
- Application deployment

## Security Considerations

- Validate file types and sizes
- Implement authentication for sensitive operations
- Use HTTPS in production
- For production, consider using environment variables for sensitive data
- Git storage is safe for public repositories as it stores only the files themselves

## License

This project is open source and available under the [MIT License](LICENSE).