<?php
// este controlador trata de todas as operações de gestão de utilizadores feitas pelo admin:
// promoção de perfil, remoção de perfil, suspensão, reativação e eliminação de conta
session_start();
require_once '../db.php';
require_once '../auth.php';

// se alguém tentar aceder diretamente pelo browser sem fazer um POST mandamos logo para o painel do admin
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/users.php');
    exit;
}

// todas as operações deste controlador são únicas ao admin
require_role('admin');

// apanhamos o que o admin quer fazer através do que foi submetido no formulário
$action = $_POST['action'] ?? '';
// qual utilizador vai sofrer a ação
$user_id = (int)($_POST['user_id'] ?? 0);

if (!$user_id) {
    $_SESSION['error'] = 'ID de utilizador inválido.';
    header('Location: ../views/admin/users.php');
    exit;
}

// o admin não pode aplicar estas ações na sua própria conta
// evita situações em que o admin se suspende a si próprio e fica sem acesso à plataforma
if ($user_id === (int)$_SESSION['user_id']) {
    $_SESSION['error'] = 'Não podes aplicar esta ação na tua própria conta.';
    header('Location: ../views/admin/users.php');
    exit;
}

switch ($action) {

    case 'promote':
        // só faz sentido promover utilizadores com perfil user, se já for simpatizante ou admin não fazemos nada
        $chk = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $chk->execute([$user_id]);
        $target = $chk->fetch(PDO::FETCH_ASSOC); // apanhamos o user e todos os seus dados

        // tem que ser um user
        if (!$target || $target['role'] !== 'user') {
            $_SESSION['error'] = 'Só é possível promover utilizadores com perfil user.';
            header('Location: ../views/admin/users.php');
            exit;
        }

        // agora atualizamos a role do user para simpatizante
        $stmt = $pdo->prepare("UPDATE users SET role = 'simpatizante' WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'Utilizador promovido a simpatizante.';
        header('Location: ../views/admin/users.php');
        exit;

    case 'demote':
        // remove o perfil de simpatizante e volta a user comum, só funciona se já for simpatizante
        $chk = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $chk->execute([$user_id]);
        $target = $chk->fetch(PDO::FETCH_ASSOC);

        // já tem que ser um simpatizante
        if (!$target || $target['role'] !== 'simpatizante') {
            $_SESSION['error'] = 'Só é possível remover o perfil de utilizadores que sejam simpatizantes.';
            header('Location: ../views/admin/users.php');
            exit;
        }

        // agora atualizamos a role do simpatizante para user
        $stmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'Perfil de simpatizante removido.';
        header('Location: ../views/admin/users.php');
        exit;

    case 'toggle_active':
        // isto define se a conta está suspensa ou não: ativo passa a suspenso e suspenso passa a ativo
        $chk = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
        $chk->execute([$user_id]);
        $target = $chk->fetch(PDO::FETCH_ASSOC);

        if (!$target) {
            $_SESSION['error'] = 'Utilizador não encontrado.';
            header('Location: ../views/admin/users.php');
            exit;
        }

        // como é um toggle é simples, vemos o valor atual e invertemos asseguir e atualizamos na bd
        $novo_estado = $target['is_active'] ? 0 : 1;
        $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$novo_estado, $user_id]);
        $_SESSION['success'] = $novo_estado ? 'Utilizador reativado.' : 'Utilizador suspenso.';
        header('Location: ../views/admin/users.php');
        exit;

    case 'delete':
        // apagamos os dados associados ao utilizador antes de apagar o utilizador em si porque as foreign keys na base de dados impedem de 
        // apagar um utilizador que ainda tenha registos ligados, primeiro as tabelas filhas e só depois o utilizador
        $pdo->prepare(
            "DELETE FROM spot_meta WHERE spot_id IN (SELECT id FROM spots WHERE user_id = ?)"
        )->execute([$user_id]);

        $pdo->prepare(
            "DELETE FROM spot_categories WHERE spot_id IN (SELECT id FROM spots WHERE user_id = ?)"
        )->execute([$user_id]);

        $pdo->prepare("DELETE FROM spots WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM categories WHERE created_by = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM role_requests WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);

        $_SESSION['success'] = 'Utilizador e todos os seus dados apagados.';
        header('Location: ../views/admin/users.php');
        exit;

    // as ações de aprovação ou rejeição de pedidos de promoção a simpatizante também passam por aqui
    // o admin pode aprovar ou rejeitar cada pedido pendente e o controlador atualiza o perfil do utilizador
    // e o estado do pedido conforme a ação escolhida
    case 'approve_request':
        $request_id = (int)($_POST['request_id'] ?? 0); // isto é o id do pedido porque na bd mudamos o valor a partir do pedido numero x
        $uid = (int)($_POST['user_id'] ?? 0); // id do user que fez o pedido
        $pdo->prepare("UPDATE users SET role = 'simpatizante' WHERE id = ? AND role = 'user'")->execute([$uid]);
        $pdo->prepare("UPDATE role_requests SET status = 'aprovado' WHERE id = ?")->execute([$request_id]);
        $_SESSION['success'] = 'Utilizador promovido a simpatizante.';
        header('Location: ../views/admin/users.php');
        exit;

    case 'reject_request':
        $request_id = (int)($_POST['request_id'] ?? 0);
        $pdo->prepare("UPDATE role_requests SET status = 'rejeitado' WHERE id = ?")->execute([$request_id]);
        $_SESSION['success'] = 'Pedido rejeitado.';
        header('Location: ../views/admin/users.php');
        exit;
}

// se chegámos aqui é porque o action não era nenhum dos esperados
header('Location: ../views/admin/users.php');
exit;
