<?php
/**
 * MKfinder Image Upload Handler
 * Handles file uploads for bird identification
 */

require_once 'config.php';
require_once 'database.php';

// Initialize session for CSRF protection
session_start();

// Check if user is authenticated
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    sendJSONResponse([
        'success' => false,
        'message' => 'Authentication required. Please log in to upload images.',
        'error_code' => 'AUTH_REQUIRED'
    ], 401);
}

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse([
        'success' => false,
        'message' => getMessage('INVALID_REQUEST')
    ], 405);
}

try {
    // Check if file was uploaded
    if (!isset($_FILES['image'])) {
        sendJSONResponse([
            'success' => false,
            'message' => getMessage('MISSING_FILE')
        ], 400);
    }

    $uploadedFile = $_FILES['image'];

    // Validate file upload
    $validationErrors = validateFileUpload($uploadedFile);
    if (!empty($validationErrors)) {
        sendJSONResponse([
            'success' => false,
            'message' => implode(' ', $validationErrors)
        ], 400);
    }

    // Generate unique filename
    $uniqueFilename = generateUniqueFilename($uploadedFile['name']);
    $uploadPath = UPLOAD_DIR . $uniqueFilename;

    // Move uploaded file
    if (!move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
        logError('Failed to move uploaded file', [
            'original_name' => $uploadedFile['name'],
            'upload_path' => $uploadPath,
            'temp_name' => $uploadedFile['tmp_name']
        ]);
        
        sendJSONResponse([
            'success' => false,
            'message' => getMessage('UPLOAD_ERROR')
        ], 500);
    }

    // Get file information
    $fileInfo = [
        'original_name' => sanitizeInput($uploadedFile['name']),
        'filename' => $uniqueFilename,
        'size' => $uploadedFile['size'],
        'mime_type' => $uploadedFile['type'],
        'upload_path' => $uploadPath,
        'upload_time' => date('Y-m-d H:i:s'),
        'file_url' => 'uploads/' . $uniqueFilename
    ];

    // Store upload record in database
    $uploadRecord = [
        'upload_id' => uniqid('upload_', true),
        'filename' => $uniqueFilename,
        'original_name' => $fileInfo['original_name'],
        'file_size' => $fileInfo['size'],
        'mime_type' => $fileInfo['mime_type'],
        'file_path' => $uploadPath,
        'status' => 'uploaded'
    ];

    try {
        $db = getDatabase();
        $recordId = $db->saveUpload($uploadRecord);
        logError('Upload record saved successfully', ['record_id' => $recordId]);
    } catch (Exception $e) {
        logError('Failed to save upload record to database', [
            'upload_record' => $uploadRecord,
            'error' => $e->getMessage()
        ]);
        // Continue execution - upload was successful even if database save failed
    }

    // Return success response
    sendJSONResponse([
        'success' => true,
        'message' => getMessage('UPLOAD_SUCCESS'),
        'data' => [
            'upload_id' => $uploadRecord['upload_id'],
            'filename' => $uniqueFilename,
            'original_name' => $fileInfo['original_name'],
            'size' => $fileInfo['size'],
            'file_url' => $fileInfo['file_url'],
            'upload_time' => $fileInfo['upload_time']
        ]
    ]);

} catch (Exception $e) {
    logError('Exception in upload.php', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);

    sendJSONResponse([
        'success' => false,
        'message' => getMessage('UPLOAD_ERROR')
    ], 500);
}
?>
