<?php
// este ficheiro processa o código de confirmação submetido pelo utilizador
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/login.php');
    exit;
}

$user_id = (int)($_SESSION['pending_user_id'] ?? 0);
$token = trim($_POST['token'] ?? '');

if (!$user_id) {
    header('Location: ../views/login.php');
    exit;
}

if (!$token) {
    $_SESSION['error'] = 'Introduz o código de confirmação.';
    header('Location: ../views/verify.php');
    exit;
}

// Verifica se o token é válido e ainda não expirou
$stmt = $pdo->prepare(
    "SELECT * FROM email_verifications 
     WHERE user_id = ? AND token = ? AND expires_at > NOW()"
);
$stmt->execute([$user_id, $token]);
$verification = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$verification) {
    $_SESSION['error'] = 'Código de confirmação inválido ou expirado.';
    header('Location: ../views/verify.php');
    exit;
}

// Token válido: ativa a conta do utilizador e remove o token usado
$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?")->execute([$user_id]);
    $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$user_id]);
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Ocorreu um erro ao ativar a conta. Tenta novamente.';
    header('Location: ../views/verify.php');
    exit;
}

unset($_SESSION['pending_user_id']);
$_SESSION['success'] = 'Conta confirmada com sucesso! Já podes iniciar sessão.';
header('Location: ../views/login.php');
exit;
