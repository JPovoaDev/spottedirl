<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['username'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request']);
    exit;
}

$username = trim($_GET['username']);

if (strlen($username) < 3) {
    echo json_encode(['available' => false, 'message' => 'Mínimo de 3 caracteres']);
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$username]);
$exists = $stmt->fetch();

if ($exists) {
    echo json_encode(['available' => false, 'message' => 'Username já em uso']);
} else {
    echo json_encode(['available' => true, 'message' => 'Username disponível']);
}
