<?php
// Catch ALL PHP errors and return as JSON — never let PHP show HTML error page
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>'PHP Error: '.$errstr.' in '.basename($errfile).' line '.$errline,'error_code'=>'PHP_ERROR']);
    exit;
});
set_exception_handler(function($e) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>'Exception: '.$e->getMessage().' in '.basename($e->getFile()).' line '.$e->getLine(),'error_code'=>'PHP_EXCEPTION']);
    exit;
});

require_once 'config.php';
require_once 'database.php';

initSession();

// Force max execution time BEFORE anything else
set_time_limit(300);
ini_set('max_execution_time', 300);
ini_set('default_socket_timeout', 300);

// Disable output buffering so response sends immediately
if (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Connection: keep-alive');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']); exit;
}

$startTime = microtime(true);

// ── 1. Validate file ──────────────────────────────────────────
if (empty($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'No image file provided.']); exit;
}

$file   = $_FILES['image'];
$errors = validateFileUpload($file);
if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => $errors[0]]); exit;
}

// ── 2. Save file ──────────────────────────────────────────────
$uniqueName = generateUniqueFilename($file['name']);
$savePath   = UPLOAD_DIR . $uniqueName;

if (!move_uploaded_file($file['tmp_name'], $savePath)) {
    echo json_encode(['success' => false, 'message' => 'Upload failed.']); exit;
}

$imageHash = md5_file($savePath);

// ── 3. Run Python AI ──────────────────────────────────────────
$pythonExact = 'C:/Users/Krrish Trivedi/AppData/Local/Programs/Python/Python311/python.exe';
$pythonCmd   = file_exists($pythonExact)
    ? '"' . str_replace('/', '\\', $pythonExact) . '"'
    : 'python';

$predictScript = __DIR__ . '/predict.py';
$command       = $pythonCmd . ' ' . escapeshellarg($predictScript) . ' ' . escapeshellarg($savePath) . ' 2>&1';
$rawOutput     = shell_exec($command) ?? '';

// Extract JSON — find last {"success" in output
$jsonStart = strrpos($rawOutput, '{"success"');
if ($jsonStart === false) $jsonStart = strrpos($rawOutput, '{');
$pyResult = null;
if ($jsonStart !== false) {
    $pyResult = json_decode(substr($rawOutput, $jsonStart), true);
}

// ── 4. Process result ─────────────────────────────────────────
$species    = 'Unknown';
$confidence = 0.0;
$allScores  = [];
$mode       = 'ai_model';
$isUnknown  = true;

if ($pyResult) {
    $pySuccess   = $pyResult['success']    ?? false;
    $pyMode      = $pyResult['mode']       ?? '';
    $pyErrorCode = $pyResult['error_code'] ?? '';

    // NOT A BIRD — check both error_code AND mode
    $isNotBird = (!$pySuccess && $pyErrorCode === 'NOT_A_BIRD')
              || (!$pySuccess && $pyMode === 'not_a_bird')
              || ($pyMode === 'not_a_bird');

    if ($isNotBird) {
        if (file_exists($savePath)) @unlink($savePath);
        echo json_encode([
            'success'    => false,
            'message'    => $pyResult['error'] ?? 'This does not appear to be a bird photo. Please upload a clear bird photo.',
            'error_code' => 'NOT_A_BIRD'
        ]);
        exit;
    }

    if ($pySuccess) {
        $species    = $pyResult['species']    ?? 'Unknown';
        $confidence = round($pyResult['confidence'] ?? 0, 1);
        $allScores  = $pyResult['all_scores'] ?? [];
        $mode       = $pyMode ?: 'ai_model';
        $isUnknown  = ($species === 'Unknown' || empty($species));
    }
}

// ── 5. Override check (only if unknown) ───────────────────────
if ($isUnknown) {
    try {
        $conn  = getDatabase()->getConnection();
        $stmt  = $conn->prepare("SELECT species_name FROM prediction_overrides WHERE image_hash = ? LIMIT 1");
        $stmt->execute([$imageHash]);
        $ov = $stmt->fetch();
        if ($ov) {
            $species    = $ov['species_name'];
            $confidence = 100.0;
            $allScores  = [$species => 100.0];
            $mode       = 'override';
            $isUnknown  = false;
        }
    } catch (Exception $e) { /* skip */ }
}

// ── 6. Get species info ───────────────────────────────────────
$info = getHardcodedInfo($species);
if (!$isUnknown && !empty($species) && $species !== 'Unknown') {
    try {
        $dbInfo = getSpeciesInfoFromDB($species);
        if ($dbInfo) $info = $dbInfo;
    } catch (Exception $e) { /* use hardcoded */ }
}

// ── 7. Save to DB (never blocks response) ─────────────────────
$processingMs = (int)round((microtime(true) - $startTime) * 1000);
$uploadId     = 'upload_' . uniqid('', true);
$identId      = 'id_'     . uniqid('', true);
$userId       = $_SESSION['user_id'] ?? null;
$origName     = sanitizeInput($file['name']);
$location     = substr(trim($_POST['location'] ?? ''), 0, 120);

try {
    $db = getDatabase();
    $db->saveUpload([
        'upload_id' => $uploadId, 'user_id' => $userId,
        'filename' => $uniqueName, 'original_name' => $origName,
        'file_size' => $file['size'], 'mime_type' => $file['type'],
        'file_path' => $savePath,
        'status' => $isUnknown ? 'unknown' : 'identified',
    ]);
    $db->saveIdentification([
        'identification_id' => $identId, 'upload_id' => $uploadId,
        'user_id' => $userId, 'filename' => $uniqueName,
        'original_name' => $origName,
        'species_name' => $isUnknown ? 'Unknown' : $species,
        'confidence' => $confidence, 'file_size' => $file['size'],
        'processing_time_ms' => $processingMs,
        'location' => $location ?: null,
    ]);
} catch (Exception $e) {
    logError('DB save failed', ['error' => $e->getMessage()]);
}

// ── 8. Send response ──────────────────────────────────────────
if ($isUnknown) {
    echo json_encode([
        'success' => true, 'unknown' => true,
        'message' => 'Bird species not recognised.',
        'confidence' => $confidence,
        'all_scores' => $allScores,
        'mode' => $mode,
        'processing_time_ms' => $processingMs,
        'image_url' => 'uploads/' . $uniqueName,
        'identification_id' => $identId,
        'image_hash' => $imageHash,
    ]);
} else {
    echo json_encode([
        'success' => true, 'unknown' => false,
        'message' => 'Bird identified successfully!',
        'species' => $species,
        'confidence' => $confidence,
        'all_scores' => $allScores,
        'mode' => $mode,
        'species_info' => [
            'name'                => $info['name']                ?? $species,
            'scientific_name'     => $info['scientific_name']     ?? '',
            'description'         => $info['description']         ?? '',
            'characteristics'     => $info['characteristics']     ?? [],
            'habitat'             => $info['habitat']             ?? '',
            'diet'                => $info['diet']                ?? '',
            'behavior'            => $info['behavior']            ?? '',
            'conservation_status' => $info['conservation_status'] ?? 'Least Concern',
        ],
        'processing_time_ms' => $processingMs,
        'image_url' => 'uploads/' . $uniqueName,
    ]);
}
exit;

// ── HELPER FUNCTIONS ─────────────────────────────────────────

function getHardcodedInfo($name) {
    $data = [
        'American Robin' => [
            'name' => 'American Robin', 'scientific_name' => 'Turdus migratorius',
            'description' => 'The American Robin is a migratory songbird with a distinctive reddish-orange breast and gray back.',
            'characteristics' => ['Red-orange breast and belly','Dark gray to black head','White markings around eyes','Yellow beak','Length: 8-11 inches','Wingspan: 12-16 inches'],
            'habitat' => 'Woodlands, parks, gardens, and lawns across North America',
            'diet' => 'Insects, earthworms, fruits, and berries',
            'behavior' => 'Known for pulling earthworms from lawns, territorial during breeding season',
            'conservation_status' => 'Least Concern',
        ],
        'Blue Grosbeak' => [
            'name' => 'Blue Grosbeak', 'scientific_name' => 'Passerina caerulea',
            'description' => 'The Blue Grosbeak is a medium-sized songbird with striking deep blue plumage in males.',
            'characteristics' => ['Males: Deep blue plumage','Females: Rich brown with blue wings','Large triangular bill','Length: 5.9-6.3 inches','Wingspan: 11 inches'],
            'habitat' => 'Open areas, brushy fields, woodland edges',
            'diet' => 'Seeds, insects, grains, wild fruits',
            'behavior' => 'Males sing from exposed perches, tail-twitching habit',
            'conservation_status' => 'Least Concern',
        ],
        'Northern Cardinal' => [
            'name' => 'Northern Cardinal', 'scientific_name' => 'Cardinalis cardinalis',
            'description' => 'Males display brilliant red plumage while females show warm brown with red accents.',
            'characteristics' => ['Males: Brilliant red with black mask','Females: Brown with red tinges','Orange-red cone beak','Length: 8.5-9 inches','Wingspan: 9.8-12.2 inches'],
            'habitat' => 'Woodlands, gardens, shrublands',
            'diet' => 'Seeds, grains, fruits, insects',
            'behavior' => 'Non-migratory, both males and females sing',
            'conservation_status' => 'Least Concern',
        ],
    ];
    return $data[$name] ?? [
        'name' => $name ?: 'Unknown', 'scientific_name' => '',
        'description' => '', 'characteristics' => [],
        'habitat' => '', 'diet' => '', 'behavior' => '',
        'conservation_status' => 'Least Concern',
    ];
}
?>