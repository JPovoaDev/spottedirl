<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/register.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validação básica
if (!$username || !$email || !$password) {
    $_SESSION['error'] = 'Preenche todos os campos.';
    header('Location: ../views/register.php');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Email inválido.';
    header('Location: ../views/register.php');
    exit;
}
if (strlen($password) < 6) {
    $_SESSION['error'] = 'A password deve ter pelo menos 6 caracteres.';
    header('Location: ../views/register.php');
    exit;
}

// Verificar se username ou email já existem
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    $_SESSION['error'] = 'Username ou email já em uso.';
    header('Location: ../views/register.php');
    exit;
}

// Inserir utilizador com perfil 'user'
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare(
    'INSERT INTO users (username, email, password_hash, role, created_at)
     VALUES (?, ?, ?, \'user\', NOW())'
);
$stmt->execute([$username, $email, $hash]);

$_SESSION['success'] = 'Conta criada com sucesso. Faz login!';
header('Location: ../views/login.php');
exit;
