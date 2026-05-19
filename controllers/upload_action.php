<?php
session_start();
require_once '../db.php';
require_once '../auth.php';
require_role('simpatizante');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/simpatizante/upload.php');
    exit;
}

$descricao    = trim($_POST['descricao'] ?? '');
$visibilidade = $_POST['visibilidade'] ?? 'publico';
$localizacao  = trim($_POST['localizacao'] ?? '');
$hora_do_dia  = $_POST['hora_do_dia'] ?? '';
$raridade     = $_POST['raridade'] ?? 'comum';
$cat_principal   = (int)($_POST['categoria_principal'] ?? 0);
$cat_secundaria  = (int)($_POST['categoria_secundaria'] ?? 0);

if (!$descricao || !isset($_FILES['ficheiro']) || $_FILES['ficheiro']['error'] !== 0) {
    $_SESSION['error'] = 'Descrição e ficheiro são obrigatórios.';
    header('Location: ../views/simpatizante/upload.php');
    exit;
}

// validar tipo
$allowed_mime = ['image/jpeg','image/png','image/gif','video/mp4','video/quicktime','audio/mpeg','audio/wav','audio/ogg'];
$mime = mime_content_type($_FILES['ficheiro']['tmp_name']);
if (!in_array($mime, $allowed_mime)) {
    $_SESSION['error'] = 'Tipo de ficheiro não permitido.';
    header('Location: ../views/simpatizante/upload.php');
    exit;
}

// determinar tipo
if (str_starts_with($mime, 'image')) $tipo = 'foto';
elseif (str_starts_with($mime, 'video')) $tipo = 'video';
else $tipo = 'audio';

// nome único para o ficheiro
$ext = pathinfo($_FILES['ficheiro']['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $ext;
$dest = __DIR__ . '/../uploads/' . $filename;

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

$_SESSION['success'] = 'Registo publicado com sucesso.';
header('Location: ../views/simpatizante/upload.php');
exit;