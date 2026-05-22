<?php
session_start();
require_once '../db.php';
require_once '../auth.php';
// Como esta ação é só para os simpatizantes, verificamos o role do utilizador e se não for o correto redirecionamos para 
// a página principal
require_role('simpatizante');

//se tentarem aceder a esta página sem ser por POST, ou seja, sem submeter o formulário, redirecionamos para o upload.php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/simpatizante/upload.php');
    exit;
}

//Lemos todos os campos do fulmularios, o trim serve para limpar espaços desnecessarios tanto no fim como no incio
$descricao    = trim($_POST['descricao'] ?? '');
$visibilidade = $_POST['visibilidade'] ?? 'privado';
$localizacao  = trim($_POST['localizacao'] ?? '');
$hora_do_dia  = $_POST['hora_do_dia'] ?? '';
$raridade     = $_POST['raridade'] ?? 'comum';
$cat_principal   = (int)($_POST['categoria_principal'] ?? 0);
$cat_secundaria  = (int)($_POST['categoria_secundaria'] ?? 0);

//Se a descrição vier vazia ou se n vier algum ficheiro, ou se vier um ficheiro com um erro no upload
// aparece um erro e redireciona para o upload.php

if (!$descricao || !isset($_FILES['ficheiro']) || $_FILES['ficheiro']['error'] !== 0) {
    $_SESSION['error'] = 'Descrição e ficheiro são obrigatórios.';
    header('Location: ../views/simpatizante/upload.php');
    exit;
}

// Criamos uma lista de tipos permitidos para o upload de data, e verficamos se o tipo do ficheiro enviado
//esta dentro dos tipos pertmitidos, se não estiver, redirecionamos para o upload.php com um erro
$allowed_mime = ['image/jpeg','image/png','image/gif','video/mp4','video/quicktime','audio/mpeg','audio/wav','audio/ogg'];
$mime = mime_content_type($_FILES['ficheiro']['tmp_name']);
if (!in_array($mime, $allowed_mime)) {
    $_SESSION['error'] = 'Tipo de ficheiro não permitido.';
    header('Location: ../views/simpatizante/upload.php');
    exit;
}

// Aqui determinamos o tipo do spot com base do tipo do ficheiro.
if (str_starts_with($mime, 'image')) $tipo = 'foto';
elseif (str_starts_with($mime, 'video')) $tipo = 'video';
else $tipo = 'audio';

//
$ext = pathinfo($_FILES['ficheiro']['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $ext;
$dest = __DIR__ . '/../uploads/' . $filename;

//
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