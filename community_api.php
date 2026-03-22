<?php
require_once 'config.php';
require_once 'database.php';
initSession();
header('Content-Type: application/json');

$conn   = getDatabase()->getConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$uid    = $_SESSION['user_id'] ?? null;
$isAdmin = $_SESSION['is_admin'] ?? false;

if ($action === 'feed') {
    $offset = intval($_GET['offset'] ?? 0);
    $sort   = $_GET['sort'] ?? 'latest';

    // Choose ORDER BY based on sort param
    if ($sort === 'liked') {
        $orderBy = 'likes DESC, i.identification_time DESC';
    } elseif ($sort === 'commented') {
        $orderBy = 'comments DESC, i.identification_time DESC';
    } else {
        $orderBy = 'i.identification_time DESC';
    }

    $rows = $conn->prepare("
        SELECT i.identification_id, i.filename, i.species_name, i.confidence,
               i.identification_time, i.location,
               u.first_name, u.last_name,
               (SELECT COUNT(*) FROM feed_likes l WHERE l.identification_id = i.identification_id) AS likes,
               (SELECT COUNT(*) FROM feed_comments c WHERE c.identification_id = i.identification_id) AS comments,
               (SELECT COUNT(*) FROM feed_likes l2 WHERE l2.identification_id = i.identification_id AND l2.user_id = ?) AS user_liked
        FROM identifications i
        LEFT JOIN users u ON i.user_id = u.user_id
        WHERE i.filename IS NOT NULL
          AND i.species_name IS NOT NULL
          AND i.species_name != ''
          AND i.confidence >= 0
        ORDER BY $orderBy
        LIMIT 10 OFFSET ?
    ");
    $rows->execute([$uid ?? '', $offset]);
    echo json_encode(['success' => true, 'data' => $rows->fetchAll()]);
    exit;
}

if ($action === 'like') {
    if (!$uid) { echo json_encode(['success'=>false,'error'=>'Login required']); exit; }
    $id = $_POST['identification_id'] ?? '';
    $check = $conn->prepare("SELECT id FROM feed_likes WHERE identification_id=? AND user_id=?");
    $check->execute([$id, $uid]);
    if ($check->fetch()) {
        $conn->prepare("DELETE FROM feed_likes WHERE identification_id=? AND user_id=?")->execute([$id,$uid]);
        $liked = false;
    } else {
        $conn->prepare("INSERT INTO feed_likes (identification_id,user_id) VALUES (?,?)")->execute([$id,$uid]);
        $liked = true;
    }
    $count = $conn->prepare("SELECT COUNT(*) FROM feed_likes WHERE identification_id=?");
    $count->execute([$id]);
    echo json_encode(['success'=>true,'liked'=>$liked,'count'=>(int)$count->fetchColumn()]);
    exit;
}

if ($action === 'comments') {
    $id = $_GET['identification_id'] ?? '';
    $rows = $conn->prepare("
        SELECT fc.id, fc.comment, fc.created_at, fc.user_id,
               u.first_name, u.last_name
        FROM feed_comments fc
        LEFT JOIN users u ON fc.user_id = u.user_id
        WHERE fc.identification_id = ?
        ORDER BY fc.created_at ASC
    ");
    $rows->execute([$id]);
    echo json_encode(['success'=>true,'data'=>$rows->fetchAll()]);
    exit;
}

if ($action === 'comment_add') {
    if (!$uid) { echo json_encode(['success'=>false,'error'=>'Login required']); exit; }
    $id  = $_POST['identification_id'] ?? '';
    $txt = trim($_POST['comment'] ?? '');
    if (empty($txt) || strlen($txt) > 500) { echo json_encode(['success'=>false,'error'=>'Invalid comment']); exit; }
    $conn->prepare("INSERT INTO feed_comments (identification_id,user_id,comment) VALUES (?,?,?)")->execute([$id,$uid,$txt]);
    $newId = $conn->lastInsertId();
    $row = $conn->prepare("SELECT fc.*,u.first_name,u.last_name FROM feed_comments fc LEFT JOIN users u ON fc.user_id=u.user_id WHERE fc.id=?");
    $row->execute([$newId]);
    echo json_encode(['success'=>true,'comment'=>$row->fetch()]);
    exit;
}

if ($action === 'comment_delete') {
    if (!$uid) { echo json_encode(['success'=>false,'error'=>'Login required']); exit; }
    $cid = intval($_POST['comment_id'] ?? 0);
    $row = $conn->prepare("SELECT user_id FROM feed_comments WHERE id=?");
    $row->execute([$cid]);
    $c = $row->fetch();
    if (!$c) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }
    if ($c['user_id'] !== $uid && !$isAdmin) { echo json_encode(['success'=>false,'error'=>'Not allowed']); exit; }
    $conn->prepare("DELETE FROM feed_comments WHERE id=?")->execute([$cid]);
    echo json_encode(['success'=>true]);
    exit;
}

if ($action === 'delete_card') {
    // Check admin — use isAdmin() helper or session directly
    $adminCheck = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true)
               || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
    if (!$adminCheck) { echo json_encode(['success'=>false,'error'=>'Admin only']); exit; }

    $id = $_POST['identification_id'] ?? '';
    if (empty($id)) { echo json_encode(['success'=>false,'error'=>'Missing ID']); exit; }
    try {
        // Step 1: Get filename AND upload_id BEFORE deleting anything
        $row = $conn->prepare("SELECT filename, upload_id FROM identifications WHERE identification_id=?");
        $row->execute([$id]);
        $rec = $row->fetch();

        // Step 2: Delete likes and comments
        $conn->prepare("DELETE FROM feed_likes WHERE identification_id=?")->execute([$id]);
        $conn->prepare("DELETE FROM feed_comments WHERE identification_id=?")->execute([$id]);

        // Step 3: Delete identification record
        $conn->prepare("DELETE FROM identifications WHERE identification_id=?")->execute([$id]);

        // Step 4: Delete upload record using upload_id we saved earlier
        if ($rec && !empty($rec['upload_id'])) {
            $conn->prepare("DELETE FROM uploads WHERE upload_id=?")->execute([$rec['upload_id']]);
        }

        // Step 5: Delete image file
        if ($rec && !empty($rec['filename'])) {
            $filePath = __DIR__ . '/uploads/' . $rec['filename'];
            if (file_exists($filePath)) @unlink($filePath);
        }

        echo json_encode(['success'=>true]);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

echo json_encode(['success'=>false,'error'=>'Unknown action']);