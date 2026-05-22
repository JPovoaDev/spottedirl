<?php
// controlador de subscrições a categorias para receber notificações por email quando há novos spots nessa categoria
session_start();
require_once '../db.php';
require_once '../auth.php';

require_role('user'); // os guests não têm conta

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/search.php');
    exit;
}

$action = $_POST['action'] ?? '';
$category_id = (int)($_POST['category_id'] ?? 0);

if (!$category_id) {
    header('Location: ../views/search.php');
    exit;
}

// verificamos se a categoria pedida existe na tabela categories antes de avançarmos
$chk = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
$chk->execute([$category_id]);

// se devolver vazio, a categoria não existe
if (!$chk->fetch()) {
    $_SESSION['error'] = 'Categoria inválida.';
    // redirecionamos o utilizador de volta à página anterior ou para a página base de pesquisa
    $ref = $_SERVER['HTTP_REFERER'] ?? '../views/search.php';
    header("Location: $ref");
    exit;
}

if ($action === 'subscribe') {
    // o IGNORE faz com que a função não faça nada e não emite erros caso o utilizador já esteja subscrito 
    $pdo->prepare("INSERT IGNORE INTO subscriptions (user_id, category_id) VALUES (?, ?)") -> 
    execute([$_SESSION['user_id'], $category_id]);
    $_SESSION['success'] = 'Subscrito com sucesso.';

} elseif ($action === 'unsubscribe') {
    // removemos a associação para o utilizador e categoria respetivos
    $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ? AND category_id = ?")
        ->execute([$_SESSION['user_id'], $category_id]);
    $_SESSION['success'] = 'Subscrição removida.';
}

safe_redirect('../views/search.php');