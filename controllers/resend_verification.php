<?php
session_start();
require_once '../db.php';
require_once 'notify_helper.php';

$user_id = (int)($_SESSION['pending_user_id'] ?? 0);
if (!$user_id) {
    header('Location: ../views/login.php');
    exit;
}

// apagar token anterior
$pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$user_id]);

// gerar novo token
$token   = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
$pdo->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)")
    ->execute([$user_id, $token, $expires]);

// buscar email do utilizador
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$subject = "Confirma a tua conta - SpottedIRL";
$body = "
    <h2>Novo código de confirmação</h2>
    <p>O teu novo código é:</p>
    <h1 style='letter-spacing:8px'>{$token}</h1>
    <p>Este código expira em 30 minutos.</p>
";
send_email($pdo, $user['email'], $subject, $body);

$_SESSION['success'] = 'Novo código enviado para o teu email.';
header('Location: ../views/verify.php');
exit;