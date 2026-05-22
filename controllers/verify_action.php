<?php
// este ficheiro processa o código de confirmação submetido pelo utilizador


session_start();
require_once '../db.php';

// garantimos que só aceitamos pedidos POST, se alguém tentar aceder diretamente pelo URL mandamos logo para o login sem fazer nada
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/login.php');
    exit;
}

// para sabermos de quem é a conta a verificar após se registar, pois como ainda não verificou o email não ficou com sessão iniciada
$user_id = (int)($_SESSION['pending_user_id'] ?? 0);
// apanhamos o código que a pessoa introduziu no formulário para verificar a conta
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

// verificamos na base de dados se existe um código igual ao que a pessoa introduziu e vemos se não expirou
$stmt = $pdo->prepare(
    "SELECT * FROM email_verifications WHERE user_id = ? AND token = ? AND expires_at > NOW()"
);
// executamos a query substituindo os '?' pelos valores reais para prevenir ataques de SQL Injection
$stmt->execute([$user_id, $token]);
$verification = $stmt->fetch(PDO::FETCH_ASSOC); // resultado da pesquisa

if (!$verification) {
    $_SESSION['error'] = 'Código de confirmação inválido ou expirado.';
    header('Location: ../views/verify.php');
    exit;
}

// se tiver houvido sucesso então ativamos a conta do utilizador
$pdo->beginTransaction();
try {
    // primeiro vamos à tabela dos users e passamos o estado dele para verificado
    $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?")->execute([$user_id]);
    // depois apagamos o registo do código de confirmação da tabela email_verifications para que ele não possa ser usado outra vez
    $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$user_id]);
    $pdo->commit(); // guardar na bd
} catch (Exception $e) {
    $pdo->rollBack(); // cancela e reverte as alterações todas
    $_SESSION['error'] = 'Ocorreu um erro ao ativar a conta. Tenta novamente.';
    header('Location: ../views/verify.php');
    exit;
}

unset($_SESSION['pending_user_id']); // como o utilizador já confirmou a conta limpamos a variável do ID porque passamos a usar o user_id
$_SESSION['success'] = 'Conta confirmada com sucesso! Já podes iniciar sessão.';
header('Location: ../views/login.php'); // está tudo feito, movemos o user para a página de Login
exit;
