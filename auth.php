<?php
/**
 * MKfinder — auth.php (UPDATED)
 * Handles login, signup, logout, session check
 * NOW detects admin role and sets is_admin session flag
 */

require_once 'config.php';
require_once 'database.php';

initSession();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ─────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────

function generateUserId() {
    return 'user_' . uniqid() . '_' . substr(md5(microtime()), 0, 8);
}

function generateSessionId() {
    return bin2hex(random_bytes(32));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    return preg_match('/^[\+]?[1-9][\d]{0,15}$/', $phone);
}

function validatePassword($password) {
    return strlen($password) >= 6;
}

// ─────────────────────────────────────────────────────────────
// SIGNUP
// ─────────────────────────────────────────────────────────────

function handleSignup() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJSONResponse(['success' => false, 'message' => 'Invalid request method'], 405);
    }

    $input = isset($_POST['email'])
        ? $_POST
        : json_decode(file_get_contents('php://input'), true);

    $email           = trim($input['email']           ?? '');
    $phone           = trim($input['phone']           ?? '');
    $firstName       = trim($input['firstName']  ?? $input['first_name']  ?? '');
    $lastName        = trim($input['lastName']   ?? $input['last_name']   ?? '');
    $password        = $input['password']             ?? '';
    $confirmPassword = $input['confirmPassword']      ?? '';

    $errors = [];
    if (empty($email) || !validateEmail($email))          $errors[] = 'Valid email address is required';
    if (empty($phone) || !validatePhone($phone))          $errors[] = 'Valid phone number is required';
    if (empty($password) || !validatePassword($password)) $errors[] = 'Password must be at least 6 characters';
    if ($password !== $confirmPassword)                   $errors[] = 'Passwords do not match';

    if (!empty($errors)) {
        sendJSONResponse(['success' => false, 'message' => implode(', ', $errors)], 400);
    }

    try {
        $db         = getDatabase();
        $connection = $db->getConnection();

        $stmt = $connection->prepare("SELECT COUNT(*) as cnt FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()['cnt'] > 0) {
            sendJSONResponse(['success' => false, 'message' => 'Email address already registered'], 400);
        }

        $userId       = generateUserId();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $connection->prepare(
            "INSERT INTO users
                (user_id, email, phone_number, first_name, last_name, password_hash, role)
             VALUES (?, ?, ?, ?, ?, ?, 'user')"
        );
        $stmt->execute([$userId, $email, $phone, $firstName, $lastName, $passwordHash]);

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

// ─────────────────────────────────────────────────────────────
// LOGIN  ← KEY CHANGE: reads role, sets is_admin, sends redirect
// ─────────────────────────────────────────────────────────────

function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJSONResponse(['success' => false, 'message' => 'Invalid request method'], 405);
    }

    $input    = isset($_POST['email'])
        ? $_POST
        : json_decode(file_get_contents('php://input'), true);

    $email    = trim($input['email']    ?? '');
    $password =      $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        sendJSONResponse(['success' => false, 'message' => 'Email and password are required'], 400);
    }

    try {
        $db         = getDatabase();
        $connection = $db->getConnection();

        // Fetch user — now includes role, first_name, last_name
        $stmt = $connection->prepare(
            "SELECT user_id, email, first_name, last_name,
                    password_hash, is_active, role
             FROM users WHERE email = ?"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !$user['is_active']) {
            sendJSONResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
        }

        if (!password_verify($password, $user['password_hash'])) {
            sendJSONResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
        }

        // Create DB session
        $sessionId = generateSessionId();
        $expiresAt = date('Y-m-d H:i:s', time() + 86400);

        $stmt = $connection->prepare(
            "INSERT INTO user_sessions (session_id, user_id, expires_at) VALUES (?, ?, ?)"
        );
        $stmt->execute([$sessionId, $user['user_id'], $expiresAt]);

        // Update last login timestamp
        $connection->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")
                   ->execute([$user['user_id']]);

        // Detect admin
        $isAdmin = ($user['role'] === 'admin');

        // Set PHP session variables
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['email']      = $user['email'];
        $_SESSION['first_name'] = $user['first_name'] ?? '';
        $_SESSION['last_name']  = $user['last_name']  ?? '';
        $_SESSION['role']       = $user['role']        ?? 'user';
        $_SESSION['is_admin']   = $isAdmin;
        $_SESSION['session_id'] = $sessionId;
        $_SESSION['logged_in']  = true;

        // Send response with redirect hint
        sendJSONResponse([
            'success'  => true,
            'message'  => 'Login successful',
            'is_admin' => $isAdmin,
            'redirect' => $isAdmin ? 'admin.php' : 'index.html',
            'user'     => [
                'user_id'    => $user['user_id'],
                'email'      => $user['email'],
                'first_name' => $user['first_name'] ?? '',
                'last_name'  => $user['last_name']  ?? '',
                'role'       => $user['role'],
            ]
        ]);

    } catch (Exception $e) {
        logError('Login failed', ['error' => $e->getMessage(), 'email' => $email]);
        sendJSONResponse(['success' => false, 'message' => 'Login failed. Please try again.'], 500);
    }
}

// ─────────────────────────────────────────────────────────────
// LOGOUT
// ─────────────────────────────────────────────────────────────

function handleLogout() {
    if (isset($_SESSION['session_id'])) {
        try {
            $db         = getDatabase();
            $connection = $db->getConnection();
            $connection->prepare("DELETE FROM user_sessions WHERE session_id = ?")
                       ->execute([$_SESSION['session_id']]);
        } catch (Exception $e) {
            logError('Logout DB cleanup failed', ['error' => $e->getMessage()]);
        }
    }

    session_unset();
    session_destroy();
    sendJSONResponse(['success' => true, 'message' => 'Logged out successfully']);
}

// ─────────────────────────────────────────────────────────────
// AUTH CHECK  ← Also returns is_admin and full user info
// ─────────────────────────────────────────────────────────────

function handleCheck() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_id'])) {
        sendJSONResponse(['success' => true, 'authenticated' => false]);
        return;
    }

    try {
        $db         = getDatabase();
        $connection = $db->getConnection();

        $stmt = $connection->prepare(
            "SELECT s.session_id, u.user_id, u.email,
                    u.first_name, u.last_name, u.role, u.is_active
             FROM user_sessions s
             JOIN users u ON s.user_id = u.user_id
             WHERE s.session_id = ?
               AND s.expires_at > NOW()
               AND u.is_active = 1"
        );
        $stmt->execute([$_SESSION['session_id']]);
        $row = $stmt->fetch();

        if (!$row) {
            sendJSONResponse(['success' => true, 'authenticated' => false]);
            return;
        }

        $isAdmin = ($row['role'] === 'admin');

        // Keep session variables in sync
        $_SESSION['is_admin']   = $isAdmin;
        $_SESSION['role']       = $row['role'];
        $_SESSION['first_name'] = $row['first_name'] ?? '';
        $_SESSION['last_name']  = $row['last_name']  ?? '';

        sendJSONResponse([
            'success'       => true,
            'authenticated' => true,
            'is_admin'      => $isAdmin,
            'user'          => [
                'user_id'    => $row['user_id'],
                'email'      => $row['email'],
                'first_name' => $row['first_name'] ?? '',
                'last_name'  => $row['last_name']  ?? '',
                'role'       => $row['role'],
            ]
        ]);

    } catch (Exception $e) {
        logError('Auth check failed', ['error' => $e->getMessage()]);
        sendJSONResponse(['success' => true, 'authenticated' => false]);
    }
}

// ─────────────────────────────────────────────────────────────
// isAdmin() — helper for other PHP pages
// ─────────────────────────────────────────────────────────────

function isAdmin() {
    initSession();
    return isset($_SESSION['is_admin'])
        && $_SESSION['is_admin'] === true
        && isset($_SESSION['role'])
        && $_SESSION['role'] === 'admin';
}

// ─────────────────────────────────────────────────────────────
// ROUTER
// ─────────────────────────────────────────────────────────────

$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'signup': handleSignup(); break;
    case 'login':  handleLogin();  break;
    case 'logout': handleLogout(); break;
    case 'check':  handleCheck();  break;
    default:
        sendJSONResponse(['success' => false, 'message' => 'Invalid action'], 400);
}
?>