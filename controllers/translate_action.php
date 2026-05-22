<?php
session_start();
require_once '../db.php';
require_once '../auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Autenticação necessária.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método inválido.']);
    exit;
}

$text   = trim($_POST['text']        ?? '');
$target = trim($_POST['target_lang'] ?? 'EN');

if (!$text) {
    echo json_encode(['error' => 'Texto vazio.']);
    exit;
}

// mapa de códigos para o formato do MyMemory (pt|en, pt|es, etc)
$lang_map = [
    'EN' => 'en',
    'ES' => 'es',
    'FR' => 'fr',
    'DE' => 'de',
    'IT' => 'it',
];
$target_code = $lang_map[$target] ?? 'en';

$url = 'https://api.mymemory.translated.net/get?q=' . urlencode($text) . '&langpair=pt|' . $target_code;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
]);
$res  = curl_exec($ch);
curl_close($ch);

$data       = json_decode($res, true);
$translated = $data['responseData']['translatedText'] ?? null;

if (!$translated) {
    echo json_encode(['error' => 'Erro na tradução.']);
    exit;
}

echo json_encode(['translated' => $translated]);
exit;