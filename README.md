# GitFile Hosting Service

A web-based file hosting service that allows users to upload files, import from Git repositories, and shorten URLs. The service is built with Python Flask backend, SQLite databases stored in a Git branch for version-controlled data storage, and a modern web frontend.

## Features

- File upload and hosting with SQLite storage
- Import files directly from Git repositories
- URL shortening service
- Web-based user interface
- Responsive design
- Version-controlled data storage with Git
- Activity logging and tracking

## Architecture

- **Frontend**: HTML/CSS/JavaScript with Bootstrap
- **Backend**: Python Flask API
- **Database**: SQLite databases with Git-based version control
- **Data Storage**: Files and metadata stored in SQLite files in the `data_branch` directory
- **Deployment**: Docker container with GitHub Actions

## Setup Instructions

### Prerequisites

- Python 3.11+
- Git
- Docker (for containerized deployment)

### Local Development

1. Install Python dependencies:
   ```bash
   pip install -r requirements.txt
   ```

2. Run the application:
   ```bash
   python app.py
   ```

3. Access the application at `http://localhost:5000`

4. The SQLite databases will be created in the `data_branch/` directory and automatically committed to Git with each change

### Production Deployment

The application is configured to deploy using GitHub Actions. The workflow automatically:
- Tests the application on pull requests
- Builds a Docker image on pushes to main
- Runs basic functionality tests

## API Endpoints

### File Operations
- `GET /` - Home endpoint
- `POST /upload` - Upload a file
- `GET /download/<file_id>` - Download a file
- `GET /files` - List all files
- `DELETE /delete/<file_id>` - Delete a file

### Git Integration
- `POST /git/import` - Import files from a Git repository
- `GET /git/files` - List files imported from Git

### Link Shortener
- `POST /shorten` - Shorten a URL
- `GET /s/<short_code>` - Redirect to the original URL
- `GET /links` - List all shortened links
- `DELETE /links/<short_code>` - Delete a shortened link

## Data Storage

All data is stored in SQLite databases located in the `data_branch/` directory:
- `files.sqlite` - Stores file metadata and content
- `links.sqlite` - Stores URL shortener data
- `activity.sqlite` - Stores system activity logs

All changes to these databases are automatically committed to Git, providing version control for your data.

## Technologies Used

- Flask: Web framework
- SQLite: Database
- Git: Version control for data
- Bootstrap: Frontend framework
- Docker: Containerization
- GitHub Actions: CI/CD

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.