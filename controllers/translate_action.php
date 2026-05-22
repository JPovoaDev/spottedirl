<?php
session_start();
require_once '../db.php';
require_once '../auth.php';

// basicamente esta página não envia nenhum código html para ser mostrado e não enviamos o utilizador para lado nenhum
// apenas enviamos uma lista de informações em json para ser usado pelo js quando o user quer traduzir um spot (ver os spot.php)
header('Content-Type: application/json');

// para não estarmos a abusar da API fazemos com que seja obrigatório estar autenticado e evitamos bots
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Autenticação necessária.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método inválido.']);
    exit;
}

$text = trim($_POST['text'] ?? ''); // as palavras/descrição em português que o js quer que traduzamos
$target = trim($_POST['target_lang'] ?? 'EN'); // a língua final (Inglês, Espanhol...) em que a pessoa clicou no botão para traduzir

if (!$text) {
    echo json_encode(['error' => 'Texto vazio.']);
    exit;
}

// mapa de códigos para o formato estrito que a API do MyMemory pede (em minusculas)
$lang_map = [
    'EN' => 'en',
    'ES' => 'es',
    'FR' => 'fr',
    'DE' => 'de',
    'IT' => 'it',
];
$target_code = $lang_map[$target] ?? 'en';

// o urlencode serve para que textos com espaços, pontos ou interrogações não quebrem o URL (são convertidos)
$url = 'https://api.mymemory.translated.net/get?q='.urlencode($text).'&langpair=pt|'.$target_code;

// o cURL é uma ferramenta do PHP que vai visitar o site da API no URL a fingir que somos nós
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, // isto diz ao cURL para guardar na variável o texto que o site respondeu
    CURLOPT_TIMEOUT => 10, // não queremos que o js fique infinitamente à espera
]);
$res = curl_exec($ch); // executamos a visita ao site do mymemory e guardamos no $res a resposta deles 
curl_close($ch);

// a resposta ($res) vem no formato JSON, o json_decode converte para um array de PHP
$data = json_decode($res, true);
$translated = $data['responseData']['translatedText'] ?? null; // usamos a keys predefinidas deles para apanhar a resposta

if (!$translated) {
    echo json_encode(['error' => 'Erro na tradução.']);
    exit;
}

// se chegamos até aqui está tudo bem, mostramos o texto traduzido
echo json_encode(['translated' => $translated]);
exit;