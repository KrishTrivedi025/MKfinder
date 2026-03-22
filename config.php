<?php
/**
 * MKfinder Configuration — XAMPP / MySQL
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);      // hide errors from browser, log them instead

date_default_timezone_set('UTC');

// ──────────────────────────────────────
// DATABASE — XAMPP defaults
// ──────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'mkfinder');
define('DB_USER', 'root');
define('DB_PASS', '');              // blank = default XAMPP password

// ──────────────────────────────────────
// UPLOAD SETTINGS
// ──────────────────────────────────────
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);   // 5 MB
define('ALLOWED_EXTENSIONS', ['jpg','jpeg','png','webp']);
define('ALLOWED_MIME_TYPES',  ['image/jpeg','image/png','image/webp']);

// ──────────────────────────────────────
// SPECIES
// ──────────────────────────────────────
define('SUPPORTED_SPECIES', [
    'American Robin',
    'Blue Grosbeak',
    'Northern Cardinal',
]);

// ──────────────────────────────────────
// BIRD ID API (leave empty = demo mode)
// ──────────────────────────────────────
define('IDENTIFICATION_API_KEY', getenv('BIRD_IDENTIFICATION_API_KEY') ?: '');
define('IDENTIFICATION_API_URL', getenv('BIRD_IDENTIFICATION_API_URL') ?: '');

// ──────────────────────────────────────
// SESSION / SECURITY
// ──────────────────────────────────────
define('SESSION_NAME',       'mkfinder_session');
define('CSRF_TOKEN_NAME',    'mkfinder_token');
define('SESSION_LIFETIME',   60 * 60 * 24 * 7);  // 7 days in seconds

// ──────────────────────────────────────
// RESPONSE MESSAGES
// ──────────────────────────────────────
define('MESSAGES', [
    'UPLOAD_SUCCESS'       => 'Image uploaded successfully.',
    'UPLOAD_ERROR'         => 'Failed to upload image. Please try again.',
    'INVALID_FILE_TYPE'    => 'Invalid file type. Please upload JPG, PNG, or WEBP images only.',
    'FILE_TOO_LARGE'       => 'File size exceeds the 5 MB limit.',
    'IDENTIFICATION_ERROR' => 'Failed to identify bird species. Please try again.',
    'NO_SPECIES_FOUND'     => 'No bird species detected in the uploaded image.',
    'SPECIES_NOT_SUPPORTED'=> 'The detected species is not currently supported.',
    'NETWORK_ERROR'        => 'Network error. Please check your connection.',
    'INVALID_REQUEST'      => 'Invalid request format.',
    'MISSING_FILE'         => 'No image file provided.',
    'DATABASE_ERROR'       => 'Database operation failed.',
    'SPECIES_NOT_FOUND'    => 'Species information not found.',
]);

// Ensure uploads folder exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// ──────────────────────────────────────
// HELPER FUNCTIONS
// ──────────────────────────────────────

function getMessage($key) {
    $msgs = MESSAGES;
    return $msgs[$key] ?? 'Unknown error.';
}

function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function generateCSRFToken() {
    initSession();
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRFToken($token) {
    initSession();
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function sanitizeInput($data) {
    if (is_array($data)) return array_map('sanitizeInput', $data);
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateFileUpload($file) {
    $errors = [];
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = getMessage('MISSING_FILE'); return $errors;
    }
    if ($file['error'] !== UPLOAD_ERR_OK)        { $errors[] = getMessage('UPLOAD_ERROR');      return $errors; }
    if ($file['size'] > MAX_UPLOAD_SIZE)          { $errors[] = getMessage('FILE_TOO_LARGE'); }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS))      { $errors[] = getMessage('INVALID_FILE_TYPE'); }

    if (function_exists('finfo_open')) {
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, ALLOWED_MIME_TYPES)) { $errors[] = getMessage('INVALID_FILE_TYPE'); }
    }
    return $errors;
}

function generateUniqueFilename($originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return 'bird_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
}

function sendJSONResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function logError($message, $context = []) {
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message'   => $message,
        'context'   => $context,
    ];
    error_log(json_encode($entry) . PHP_EOL, 3, __DIR__ . '/error.log');
}

function isLoggedIn() {
    initSession();
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true
           && !empty($_SESSION['user_id']);
}
?>