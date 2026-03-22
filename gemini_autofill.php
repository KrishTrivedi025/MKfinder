<?php
require_once 'config.php';
require_once 'database.php';
initSession();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit;
}
header('Content-Type: application/json');

$apiKey = 'REDACTED_OPENROUTER_KEY';

if (($_POST['action'] ?? '') === 'getmodels') {
    $ch = curl_init('https://openrouter.ai/api/v1/models');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$apiKey],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res  = curl_exec($ch); curl_close($ch);
    $data = json_decode($res, true);
    $free = [];
    foreach (($data['data'] ?? []) as $m) {
        $cp = floatval($m['pricing']['completion'] ?? 1);
        $pt = floatval($m['pricing']['prompt']     ?? 1);
        if ($cp == 0 && $pt == 0) $free[] = $m['id'];
    }
    echo json_encode(['free_models' => $free]);
    exit;
}

$bird = trim($_POST['bird'] ?? '');
if (empty($bird)) { echo json_encode(['error'=>'No bird name']); exit; }

$ch = curl_init('https://openrouter.ai/api/v1/models');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$apiKey],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$res  = curl_exec($ch); curl_close($ch);
$data = json_decode($res, true);
$freeModels = [];
foreach (($data['data'] ?? []) as $m) {
    $cp = floatval($m['pricing']['completion'] ?? 1);
    $pt = floatval($m['pricing']['prompt']     ?? 1);
    if ($cp == 0 && $pt == 0) $freeModels[] = $m['id'];
}

if (empty($freeModels)) {
    echo json_encode(['error' => 'No free models found from OpenRouter']); exit;
}

$prompt = 'For the bird "' . addslashes($bird) . '", return ONLY a raw JSON object, no markdown, no backticks:
{"scientific_name":"","description":"2-3 sentences about appearance and traits","habitat":"comma separated habitats","diet":"comma separated diet","behavior":"short behavior description","conservation_status":"one of: Least Concern, Near Threatened, Vulnerable, Endangered, Critically Endangered, Extinct in the Wild, Extinct","characteristics":"5 key physical traits one per line"}';

$url = 'https://openrouter.ai/api/v1/chat/completions';
$lastError = '';

foreach ($freeModels as $model) {
    $body = json_encode(['model'=>$model,'messages'=>[['role'=>'user','content'=>$prompt]],'max_tokens'=>600]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json','Authorization: Bearer '.$apiKey,'HTTP-Referer: http://localhost','X-Title: MKfinder'],
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $d = json_decode($response, true);
        $lastError = $d['error']['message'] ?? 'HTTP '.$httpCode;
        continue;
    }
    $d   = json_decode($response, true);
    $raw = $d['choices'][0]['message']['content'] ?? '';
    $raw = trim(preg_replace('/^```[a-zA-Z]*
?|
?```$/m', '', $raw));
    preg_match('/{.*}/s', $raw, $m2);
    $info = json_decode($m2[0] ?? $raw, true);
    if ($info && isset($info['scientific_name'])) {
        echo json_encode($info); exit;
    }
    $lastError = 'Bad JSON from '.$model;
}
echo json_encode(['error' => $lastError]);