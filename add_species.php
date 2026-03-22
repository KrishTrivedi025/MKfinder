<?php
/**
 * MKfinder — add_species.php
 * Saves a user-submitted unknown bird to the database
 * NEW FILE — place in BirdImageRecognizer/ folder
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

initSession();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}

if (!isLoggedIn()) {
    sendJSONResponse(['success' => false, 'message' => 'Please log in first.'], 401);
}

try {
    $pdo = getDatabase()->getConnection();

    // ── Read fields (ALL optional except image) ───────────────
    $birdName       = trim($_POST['bird_name']       ?? '');
    $scientificName = trim($_POST['scientific_name'] ?? '');
    $description    = trim($_POST['description']     ?? '');
    $habitat        = trim($_POST['habitat']         ?? '');
    $userId         = $_SESSION['user_id'] ?? null;

    // If no name given, use a placeholder
    if (empty($birdName)) {
        $birdName = 'Unknown Bird ' . date('Y-m-d H:i');
    }

    // ── Handle image upload ───────────────────────────────────
    $savedFilename = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file   = $_FILES['image'];
        $errors = validateFileUpload($file);

        if (empty($errors)) {
            $savedFilename = generateUniqueFilename($file['name']);
            $savePath      = UPLOAD_DIR . $savedFilename;
            if (!move_uploaded_file($file['tmp_name'], $savePath)) {
                $savedFilename = null;
            }
        }
    }

    // ── Check if already submitted ────────────────────────────
    if (!empty($birdName)) {
        $check = $pdo->prepare("SELECT id FROM unknown_submissions WHERE bird_name = ? AND status='pending' LIMIT 1");
        $check->execute([$birdName]);
        if ($check->fetch()) {
            sendJSONResponse([
                'success' => false,
                'message' => "A request for '{$birdName}' is already pending admin review.",
            ]);
        }
    }

    // ── Save ONLY to unknown_submissions (NOT to species table) ──
    // Species will only appear in the public database after admin approves

    // ── Also save to uploads table if image provided ──────────
    if ($savedFilename) {
        $db = getDatabase();
        $db->saveUpload([
            'upload_id'     => 'upload_' . uniqid('', true),
            'user_id'       => $userId,
            'filename'      => $savedFilename,
            'original_name' => $_FILES['image']['name'],
            'file_size'     => $_FILES['image']['size'],
            'mime_type'     => $_FILES['image']['type'],
            'file_path'     => UPLOAD_DIR . $savedFilename,
            'status'        => 'unknown_submission',
        ]);
    }

    // ── Save to unknown_submissions for admin review ──────────
    $pdo->prepare("
        INSERT INTO unknown_submissions
            (user_id, bird_name, scientific_name, description, habitat, image_filename, status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ")->execute([
        $userId,
        $birdName,
        $scientificName ?: null,
        $description    ?: null,
        $habitat        ?: null,
        $savedFilename  ?: null,
    ]);

    sendJSONResponse([
        'success' => true,
        'message' => "Thank you! Your submission for '{$birdName}' has been sent to the admin for review. It will appear in the species database once approved.",
        'bird_name' => $birdName,
    ]);

} catch (Exception $e) {
    logError('add_species.php error', ['error' => $e->getMessage()]);
    sendJSONResponse(['success' => false, 'message' => 'Failed to save. Please try again.'], 500);
}
?>