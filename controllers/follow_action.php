<?php
// este controlador é responsável por gerir as ações de dar follow e unfollow
session_start();
require_once '../db.php';
require_once '../auth.php';
// apenas utilizadores autenticados (que têm conta) podem usar estas features
require_role('user');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/search.php');
    exit;
}

// guardamos a pessoa que este user quer seguir e convertemos para números inteiros para prevenir injeções através da manipulação do ID
$action = $_POST['action'] ?? '';
$followed_id = (int)($_POST['followed_id'] ?? 0);

// interrompemos a execução caso o ID de destino não exista ou se o utilizador tentar seguir-se a si mesmo
if (!$followed_id || $followed_id === (int)$_SESSION['user_id']) {
    header('Location: ../views/search.php');
    exit;
}

if ($action === 'follow') {
    // usamos INSERT IGNORE para prevenir que o sistema tenha um erro caso haja uma anomalia em que o 
    // formulário envie um duplo clique, o que faria com que inserisse uma relação que já estaria estabelecida
    $pdo->prepare("INSERT IGNORE INTO follows (follower_id, followed_id) VALUES (?, ?)")
        ->execute([$_SESSION['user_id'], $followed_id]);
} elseif ($action === 'unfollow') {
    $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?")
        ->execute([$_SESSION['user_id'], $followed_id]);
}

safe_redirect('../views/search.php');