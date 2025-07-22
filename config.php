<?php
/**
 * MKfinder Configuration File
 * Contains all configuration settings for the bird identification system
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

// Database configuration (JSON file-based)
define('DB_FILE', __DIR__ . '/database.json');

// Upload configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Bird species configuration
define('SUPPORTED_SPECIES', [
    'American Robin',
    'Blue Jay',
    'Northern Cardinal'
]);

// API Configuration
define('IDENTIFICATION_API_KEY', getenv('BIRD_IDENTIFICATION_API_KEY') ?: '');
define('IDENTIFICATION_API_URL', getenv('BIRD_IDENTIFICATION_API_URL') ?: '');

// Security settings
define('CSRF_TOKEN_NAME', 'mkfinder_token');
define('SESSION_NAME', 'mkfinder_session');

// Response messages
define('MESSAGES', [
    'UPLOAD_SUCCESS' => 'Image uploaded successfully.',
    'UPLOAD_ERROR' => 'Failed to upload image. Please try again.',
    'INVALID_FILE_TYPE' => 'Invalid file type. Please upload JPG, PNG, or WEBP images only.',
    'FILE_TOO_LARGE' => 'File size exceeds the maximum limit of 5MB.',
    'IDENTIFICATION_ERROR' => 'Failed to identify bird species. Please try again.',
    'NO_SPECIES_FOUND' => 'No bird species detected in the uploaded image.',
    'SPECIES_NOT_SUPPORTED' => 'The detected species is not currently supported by our system.',
    'NETWORK_ERROR' => 'Network error occurred. Please check your connection.',
    'INVALID_REQUEST' => 'Invalid request format.',
    'MISSING_FILE' => 'No image file provided.',
    'DATABASE_ERROR' => 'Database operation failed.',
    'SPECIES_NOT_FOUND' => 'Species information not found.'
]);

// Ensure upload directory exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

/**
 * Get configuration value
 */
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

/**
 * Get message by key
 */
function getMessage($key) {
    $messages = MESSAGES;
    return isset($messages[$key]) ? $messages[$key] : 'Unknown error occurred.';
}

/**
 * Initialize session if not started
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    initSession();
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    initSession();
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate file upload
 */
function validateFileUpload($file) {
    $errors = [];

    // Check if file was uploaded
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = getMessage('MISSING_FILE');
        return $errors;
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = getMessage('UPLOAD_ERROR');
        return $errors;
    }

    // Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $errors[] = getMessage('FILE_TOO_LARGE');
    }

    // Check file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
        $errors[] = getMessage('INVALID_FILE_TYPE');
    }

    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        $errors[] = getMessage('INVALID_FILE_TYPE');
    }

    return $errors;
}

/**
 * Generate unique filename
 */
function generateUniqueFilename($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    return "bird_{$timestamp}_{$random}.{$extension}";
}

/**
 * Send JSON response
 */
function sendJSONResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Log error message
 */
function logError($message, $context = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context
    ];
    
    error_log(json_encode($logEntry) . PHP_EOL, 3, __DIR__ . '/error.log');
}

/**
 * Legacy database functions - kept for backward compatibility
 * Now redirects to PostgreSQL database
 */
function getLegacyDatabase() {
    if (!file_exists(DB_FILE)) {
        return ['species' => [], 'identifications' => []];
    }
    
    $data = file_get_contents(DB_FILE);
    $decoded = json_decode($data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logError('Failed to decode database JSON: ' . json_last_error_msg());
        return ['species' => [], 'identifications' => []];
    }
    
    return $decoded;
}

/**
 * Legacy save database function
 */
function saveLegacyDatabase($data) {
    $json = json_encode($data, JSON_PRETTY_PRINT);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logError('Failed to encode database JSON: ' . json_last_error_msg());
        return false;
    }
    
    $result = file_put_contents(DB_FILE, $json, LOCK_EX);
    if ($result === false) {
        logError('Failed to write database file');
        return false;
    }
    
    return true;
}
?>
