<?php
/**
 * MKfinder Configuration File
 * MySQL/XAMPP Version
 */

// Database configuration for XAMPP MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'mkfinder');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default XAMPP MySQL password is empty
define('DB_CHARSET', 'utf8mb4');

// File paths
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('DB_BACKUP_FILE', __DIR__ . '/database_backup.json');

// Supported species
define('SUPPORTED_SPECIES', [
    'American Robin',
    'Blue Jay', 
    'Northern Cardinal'
]);

// File upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png', 
    'image/gif'
]);

// Application settings
define('ITEMS_PER_PAGE', 12);
define('LOG_ERRORS', true);

/**
 * Initialize session
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Sanitize user input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Send JSON response
 */
function sendJSONResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Get localized message
 */
function getMessage($key) {
    $messages = [
        'UPLOAD_SUCCESS' => 'File uploaded successfully',
        'UPLOAD_ERROR' => 'Error uploading file',
        'INVALID_FILE_TYPE' => 'Invalid file type. Please upload JPG, PNG, or GIF images.',
        'FILE_TOO_LARGE' => 'File is too large. Maximum size is ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB.',
        'IDENTIFICATION_SUCCESS' => 'Bird species identified successfully',
        'IDENTIFICATION_ERROR' => 'Error identifying bird species',
        'SPECIES_NOT_SUPPORTED' => 'This bird species is not currently supported',
        'DATABASE_ERROR' => 'Database connection error',
        'INVALID_REQUEST' => 'Invalid request method'
    ];
    
    return isset($messages[$key]) ? $messages[$key] : $key;
}

/**
 * Validate uploaded file
 */
function validateUploadedFile($file) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['valid' => false, 'message' => getMessage('UPLOAD_ERROR')];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['valid' => false, 'message' => 'No file was uploaded'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['valid' => false, 'message' => getMessage('FILE_TOO_LARGE')];
        default:
            return ['valid' => false, 'message' => getMessage('UPLOAD_ERROR')];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['valid' => false, 'message' => getMessage('FILE_TOO_LARGE')];
    }

    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);

    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        return ['valid' => false, 'message' => getMessage('INVALID_FILE_TYPE')];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['valid' => false, 'message' => getMessage('INVALID_FILE_TYPE')];
    }

    return ['valid' => true, 'message' => 'File is valid'];
}

/**
 * Generate unique filename
 */
function generateUniqueFilename($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return uniqid('bird_', true) . '.' . $extension;
}

/**
 * Log errors
 */
function logError($message, $context = []) {
    if (!LOG_ERRORS) {
        return;
    }
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'context' => $context,
        'file' => basename($_SERVER['SCRIPT_NAME'] ?? 'unknown'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    error_log(json_encode($logEntry) . PHP_EOL, 3, __DIR__ . '/error.log');
}

/**
 * Get default species information (fallback)
 */
function getDefaultSpeciesInfo($speciesName) {
    $defaultInfo = [
        'American Robin' => [
            'name' => 'American Robin',
            'scientific_name' => 'Turdus migratorius',
            'description' => 'The American Robin is a migratory songbird with a distinctive reddish-orange breast.',
            'size' => '8-11 inches',
            'habitat' => 'Woodlands, suburban areas, parks, and gardens',
            'diet' => 'Earthworms, insects, fruits, and berries',
            'behavior' => 'Often seen hopping on lawns searching for worms',
            'conservation_status' => 'Least Concern'
        ],
        'Blue Jay' => [
            'name' => 'Blue Jay',
            'scientific_name' => 'Cyanocitta cristata',
            'description' => 'The Blue Jay is an intelligent songbird with bright blue coloration and a prominent crest.',
            'size' => '11-12 inches',
            'habitat' => 'Deciduous and mixed forests, parks, and suburban areas',
            'diet' => 'Nuts, seeds, insects, and occasionally eggs',
            'behavior' => 'Highly intelligent and social, known for loud calls',
            'conservation_status' => 'Least Concern'
        ],
        'Northern Cardinal' => [
            'name' => 'Northern Cardinal',
            'scientific_name' => 'Cardinalis cardinalis',
            'description' => 'The Northern Cardinal is a vibrant red songbird with a distinctive crest and orange bill.',
            'size' => '8.5-9 inches',
            'habitat' => 'Woodland edges, gardens, shrublands, and swamps',
            'diet' => 'Seeds, grains, fruits, and insects',
            'behavior' => 'Non-migratory, males are territorial and sing from perches',
            'conservation_status' => 'Least Concern'
        ]
    ];
    
    return $defaultInfo[$speciesName] ?? null;
}

/**
 * Legacy database functions for JSON fallback
 */
function getLegacyDatabase() {
    if (!file_exists(DB_BACKUP_FILE)) {
        return ['species' => [], 'identifications' => []];
    }
    
    $data = file_get_contents(DB_BACKUP_FILE);
    $decoded = json_decode($data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logError('Failed to decode database JSON: ' . json_last_error_msg());
        return ['species' => [], 'identifications' => []];
    }
    
    return $decoded;
}

/**
 * Save legacy database
 */
function saveLegacyDatabase($data) {
    $json = json_encode($data, JSON_PRETTY_PRINT);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logError('Failed to encode database JSON: ' . json_last_error_msg());
        return false;
    }
    
    $result = file_put_contents(DB_BACKUP_FILE, $json, LOCK_EX);
    if ($result === false) {
        logError('Failed to write database backup file');
        return false;
    }
    
    return true;
}
?>