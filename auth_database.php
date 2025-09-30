<?php
/**
 * Authentication handler
 * Handles login, signup, and session management
 */

require_once 'config.php';
require_once 'database.php';

// Start session
session_start();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Generate unique user ID
 */
function generateUserId() {
    return 'user_' . uniqid() . '_' . substr(md5(microtime()), 0, 8);
}

/**
 * Generate session ID
 */
function generateSessionId() {
    return bin2hex(random_bytes(32));
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number format
 */
function validatePhone($phone) {
    return preg_match('/^[\+]?[1-9][\d]{0,15}$/', $phone);
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    return strlen($password) >= 6; // Minimum 6 characters
}

/**
 * Handle user signup
 */
function handleSignup() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJSONResponse(['success' => false, 'message' => 'Invalid request method'], 405);
    }
    
    // Handle both JSON and form data
    $input = null;
    if (isset($_POST['email'])) {
        $input = $_POST;
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
    }
    
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirmPassword'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($email) || !validateEmail($email)) {
        $errors[] = 'Valid email address is required';
    }
    
    if (empty($phone) || !validatePhone($phone)) {
        $errors[] = 'Valid phone number is required';
    }
    
    if (empty($password) || !validatePassword($password)) {
        $errors[] = 'Password must be at least 6 characters long';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if (!empty($errors)) {
        sendJSONResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
    }
    
    try {
        $db = getDatabase();
        $connection = $db->getConnection();
        
        // Check if email already exists
        $checkEmail = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $stmt = $connection->prepare($checkEmail);
        $stmt->execute([$email]);
        
        if ($stmt->fetch()['count'] > 0) {
            sendJSONResponse(['success' => false, 'message' => 'Email address already registered'], 400);
        }
        
        // Create new user
        $userId = generateUserId();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $insertUser = "
            INSERT INTO users (user_id, email, phone_number, password_hash) 
            VALUES (?, ?, ?, ?)
        ";
        $stmt = $connection->prepare($insertUser);
        $stmt->execute([$userId, $email, $phone, $passwordHash]);
        
        sendJSONResponse([
            'success' => true, 
            'message' => 'Account created successfully',
            'user_id' => $userId
        ]);
        
    } catch (Exception $e) {
        logError('Signup failed', ['error' => $e->getMessage(), 'email' => $email]);
        sendJSONResponse(['success' => false, 'message' => 'Registration failed. Please try again.'], 500);
    }
}

/**
 * Handle user login
 */
function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJSONResponse(['success' => false, 'message' => 'Invalid request method'], 405);
    }
    
    // Handle both JSON and form data
    $input = null;
    if (isset($_POST['email'])) {
        $input = $_POST;
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
    }
    
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        sendJSONResponse(['success' => false, 'message' => 'Email and password are required'], 400);
    }
    
    try {
        $db = getDatabase();
        $connection = $db->getConnection();
        
        // Get user by email
        $getUser = "SELECT user_id, email, password_hash, is_active FROM users WHERE email = ?";
        $stmt = $connection->prepare($getUser);
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !$user['is_active']) {
            sendJSONResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            sendJSONResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
        }
        
        // Create session
        $sessionId = generateSessionId();
        $expiresAt = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
        
        $createSession = "
            INSERT INTO user_sessions (session_id, user_id, expires_at) 
            VALUES (?, ?, ?)
        ";
        $stmt = $connection->prepare($createSession);
        $stmt->execute([$sessionId, $user['user_id'], $expiresAt]);
        
        // Update last login
        $updateLastLogin = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
        $stmt = $connection->prepare($updateLastLogin);
        $stmt->execute([$user['user_id']]);
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['session_id'] = $sessionId;
        
        sendJSONResponse([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'user_id' => $user['user_id'],
                'email' => $user['email']
            ]
        ]);
        
    } catch (Exception $e) {
        logError('Login failed', ['error' => $e->getMessage(), 'email' => $email]);
        sendJSONResponse(['success' => false, 'message' => 'Login failed. Please try again.'], 500);
    }
}

/**
 * Handle logout
 */
function handleLogout() {
    if (isset($_SESSION['session_id'])) {
        try {
            $db = getDatabase();
            $connection = $db->getConnection();
            
            // Delete session from database
            $deleteSession = "DELETE FROM user_sessions WHERE session_id = ?";
            $stmt = $connection->prepare($deleteSession);
            $stmt->execute([$_SESSION['session_id']]);
            
        } catch (Exception $e) {
            logError('Logout database cleanup failed', ['error' => $e->getMessage()]);
        }
    }
    
    // Destroy session
    session_destroy();
    
    sendJSONResponse(['success' => true, 'message' => 'Logged out successfully']);
}

/**
 * Check if user is authenticated
 */
function checkAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_id'])) {
        return false;
    }
    
    try {
        $db = getDatabase();
        $connection = $db->getConnection();
        
        // Check if session is valid
        $checkSession = "
            SELECT s.session_id, u.user_id, u.email, u.is_active
            FROM user_sessions s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.session_id = ? AND s.expires_at > NOW() AND u.is_active = 1
        ";
        $stmt = $connection->prepare($checkSession);
        $stmt->execute([$_SESSION['session_id']]);
        $session = $stmt->fetch();
        
        return $session !== false;
        
    } catch (Exception $e) {
        logError('Auth check failed', ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!checkAuth()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email']
    ];
}

// Note: sendJSONResponse and logError functions are imported from config.php

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if ($action) {
    switch ($action) {
        case 'signup':
            handleSignup();
            break;
        case 'login':
            handleLogin();
            break;
        case 'logout':
            handleLogout();
            break;
        case 'check':
            $user = getCurrentUser();
            if ($user) {
                sendJSONResponse(['success' => true, 'authenticated' => true, 'user' => $user]);
            } else {
                sendJSONResponse(['success' => true, 'authenticated' => false]);
            }
            break;
        default:
            sendJSONResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }
} else {
    sendJSONResponse(['success' => false, 'message' => 'No action specified'], 400);
}
?>