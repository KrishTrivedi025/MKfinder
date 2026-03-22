<?php
// test_timeout.php
// Open: http://localhost/BirdImageRecognizer/test_timeout.php
echo "<pre style='background:#1a1a1a;color:#74c69d;padding:20px;font-size:13px;'>";
echo "=== XAMPP PHP Settings ===\n\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "max_input_time: " . ini_get('max_input_time') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "disable_functions: " . (ini_get('disable_functions') ?: 'none') . "\n\n";

// Test: can PHP run for 15 seconds?
echo "Testing 15 second execution...\n";
flush();
$start = time();
sleep(15);
$took = time() - $start;
echo "Completed in {$took}s — PHP timeout is NOT the issue\n\n";

// Test: run python quickly
$python = '"C:\\Users\\Krrish Trivedi\\AppData\\Local\\Programs\\Python\\Python311\\python.exe"';
$cmd = $python . ' -c "import json; print(json.dumps({\'test\':\'ok\'}))" 2>&1';
$out = shell_exec($cmd);
echo "Python quick test: " . trim($out ?? 'NULL') . "\n\n";

// Test: run predict.py with a small test
$script = __DIR__ . '/predict.py';
$uploads = glob(__DIR__ . '/uploads/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
if ($uploads) {
    $img = $uploads[0];
    echo "Testing predict.py with: " . basename($img) . "\n";
    $cmd2 = $python . ' "' . $script . '" "' . $img . '" 2>&1';
    echo "Command: $cmd2\n";
    flush();
    $t = microtime(true);
    $out2 = shell_exec($cmd2);
    $took2 = round(microtime(true) - $t, 1);
    echo "Took: {$took2}s\n";
    echo "Output length: " . strlen($out2 ?? '') . " chars\n";
    // Find JSON
    $js = strrpos($out2 ?? '', '{"success"');
    if ($js !== false) {
        $decoded = json_decode(substr($out2, $js), true);
        echo "JSON decoded: " . ($decoded ? 'YES' : 'NO') . "\n";
        echo "success: " . ($decoded['success'] ? 'true' : 'false') . "\n";
        echo "species: " . ($decoded['species'] ?? 'N/A') . "\n";
        echo "mode: " . ($decoded['mode'] ?? 'N/A') . "\n";
    } else {
        echo "NO JSON FOUND in output\n";
        echo "Raw output:\n" . htmlspecialchars(substr($out2 ?? '', 0, 500)) . "\n";
    }
}
echo "\n=== END ===\n";
echo "</pre>";
?>
