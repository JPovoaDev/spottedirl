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
$target_code = $lang_map[$target] ?? 'EN';

$stmt = $pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'deepl_api_key'");
$stmt->execute();
$deepl_key = $stmt->fetchColumn();

if (!$deepl_key) {
    echo json_encode(['error' => 'Chave DeepL não configurada.']);
    exit;
}

$url = str_ends_with($deepl_key, ':fx') 
    ? 'https://api-free.deepl.com/v2/translate' 
    : 'https://api.deepl.com/v2/translate';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'text' => $text,
        'target_lang' => $target_code
    ]),
    CURLOPT_HTTPHEADER => [
        'Authorization: DeepL-Auth-Key ' . $deepl_key,
        'Content-Type: application/x-www-form-urlencoded'
    ],
    CURLOPT_TIMEOUT => 10,
]);
$res  = curl_exec($ch);
curl_close($ch);

$data       = json_decode($res, true);
$translated = $data['translations'][0]['text'] ?? null;

if (!$translated) {
    echo json_encode(['error' => 'Erro na tradução.']);
    exit;
}

echo json_encode(['translated' => $translated]);
exit;