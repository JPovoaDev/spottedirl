<?php
// controlador de subscrições a categorias — para receber notificações por email quando há novos spots nessa categoria
session_start();
require_once '../db.php';
require_once '../auth.php';
require_role('user');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/search.php');
    exit;
}

$action      = $_POST['action']      ?? '';
$category_id = (int)($_POST['category_id'] ?? 0);

if (!$category_id) {
    header('Location: ../views/search.php');
    exit;
}

// verificamos que a categoria existe antes de inserir
$chk = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
$chk->execute([$category_id]);
if (!$chk->fetch()) {
    $_SESSION['error'] = 'Categoria inválida.';
    $ref = $_SERVER['HTTP_REFERER'] ?? '../views/search.php';
    header("Location: $ref");
    exit;
}

if ($action === 'subscribe') {
    // INSERT IGNORE para não duplicar caso já exista
    $pdo->prepare("INSERT IGNORE INTO subscriptions (user_id, category_id) VALUES (?, ?)")
        ->execute([$_SESSION['user_id'], $category_id]);
    $_SESSION['success'] = 'Subscrito com sucesso.';
} elseif ($action === 'unsubscribe') {
    $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ? AND category_id = ?")
        ->execute([$_SESSION['user_id'], $category_id]);
    $_SESSION['success'] = 'Subscrição removida.';
}

safe_redirect('../views/search.php');