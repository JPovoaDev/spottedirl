<?php
session_start();
require_once '../db.php';
require_once '../auth.php';
require_role('user');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/search.php');
    exit;
}

$action      = $_POST['action'] ?? '';
$followed_id = (int)($_POST['followed_id'] ?? 0);

if (!$followed_id || $followed_id === (int)$_SESSION['user_id']) {
    header('Location: ../views/search.php');
    exit;
}

if ($action === 'follow') {
    $pdo->prepare("INSERT IGNORE INTO user_follows (user_id, simpatizante_id) VALUES (?, ?)")
        ->execute([$_SESSION['user_id'], $followed_id]);
} elseif ($action === 'unfollow') {
    $pdo->prepare("DELETE FROM user_follows WHERE user_id = ? AND simpatizante_id = ?")
        ->execute([$_SESSION['user_id'], $followed_id]);
}

// valida o referer para evitar open-redirect: só redireciona para URLs do mesmo host
$ref = $_SERVER['HTTP_REFERER'] ?? '';
$safe_ref = '../views/search.php';
if ($ref && filter_var($ref, FILTER_VALIDATE_URL)) {
    $ref_host = parse_url($ref, PHP_URL_HOST);
    $own_host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref_host === $own_host) {
        $safe_ref = $ref;
    }
}
header("Location: $safe_ref");
exit;