<?php
require_once 'config.php';
require_once 'database.php';

initSession();
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Login required.']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
}

$identificationId = trim($_POST['identification_id'] ?? '');
$userId           = $_SESSION['user_id'];

if (empty($identificationId)) {
    echo json_encode(['success' => false, 'message' => 'Missing identification ID.']); exit;
}

try {
    $conn = getDatabase()->getConnection();

    // Verify this record belongs to this user
    $stmt = $conn->prepare(
        "SELECT identification_id, filename FROM identifications
         WHERE identification_id = ? AND user_id = ? LIMIT 1"
    );
    $stmt->execute([$identificationId, $userId]);
    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Record not found.']); exit;
    }

    // Delete from identifications
    $conn->prepare("DELETE FROM identifications WHERE identification_id = ? AND user_id = ?")
         ->execute([$identificationId, $userId]);

    // Delete from uploads too
    $conn->prepare("DELETE FROM uploads WHERE upload_id IN (
        SELECT upload_id FROM identifications WHERE identification_id = ?
    )")->execute([$identificationId]);

    // Delete from community likes/comments if exists
    try {
        $conn->prepare("DELETE FROM feed_likes    WHERE identification_id = ?")
             ->execute([$identificationId]);
        $conn->prepare("DELETE FROM feed_comments WHERE identification_id = ?")
             ->execute([$identificationId]);
    } catch (Exception $e) { /* tables may not exist */ }

    // Delete image file
    if (!empty($row['filename'])) {
        $filePath = UPLOAD_DIR . $row['filename'];
        if (file_exists($filePath)) @unlink($filePath);
    }

    echo json_encode(['success' => true, 'message' => 'Record deleted.']);

} catch (Exception $e) {
    logError('delete_identification failed', ['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'Delete failed. Please try again.']);
}
?>
