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
    $pdo->prepare("INSERT IGNORE INTO follows (follower_id, followed_id) VALUES (?, ?)")
        ->execute([$_SESSION['user_id'], $followed_id]);
} elseif ($action === 'unfollow') {
    $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?")
        ->execute([$_SESSION['user_id'], $followed_id]);
}

// volta para onde veio
$ref = $_SERVER['HTTP_REFERER'] ?? '../views/search.php';
header("Location: $ref");
exit;