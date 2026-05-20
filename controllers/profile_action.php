<?php
// este controlador trata das alterações ao perfil do próprio utilizador
// por agora serve para alternar a visibilidade do perfil entre público e privado
session_start();
require_once '../db.php';
require_once '../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/simpatizante/profile.php');
    exit;
}

// só simpatizantes e acima têm perfil com visibilidade configurável
require_role('simpatizante');

$action = $_POST['action'] ?? '';

if ($action === 'toggle_visibility') {
    // lemos a visibilidade atual do utilizador logged in e invertemo-la
    $chk = $pdo->prepare("SELECT profile_visibility FROM users WHERE id = ?");
    $chk->execute([$_SESSION['user_id']]);
    $user = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = 'Utilizador não encontrado.';
        header('Location: ../views/simpatizante/profile.php');
        exit;
    }

    $nova = $user['profile_visibility'] === 'publico' ? 'privado' : 'publico';
    $pdo->prepare("UPDATE users SET profile_visibility = ? WHERE id = ?")->execute([$nova, $_SESSION['user_id']]);
    $_SESSION['success'] = "Perfil agora definido como $nova.";
}

header('Location: ../views/simpatizante/profile.php');
exit;
