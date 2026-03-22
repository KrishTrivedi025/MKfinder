<?php
// check_cache.php
// http://localhost/BirdImageRecognizer/check_cache.php
echo "<pre style='background:#1a1a1a;color:#74c69d;padding:20px;font-size:13px;'>";
echo "=== Cache & Offline Check ===\n\n";

$python = '"C:\\Users\\Krrish Trivedi\\AppData\\Local\\Programs\\Python\\Python311\\python.exe"';

// Check HuggingFace cache
$checkScript = <<<'PY'
import os, json
cache_dir = os.path.expanduser("~/.cache/huggingface/hub")
model_name = "prithivMLmods/Bird-Species-Classifier-526"
model_folder = "models--" + model_name.replace("/", "--")
model_path = os.path.join(cache_dir, model_folder)

result = {
    "cache_dir_exists": os.path.exists(cache_dir),
    "cache_dir": cache_dir,
    "model_cached": os.path.exists(model_path),
    "model_path": model_path,
    "cache_size_mb": 0
}

if os.path.exists(model_path):
    total = 0
    for root, dirs, files in os.walk(model_path):
        for f in files:
            try: total += os.path.getsize(os.path.join(root, f))
            except: pass
    result["cache_size_mb"] = round(total / 1024 / 1024, 1)

# Test offline load
try:
    os.environ["TRANSFORMERS_OFFLINE"] = "1"
    os.environ["HF_HUB_OFFLINE"] = "1"
    from transformers import AutoImageProcessor, SiglipForImageClassification
    import warnings; warnings.filterwarnings("ignore")
    import logging; logging.getLogger("transformers").setLevel(logging.ERROR)
    processor = AutoImageProcessor.from_pretrained(model_name, local_files_only=True)
    model = SiglipForImageClassification.from_pretrained(model_name, local_files_only=True)
    result["offline_load"] = "SUCCESS"
except Exception as e:
    result["offline_load"] = f"FAILED: {str(e)[:100]}"

print(json.dumps(result))
PY;

$tmpFile = __DIR__ . '/tmp_cache_check.py';
file_put_contents($tmpFile, $checkScript);
$cmd = $python . ' "' . $tmpFile . '" 2>&1';
$out = shell_exec($cmd);
@unlink($tmpFile);

$jsonStart = strpos($out ?? '', '{');
if ($jsonStart !== false) {
    $data = json_decode(substr($out, $jsonStart), true);
    if ($data) {
        echo "HuggingFace cache dir: " . $data['cache_dir'] . "\n";
        echo "Cache dir exists: " . ($data['cache_dir_exists'] ? 'YES' : 'NO') . "\n";
        echo "Model cached: " . ($data['model_cached'] ? 'YES' : 'NO') . "\n";
        echo "Cache size: " . $data['cache_size_mb'] . " MB\n";
        echo "Offline load test: " . $data['offline_load'] . "\n\n";

        if ($data['offline_load'] === 'SUCCESS') {
            echo "✓ Model will work OFFLINE — no internet needed!\n";
        } else {
            echo "✗ Offline load FAILED — see error above\n";
            echo "  Run this command to fix:\n";
            echo "  python -c \"from transformers import AutoImageProcessor,SiglipForImageClassification; AutoImageProcessor.from_pretrained('prithivMLmods/Bird-Species-Classifier-526'); SiglipForImageClassification.from_pretrained('prithivMLmods/Bird-Species-Classifier-526')\"\n";
        }
    }
} else {
    echo "Raw output:\n" . htmlspecialchars($out ?? 'null');
}

echo "\n=== END ===\n";
echo "</pre>";
?>
