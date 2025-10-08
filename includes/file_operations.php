<?php
// File upload/download functionality with Data Branch Storage
require_once 'config.php';
require_once 'includes/functions.php';

/**
 * Upload a file to specified storage
 * @param array $file File array from $_FILES
 * @param string $storageType Type of storage ('sqlite', with data branch)
 * @return array Result of the upload operation
 */
function uploadFile($file, $storageType = 'sqlite') {
    if (!validateFile($file)) {
        return [
            'success' => false,
            'error' => 'Invalid file'
        ];
    }
    
    $originalName = basename($file['name']);
    $contentType = mime_content_type($file['tmp_name']) ?: 'application/octet-stream';
    $content = file_get_contents($file['tmp_name']);
    
    switch ($storageType) {
        case 'sqlite':
        default:
            $storage = getDataBranchStorage();
            return $storage->storeFile(hashFilename($originalName), $content, $contentType, $originalName);
    }
}

/**
 * Download a file from specified storage
 * @param string $fileId ID of the file to download
 * @param string $storageType Type of storage ('sqlite', with data branch)
 * @return array File data or error
 */
function downloadFile($fileId, $storageType = 'sqlite') {
    switch ($storageType) {
        case 'sqlite':
        default:
            $storage = getDataBranchStorage();
            return $storage->retrieveFile($fileId);
    }
}

/**
 * List all files from data branch storage
 * @return array List of files from data branch storage
 */
function listAllFiles() {
    $files = [];
    
    // Get files from data branch storage
    try {
        $storage = getDataBranchStorage();
        $result = $storage->listFiles();
        if ($result['success']) {
            foreach ($result['files'] as $file) {
                $files[] = [
                    'id' => $file['id'],
                    'filename' => $file['original_name'] ?? $file['filename'],
                    'size' => $file['size'],
                    'date' => $file['uploaded_at'],
                    'storage_type' => 'sqlite_data_branch'
                ];
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching data branch files: " . $e->getMessage());
    }
    
    return $files;
}

/**
 * Delete a file from data branch storage
 * @param string $fileId ID of the file to delete
 * @param string $storageType Type of storage ('sqlite', with data branch)
 * @return array Result of the delete operation
 */
function deleteFile($fileId, $storageType = 'sqlite') {
    switch ($storageType) {
        case 'sqlite':
        default:
            $storage = getDataBranchStorage();
            return $storage->deleteFile($fileId);
    }
}

/**
 * Store a link in data branch storage
 * @param string $shortCode The short code for the link
 * @param string $longUrl The original URL
 * @param string $title Optional title for the link
 * @return array Result of the operation
 */
function storeLink($shortCode, $longUrl, $title = null) {
    $storage = getDataBranchStorage();
    return $storage->storeLink($shortCode, $longUrl, $title);
}

/**
 * Retrieve a link from data branch storage
 * @param string $shortCode The short code to look up
 * @return array Link data or error
 */
function retrieveLink($shortCode) {
    $storage = getDataBranchStorage();
    return $storage->retrieveLink($shortCode);
}

/**
 * List all links from data branch storage
 * @return array List of links
 */
function listAllLinks() {
    $links = [];
    
    try {
        $storage = getDataBranchStorage();
        $result = $storage->listLinks();
        if ($result['success']) {
            $links = $result['links'];
        }
    } catch (Exception $e) {
        error_log("Error fetching data branch links: " . $e->getMessage());
    }
    
    return $links;
}

/**
 * Perform sync to data branch if needed
 * @return array Result of the sync operation
 */
function performDataBranchSync() {
    $storage = getDataBranchStorage();
    return $storage->performSync();
}

/**
 * Check if sync is needed
 * @return bool True if sync is needed, false otherwise
 */
function isSyncNeeded() {
    $storage = getDataBranchStorage();
    return $storage->isSyncNeeded();
}
?>