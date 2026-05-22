<?php
// este controlador é responsável por criar e reenviar um novo código de verificação para o email do utilizador 
// caso o código anterior tenha expirado ou tenha sido perdido antes da conta ter sido confirmada
session_start();
require_once '../db.php';
require_once 'notify_helper.php';

// samos o id que atribuímos após criar a conta mas antes de registar o email, por isso ainda não existe o user_id
$user_id = (int)($_SESSION['pending_user_id'] ?? 0);
if (!$user_id) {
    header('Location: ../views/login.php');
    exit;
}

// apagamos o token anterior
$pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$user_id]);

// e criamos o novo
// geramos um número entre 0 e 999999 e se for gerado "42" o str_pad acrescenta zeros à esquerda até ficarem 6 dígitos
$token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
// damos uma margem de 30 minutos para o user confirmar a conta e usar o token
$expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
// guardamos este novo código e a respetiva data de expiração na base de dados associados ao utilizador
$pdo->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)")
    ->execute([$user_id, $token, $expires]);

// apanhamos o email do utilizador
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
// o FETCH_ASSOC ordena a resposta pelas colunas em vez do fazer por números ($user[0]) para podermos fazer `$user['email']`
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$subject = "Confirma a tua conta - SpottedIRL";
$body = "
    <h2>Novo código de confirmação</h2>
    <p>O teu novo código é:</p>
    <h1 style='letter-spacing:8px'>{$token}</h1>
    <p>Este código expira em 30 minutos.</p>
";
// chama a função send_email que está guardada no ficheiro notify_helper.php que importámos no topo
// passamos as 4 coisas que ela precisa de saber para fazer o envio:
// a ligação à base de dados ($pdo), para quem vai o email ($user['email']), qual o assunto ($subject) e qual a mensagem ($body)
send_email($pdo, $user['email'], $subject, $body);

$_SESSION['success'] = 'Novo código enviado para o teu email.';
header('Location: ../views/verify.php');
exit;