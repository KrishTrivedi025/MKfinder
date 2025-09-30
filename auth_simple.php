<?php
/**
 * Simple Authentication Handler - NO DATABASE REQUIRED
 * Works with JSON file storage for DEMO MODE
 */

session_start();

header('Content-Type: application/json');

// Simple users file
$usersFile = __DIR__ . '/users_demo.json';

// Initialize users file if doesn't exist
if (!file_exists($usersFile)) {
    $defaultUsers = [
        [
            'user_id' => 'user_demo_123',
            'email' => 'demo@mkfinder.com',
            'phone' => '+1234567890',
            'password_hash' => password_hash('demo123', PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
    file_put_contents($usersFile, json_encode($defaultUsers, JSON_PRETTY_PRINT));
}

/**
 * Load users from JSON file
 */
function loadUsers() {
    global $usersFile;
    $content = file_get_contents($usersFile);
    return json_decode($content, true) ?: [];
}

/**
 * Save users to JSON file
 */
function saveUsers($users) {
    global $usersFile;
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
}

/**
 * Find user by email
 */
function findUserByEmail($email) {
    $users = loadUsers();
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return $user;
        }
    }
    return null;
}

// Get action
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// Handle actions
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
        handleCheck();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Handle signup
 */
function handleSignup() {
    // Get POST data
    $input = $_POST;
    if (empty($input)) {
        $input = json_decode(file_get_contents('php://input'), true);
    }
    
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirmPassword'] ?? '';
    
    // Validate
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Valid email address is required']);
        return;
    }
    
    if (empty($password) || strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        return;
    }
    
    if ($password !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        return;
    }
    
    // Check if user exists
    if (findUserByEmail($email)) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        return;
    }
    
    // Create new user
    $users = loadUsers();
    $newUser = [
        'user_id' => 'user_' . uniqid(),
        'email' => $email,
        'phone' => $phone,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $users[] = $newUser;
    saveUsers($users);
    
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'user_id' => $newUser['user_id']
    ]);
}

/**
 * Handle login
 */
function handleLogin() {
    // Get POST data
    $input = $_POST;
    if (empty($input)) {
        $input = json_decode(file_get_contents('php://input'), true);
    }
    
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password required']);
        return;
    }
    
    // Find user
    $user = findUserByEmail($email);
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        return;
    }
    
    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['logged_in'] = true;
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'user_id' => $user['user_id'],
            'email' => $user['email']
        ]
    ]);
}

/**
 * Handle logout
 */
function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}

/**
 * Check authentication
 */
function handleCheck() {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        echo json_encode([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'user_id' => $_SESSION['user_id'],
                'email' => $_SESSION['email']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'authenticated' => false
        ]);
    }
}
?>
