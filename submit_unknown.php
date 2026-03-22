<?php
require_once 'config.php';
require_once 'database.php';

initSession();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

if (!isLoggedIn()) {
    sendJSONResponse(['success' => false, 'message' => 'Please log in to submit a review request.', 'error_code' => 'AUTH_REQUIRED'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

try {
    $conn = getDatabase()->getConnection();
    $userId = $_SESSION['user_id'];

    $identificationId  = trim($_POST['identification_id']  ?? '');
    $imageFilename     = trim($_POST['image_filename']     ?? '');
    $suggestedSpecies  = trim($_POST['suggested_species']  ?? '');
    $userNote          = trim($_POST['user_note']          ?? '');
    $imageHash         = trim($_POST['image_hash']         ?? '');

    if (empty($identificationId) || empty($imageFilename)) {
        sendJSONResponse(['success' => false, 'message' => 'Missing required fields.'], 400);
    }

    // Check if user already submitted this exact identification
    $dupCheck = $conn->prepare(
        "SELECT id FROM unknown_submissions
         WHERE identification_id = ? AND user_id = ? LIMIT 1"
    );
    $dupCheck->execute([$identificationId, $userId]);
    if ($dupCheck->fetch()) {
        sendJSONResponse(['success' => false, 'message' => 'You have already submitted this image for review.'], 400);
    }

    // Verify this identification actually belongs to this user
    $verify = $conn->prepare(
        "SELECT identification_id, filename FROM identifications
         WHERE identification_id = ? AND user_id = ? LIMIT 1"
    );
    $verify->execute([$identificationId, $userId]);
    $identRow = $verify->fetch();

    if (!$identRow) {
        sendJSONResponse(['success' => false, 'message' => 'Identification not found.'], 404);
    }

    $filename = $identRow['filename'];

    // Save to unknown_submissions
    $stmt = $conn->prepare(
        "INSERT INTO unknown_submissions
            (user_id, image_filename, suggested_species, user_note,
             identification_id, status, submitted_at)
         VALUES (?, ?, ?, ?, ?, 'pending', NOW())"
    );
    $stmt->execute([
        $userId,
        $filename,
        $suggestedSpecies ?: null,
        $userNote         ?: null,
        $identificationId,
    ]);

    sendJSONResponse([
        'success' => true,
        'message' => 'Your submission has been sent to the admin for review. Thank you for helping improve MKfinder!',
    ]);

} catch (Exception $e) {
    logError('submit_unknown failed', ['error' => $e->getMessage()]);
    sendJSONResponse(['success' => false, 'message' => 'Submission failed. Please try again.'], 500);
}
?>
