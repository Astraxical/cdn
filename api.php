<?php
// API Endpoint
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/file_operations.php';

header('Content-Type: application/json');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];

// Parse URL to get API path
$basePath = '/api';
if (strpos($request, $basePath) === 0) {
    $path = substr($request, strlen($basePath));
} else {
    $path = $request;
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
                    'POST /upload' => 'Upload a file',
                    'POST /shorten' => 'Shorten a URL',
                    'GET /redirect/{code}' => 'Redirect using short code'
                ]
            ]);
        } elseif (preg_match('/^\/files\/?/', $path)) {
            // List files from all storage types
            $files = listAllFiles();
            
            // Format for API response
            $apiFiles = [];
            foreach ($files as $file) {
                $apiFiles[] = [
                    'id' => $file['id'],
                    'name' => $file['filename'] ?? $file['name'],
                    'size' => $file['size'],
                    'date' => $file['date'],
                    'storage_type' => $file['storage_type']
                ];
            }
            
            echo json_encode(['files' => $apiFiles]);
        } elseif (preg_match('/^\/file\/(.+)$/', $path, $matches)) {
            // Get specific file - need to know the storage type
            $fileId = $matches[1];
            $files = listAllFiles();
            
            $targetFile = null;
            foreach ($files as $file) {
                if (($file['id'] === $fileId) || (md5($file['filename'] ?? $file['name'] ?? '') === $fileId)) {
                    $targetFile = $file;
                    break;
                }
            }
            
            if ($targetFile) {
                echo json_encode([
                    'id' => $targetFile['id'],
                    'name' => $targetFile['filename'] ?? $targetFile['name'],
                    'size' => $targetFile['size'],
                    'date' => $targetFile['date'],
                    'storage_type' => $targetFile['storage_type']
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'File not found']);
            }
        } elseif (preg_match('/^\/redirect\/(.+)$/', $path, $matches)) {
            // Redirect using short code
            $shortCode = $matches[1];
            $pdo = connectDatabase();
            
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("SELECT long_url FROM links WHERE short_code = ?");
                    $stmt->execute([$shortCode]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result) {
                        header('Location: ' . $result['long_url']);
                        exit;
                    } else {
                        http_response_code(404);
                        echo json_encode(['error' => 'Short link not found']);
                    }
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Database error']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Database connection failed']);
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
            $storageType = $_POST['storage_type'] ?? 'local'; // Default to local storage
            
            if (isset($_FILES['file'])) {
                $uploadResult = uploadFile($_FILES['file'], $storageType);
                
                if ($uploadResult['success']) {
                    $response = [
                        'success' => true,
                        'message' => 'File uploaded successfully',
                        'file' => [
                            'name' => $uploadResult['filename'] ?? $uploadResult['originalName'],
                            'id' => $uploadResult['fileId'] ?? $uploadResult['hashedName'],
                            'storage_type' => $storageType,
                            'url' => $uploadResult['url'] ?? null
                        ]
                    ];
                } else {
                    http_response_code(500);
                    $response = [
                        'success' => false,
                        'message' => $uploadResult['error'] ?? 'Failed to upload file'
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
                                'name' => $uploadResult['filename'] ?? $uploadResult['originalName'],
                                'id' => $uploadResult['fileId'] ?? $uploadResult['hashedName'],
                                'storage_type' => $storageType,
                                'url' => $uploadResult['url'] ?? null
                            ]
                        ];
                    } else {
                        http_response_code(500);
                        $response = [
                            'success' => false,
                            'message' => $uploadResult['error'] ?? 'Failed to upload file'
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
            // Shorten URL via API
            $input = json_decode(file_get_contents('php://input'), true);
            $longUrl = isset($input['url']) ? filter_var($input['url'], FILTER_SANITIZE_URL) : null;
            
            if ($longUrl && filter_var($longUrl, FILTER_VALIDATE_URL)) {
                $pdo = connectDatabase();
                
                if ($pdo) {
                    try {
                        $shortCode = generateShortCode();
                        
                        $stmt = $pdo->prepare("SELECT short_code FROM links WHERE short_code = ?");
                        $stmt->execute([$shortCode]);
                        
                        while ($stmt->rowCount() > 0) {
                            $shortCode = generateShortCode();
                            $stmt = $pdo->prepare("SELECT short_code FROM links WHERE short_code = ?");
                            $stmt->execute([$shortCode]);
                        }
                        
                        $stmt = $pdo->prepare("INSERT INTO links (short_code, long_url, created_at) VALUES (?, ?, NOW())");
                        $result = $stmt->execute([$shortCode, $longUrl]);
                        
                        if ($result) {
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
                                'message' => 'Failed to create short URL'
                            ];
                        }
                    } catch (PDOException $e) {
                        http_response_code(500);
                        $response = [
                            'success' => false,
                            'message' => 'Database error: ' . $e->getMessage()
                        ];
                    }
                } else {
                    http_response_code(500);
                    $response = [
                        'success' => false,
                        'message' => 'Database connection failed'
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
            
            // Need to determine storage type - for now, try to get file info from DB first
            $pdo = connectDatabase();
            $storageType = 'local'; // Default
            
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("SELECT storage_type FROM files WHERE file_id = ?");
                    $stmt->execute([$fileId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result) {
                        $storageType = $result['storage_type'];
                    }
                } catch (PDOException $e) {
                    error_log("Error determining storage type: " . $e->getMessage());
                }
            }
            
            $deleteResult = deleteFile($fileId, $storageType);
            
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