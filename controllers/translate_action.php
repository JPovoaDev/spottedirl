<?php
// endpoint AJAX para tradução via DeepL — chamado com fetch() nas views de detalhe de um spot
session_start();
require_once '../db.php';
require_once '../auth.php';

header('Content-Type: application/json');

// só utilizadores autenticados podem consumir a API DeepL
if (!has_role('user')) {
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

// buscar a chave DeepL guardada pelo admin na system_config
$cfg = $pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'deepl_api_key'");
$cfg->execute();
$row = $cfg->fetch(PDO::FETCH_ASSOC);
$api_key = $row['config_value'] ?? '';

if (!$api_key) {
    echo json_encode(['error' => 'API de tradução não configurada.']);
    exit;
}

// chamada à API DeepL Free
$ch = curl_init('https://api-free.deepl.com/v2/translate');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'auth_key'    => $api_key,
        'text'        => $text,
        'target_lang' => strtoupper($target),
    ]),
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