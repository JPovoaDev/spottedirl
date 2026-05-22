<?php
session_start();
require_once '../db.php';
require_once '../auth.php';

// como só os simpatizantes é que fazem upload verificamos a role do user 
require_role('simpatizante');

// se tentarem aceder a esta página sem ser por POST, ou seja, sem submeter o formulário, redirecionamos para o upload.php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/simpatizante/upload.php');
    exit;
}

// lemos todos os campos do formulário
$descricao = trim($_POST['descricao'] ?? '');
$visibilidade = $_POST['visibilidade'] ?? 'privado';
$localizacao = trim($_POST['localizacao'] ?? '');
$hora_do_dia = $_POST['hora_do_dia'] ?? '';
$raridade = $_POST['raridade'] ?? 'comum';
$cat_principal = (int)($_POST['categoria_principal'] ?? 0);
$cat_secundaria = (int)($_POST['categoria_secundaria'] ?? 0);

// se a descrição vier vazia ou se não vier algum ficheiro ou se vier um ficheiro com um erro no upload
// aparece um erro e redireciona para o upload.php
if (!$descricao || !isset($_FILES['ficheiro']) || $_FILES['ficheiro']['error'] !== 0) {
    $_SESSION['error'] = 'Descrição e ficheiro são obrigatórios.';
    header('Location: ../views/simpatizante/upload.php');
    exit;
}

// criamos uma lista de tipos permitidos para o upload de ficheiros e verficamos se o tipo do ficheiro enviado
// está dentro dos pertmitidos senão redirecionamos para o upload.php com um erro
$allowed_mime = ['image/jpeg','image/png','image/gif','video/mp4','video/quicktime','audio/mpeg','audio/wav','audio/ogg'];
$mime = mime_content_type($_FILES['ficheiro']['tmp_name']);
if (!in_array($mime, $allowed_mime)) {
    $_SESSION['error'] = 'Tipo de ficheiro não permitido.';
    header('Location: ../views/simpatizante/upload.php');
    exit;
}

// determinamos o tipo do spot com base do tipo do ficheiro
if (str_starts_with($mime, 'image')) $tipo = 'foto';
elseif (str_starts_with($mime, 'video')) $tipo = 'video';
else $tipo = 'audio';

// o pathinfo lê o nome original do ficheiro do utilizador e fica apenas com a extensão do mesma
$ext = pathinfo($_FILES['ficheiro']['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $ext; // cria um nome aleatórioe volta a colar a extensão original no fim
// define o caminho absoluto de onde essa imagem vai estar no nosso computador (na pasta /uploads/)
$dest = __DIR__ . '/../uploads/' . $filename;

// o PHP quando recebe um ficheiro por upload guarda-o numa pasta temporária
// a função 'move_uploaded_file' pega nesse ficheiro temporário ('tmp_name') e move-o para o destino definitivo 
// ($dest, dentro dos nossos /uploads/)
if (!move_uploaded_file($_FILES['ficheiro']['tmp_name'], $dest)) {
    $_SESSION['error'] = 'Erro ao guardar o ficheiro.';
    header('Location: ../views/simpatizante/upload.php');
    exit;
}

// inserir na tabela spots
$stmt = $pdo->prepare(
    "INSERT INTO spots (user_id, type, filename, description, visibility)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->execute([$_SESSION['user_id'], $tipo, $filename, $descricao, $visibilidade]);
$spot_id = $pdo->lastInsertId();

// associar categorias
if ($cat_principal) {
    $pdo->prepare("INSERT INTO spot_categories (spot_id, category_id) VALUES (?, ?)")->execute([$spot_id, $cat_principal]);
}
if ($cat_secundaria) {
    $pdo->prepare("INSERT INTO spot_categories (spot_id, category_id) VALUES (?, ?)")->execute([$spot_id, $cat_secundaria]);
}

// inserir metainfo
$pdo->prepare("INSERT INTO spot_meta (spot_id, meta_key, meta_value) VALUES (?, 'localizacao', ?)")->execute([$spot_id, $localizacao]);
$pdo->prepare("INSERT INTO spot_meta (spot_id, meta_key, meta_value) VALUES (?, 'hora_do_dia', ?)")->execute([$spot_id, $hora_do_dia]);
$pdo->prepare("INSERT INTO spot_meta (spot_id, meta_key, meta_value) VALUES (?, 'raridade', ?)")->execute([$spot_id, $raridade]);

// notificamos os seguidores do uploader e subscritores das categorias do spot
require_once 'notify_helper.php';
notify_new_spot($pdo, $spot_id, $_SESSION['user_id']);

$_SESSION['success'] = 'Registo publicado com sucesso.';
header('Location: ../views/simpatizante/upload.php');
exit;