<?php
require_once 'config.php';
require_once 'database.php';

initSession();

header('Content-Type: application/json');

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    sendJSONResponse(['success' => false, 'message' => 'Admin access required.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['success' => false, 'message' => 'POST required.'], 405);
}

set_time_limit(600);
ini_set('max_execution_time', 600);

try {
    $conn  = getDatabase()->getConnection();
    $count = $conn->query("SELECT COUNT(*) FROM training_images WHERE used_in_training=0")->fetchColumn();

    if ($count == 0) {
        sendJSONResponse(['success' => false, 'message' => 'No training images queued. Go to Submissions tab and click "Use for AI Training" first.']);
    }

    $scriptPath = __DIR__ . '/finetune.py';

    if (!file_exists($scriptPath)) {
        sendJSONResponse(['success' => false, 'message' => 'finetune.py not found in project folder. Please copy it there.']);
    }

    // ── Exact Python path for this machine ───────────────────────
    $pythonExact = 'C:\Users\Krrish Trivedi\AppData\Local\Programs\Python\Python311\python.exe';

    // Try exact path first, then fallbacks
    $pythonCmd = '';
    if (file_exists($pythonExact)) {
        $pythonCmd = '"' . $pythonExact . '"';
    } else {
        // Fallback: try common python commands
        $candidates = ['python3', 'python', 'py'];
        foreach ($candidates as $candidate) {
            $test = shell_exec("{$candidate} --version 2>&1");
            if ($test && stripos($test, 'Python 3') !== false) {
                $pythonCmd = $candidate;
                break;
            }
        }
    }

    if (empty($pythonCmd)) {
        sendJSONResponse([
            'success' => false,
            'message' => 'Python not found. Open CMD in your project folder and run: python finetune.py',
        ]);
    }

    $escaped = escapeshellarg($scriptPath);
    $command = "{$pythonCmd} {$escaped} 2>&1";

    // Run finetune.py and capture full output
    $lines  = [];
    $return = 0;
    exec($command, $lines, $return);
    $output = implode("\n", $lines);

    if (empty(trim($output))) {
        $output = shell_exec($command) ?? '';
    }

    if (empty(trim($output))) {
        sendJSONResponse([
            'success' => false,
            'output'  => "Python path used: {$pythonCmd}\nScript: {$scriptPath}\nNo output received.",
            'message' => 'Python ran but produced no output. Try manually: open CMD in your project folder → python finetune.py',
        ]);
    }

    $success = strpos($output, 'Fine-tuning complete') !== false
            || strpos($output, 'Training complete')    !== false;

    sendJSONResponse([
        'success' => $success,
        'output'  => $output,
        'message' => $success
            ? 'Fine-tuning completed successfully!'
            : 'Script ran — check output for details.',
    ]);

} catch (Exception $e) {
    logError('trigger_training failed', ['error' => $e->getMessage()]);
    sendJSONResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
?>