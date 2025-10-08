<?php
// API Endpoint with Data Branch Storage
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/file_operations.php';

header('Content-Type: application/json');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];

// Parse URL to get API path - updated for direct file access
$basePath = '/api.php';
if (strpos($request, $basePath) === 0) {
    $path = substr($request, strlen($basePath));
} else {
    // For compatibility with routing systems that use /api/ format
    $basePath = '/api';
    if (strpos($request, $basePath) === 0) {
        $path = substr($request, strlen($basePath));
    } else {
        $path = $request;
    }
}

// Handle API routes
switch ($method) {
    case 'GET':
        if ($path === '/') {
            // Return API info
            echo json_encode([
                'message' => 'File Hosting API',
                'version' => '1.0',
                'endpoints' => [
                    'GET /files' => 'List all files',
                    'GET /file/{id}' => 'Get file by ID',
                    'GET /stats' => 'Get system statistics',
                    'POST /upload' => 'Upload a file',
                    'POST /shorten' => 'Shorten a URL',
                    'GET /redirect/{code}' => 'Redirect using short code'
                ]
            ]);
        } elseif (preg_match('/^\/stats\/?$/', $path)) {
            // Get system statistics
            $files = listAllFiles();
            $storage = getDataBranchStorage();
            $links = $storage->listLinks();
            
            $stats = [
                'total_files' => count($files),
                'total_links' => $links['success'] ? count($links['links']) : 0,
                'system_status' => 'operational',
                'timestamp' => date('c')
            ];
            
            echo json_encode($stats);
        } elseif (preg_match('/^\/files\/?/', $path)) {
            // List files from data branch storage
            $files = listAllFiles();
            
            // Format for API response
            $apiFiles = [];
            foreach ($files as $file) {
                $apiFiles[] = [
                    'id' => $file['id'],
                    'name' => $file['filename'],
                    'size' => $file['size'],
                    'date' => $file['date'],
                    'storage_type' => $file['storage_type']
                ];
            }
            
            echo json_encode(['files' => $apiFiles]);
        } elseif (preg_match('/^\/file\/(.+)$/', $path, $matches)) {
            // Get specific file
            $fileId = $matches[1];
            
            $storage = getDataBranchStorage();
            $file = $storage->retrieveFile($fileId);
            
            if ($file['success']) {
                echo json_encode([
                    'id' => $fileId,
                    'name' => $file['original_name'],
                    'size' => $file['size'],
                    'date' => $file['uploadedAt'],
                    'storage_type' => 'sqlite_data_branch'
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => $file['error']]);
            }
        } elseif (preg_match('/^\/redirect\/(.+)$/', $path, $matches)) {
            // Redirect using short code - use data branch storage
            $shortCode = $matches[1];
            
            $link = retrieveLink($shortCode);
            
            if ($link['success']) {
                header('Location: ' . $link['longUrl']);
                exit;
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Short link not found']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'POST':
        if (preg_match('/^\/upload\/?$/', $path)) {
            // Upload file via API
            $response = [];
            $storageType = $_POST['storage_type'] ?? DEFAULT_STORAGE_TYPE; // Use default which is 'sqlite'
            
            if (isset($_FILES['file'])) {
                $uploadResult = uploadFile($_FILES['file'], $storageType);
                
                if ($uploadResult['success']) {
                    $response = [
                        'success' => true,
                        'message' => 'File uploaded successfully',
                        'file' => [
                            'name' => $uploadResult['filename'],
                            'id' => $uploadResult['fileId'],
                            'storage_type' => $storageType
                        ]
                    ];
                } else {
                    http_response_code(500);
                    $response = [
                        'success' => false,
                        'message' => $uploadResult['error']
                    ];
                }
            } else {
                // Handle upload from JSON payload (for binary data)
                $input = json_decode(file_get_contents('php://input'), true);
                
                if ($input && isset($input['filename'], $input['content'])) {
                    // Create a temporary file from the content
                    $tmpFile = tempnam(sys_get_temp_dir(), 'api_upload_');
                    file_put_contents($tmpFile, base64_decode($input['content']));
                    
                    // Create the $_FILES-like structure
                    $file = [
                        'name' => $input['filename'],
                        'type' => $input['contentType'] ?? 'application/octet-stream',
                        'tmp_name' => $tmpFile,
                        'error' => UPLOAD_ERR_OK,
                        'size' => strlen(base64_decode($input['content']))
                    ];
                    
                    $uploadResult = uploadFile($file, $storageType);
                    
                    // Clean up temp file
                    unlink($tmpFile);
                    
                    if ($uploadResult['success']) {
                        $response = [
                            'success' => true,
                            'message' => 'File uploaded successfully',
                            'file' => [
                                'name' => $uploadResult['filename'],
                                'id' => $uploadResult['fileId'],
                                'storage_type' => $storageType
                            ]
                        ];
                    } else {
                        http_response_code(500);
                        $response = [
                            'success' => false,
                            'message' => $uploadResult['error']
                        ];
                    }
                } else {
                    http_response_code(400);
                    $response = [
                        'success' => false,
                        'message' => 'No file provided'
                    ];
                }
            }
            
            echo json_encode($response);
        } elseif (preg_match('/^\/shorten\/?$/', $path)) {
            // Shorten URL via API - use data branch storage
            $input = json_decode(file_get_contents('php://input'), true);
            $longUrl = isset($input['url']) ? filter_var($input['url'], FILTER_SANITIZE_URL) : null;
            $title = isset($input['title']) ? $input['title'] : null;
            
            if ($longUrl && filter_var($longUrl, FILTER_VALIDATE_URL)) {
                $storage = getDataBranchStorage();
                
                // Generate short code
                $shortCode = generateShortCode();
                
                // Check if short code already exists and regenerate if needed
                $link = $storage->retrieveLink($shortCode);
                while ($link['success']) {
                    $shortCode = generateShortCode();
                    $link = $storage->retrieveLink($shortCode);
                }
                
                $result = $storage->storeLink($shortCode, $longUrl, $title);
                
                if ($result['success']) {
                    $response = [
                        'success' => true,
                        'short_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/r/' . $shortCode,
                        'short_code' => $shortCode,
                        'original_url' => $longUrl
                    ];
                } else {
                    http_response_code(500);
                    $response = [
                        'success' => false,
                        'message' => $result['error']
                    ];
                }
            } else {
                http_response_code(400);
                $response = [
                    'success' => false,
                    'message' => 'Invalid URL provided'
                ];
            }
            
            echo json_encode($response);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    case 'DELETE':
        if (preg_match('/^\/file\/(.+)$/', $path, $matches)) {
            // Delete specific file
            $fileId = $matches[1];
            
            $deleteResult = deleteFile($fileId, 'sqlite');
            
            if ($deleteResult['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'File deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => $deleteResult['error']
                ]);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>