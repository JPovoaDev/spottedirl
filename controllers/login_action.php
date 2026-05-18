<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    $_SESSION['error'] = 'Preenche todos os campos.';
    header('Location: ../views/login.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    $_SESSION['error'] = 'Credenciais inválidas.';
    header('Location: ../views/login.php');
    exit;
}

// Guardar sessão
$_SESSION['user_id']   = $user['id'];
$_SESSION['username']  = $user['username'];
$_SESSION['role']      = $user['role'];

// Redirecionar conforme o perfil
switch ($user['role']) {
    case 'admin':
        header('Location: ../views/admin/dashboard.php');
        break;
    case 'simpatizante':
        header('Location: ../views/simpatizante/dashboard.php');
        break;
    default:
        header('Location: ../index.php');
}
exit;
