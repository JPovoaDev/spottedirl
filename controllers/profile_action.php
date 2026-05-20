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
// esta página é acessível tanto a utilizadores comuns como a simpatizantes, o que muda é o redirecionamento no final para o perfil certo
//os utilizadores tem acesso ao perfil de maneira a pedirem promoção a simpatizante
require_role('user');

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
// esta ação é para os simpatizantes pedirem promoção a admin, o que é só um registo na tabela role_requests que o admin depois aprova ou rejeita 
    if ($action === 'request_promotion') {
    // verificamos se já há pedido pendente para não criar duplicados
    $chk = $pdo->prepare("SELECT id FROM role_requests WHERE user_id = ? AND status = 'pendente'");
    $chk->execute([$_SESSION['user_id']]);
   // se já existe um pedido pendente mostramos um erro e não criamos outro 
    if ($chk->fetch()) {
        $_SESSION['error'] = 'Já tens um pedido pendente.';
    } else {
        $pdo->prepare("INSERT INTO role_requests (user_id) VALUES (?)")->execute([$_SESSION['user_id']]);
        $_SESSION['success'] = 'Pedido enviado ao administrador.';
    }

    //redireciona para a página certa consoante o perfil
    $role = $_SESSION['role'] ?? '';
    $dest = $role === 'simpatizante'
        ? '../views/simpatizante/profile.php'
        : '../views/user/profile.php';
    header("Location: $dest");
    exit;
}

// fallback: redireciona para a página certa
$role = $_SESSION['role'] ?? '';
$dest = $role === 'simpatizante'
    ? '../views/simpatizante/profile.php'
    : '../views/user/profile.php';
header("Location: $dest");
exit;
