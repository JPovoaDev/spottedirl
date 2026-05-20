<?php
require_once '../../auth.php';
require_once '../../db.php';

require_role('admin');

$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);

// buscamos todos os utilizadores exceto o admin logado, ordenados por perfil e depois por username
$stmt = $pdo->prepare(
    "SELECT id, username, email, role, profile_visibility, is_active, created_at
     FROM users
     WHERE id != ?
     ORDER BY FIELD(role, 'admin', 'simpatizante', 'user'), username"
);
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// buscamos os pedidos pendentes ANTES de começar o HTML, fora de qualquer tabela
$reqs = $pdo->query(
    "SELECT r.id, r.requested_at, u.username, u.role, u.id AS user_id
     FROM role_requests r
     JOIN users u ON u.id = r.user_id
     WHERE r.status = 'pendente'
     ORDER BY r.requested_at ASC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- esta página tem duas tabelas, uma para os pedidos de subida de perfil e outra para a gestão dos utilizadores -->
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Utilizadores – Admin</title>
</head>
<body>
<h1>Gestão de Utilizadores</h1>

<?php if ($error):   ?><p style="color:red"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p style="color:green"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<?php if (!empty($reqs)): ?>
<!-- se houver pedidos pendentes mostramos a tabela, caso contrário mostramos uma mensagem -->
<h2>Pedidos de subida de perfil</h2>
<table border="1" cellpadding="6">
    <thead>
        <tr>
            <th>Username</th>
            <th>Perfil atual</th>
            <th>Pedido em</th>
            <th>Ação</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($reqs as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['username']) ?></td>
            <td><?= htmlspecialchars($r['role']) ?></td>
            <td><?= htmlspecialchars($r['requested_at']) ?></td>
            <td>
                <!-- cada pedido tem dois botões, um para aprovar e outro para rejeitar -->
                <!-- ambos os formulários enviam o request_id e o user_id para o user_action.php processar a decisão -->
                <form method="POST" action="../../controllers/user_action.php" style="display:inline">
                    <input type="hidden" name="action" value="approve_request">
                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="user_id" value="<?= $r['user_id'] ?>">
                    <button type="submit">Aprovar</button>
                </form>
                <form method="POST" action="../../controllers/user_action.php" style="display:inline">
                    <input type="hidden" name="action" value="reject_request">
                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                    <!-- CORREÇÃO: user_id adicionado ao form de rejeitar também -->
                    <input type="hidden" name="user_id" value="<?= $r['user_id'] ?>">
                    <button type="submit" style="color:red">Rejeitar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<hr>
<?php endif; ?>

<?php if (empty($users)): ?>
    <p>Não há outros utilizadores registados.</p>
<?php else: ?>
    <!-- listagem de todos os utilizadores exceto o admin logado, com ações para promover/demover, suspender/reativar e apagar -->
<h2>Utilizadores</h2>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Perfil</th>
                <th>Visibilidade do perfil</th>
                <th>Estado</th>
                <th>Registado em</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <!-- para cada utilizador mostramos os seus dados e os botões de ação consoante o perfil e estado -->
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td><?= htmlspecialchars($u['profile_visibility']) ?></td>
                <td style="color:<?= $u['is_active'] ? 'green' : 'red' ?>">
                    <?= $u['is_active'] ? 'Ativo' : 'Suspenso' ?>
                </td>
                <td><?= htmlspecialchars($u['created_at']) ?></td>
                <td>
                    <!-- um utilizador com perfil user pode ser promovido a simpatizante, e um simpatizante pode ser rebaixado a user -->
                    <?php if ($u['role'] === 'user'): ?>
                        <form method="POST" action="../../controllers/user_action.php" style="display:inline">
                            <input type="hidden" name="action" value="promote">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit">Promover a simpatizante</button>
                        </form>
                        <!--  o botão de promover só aparece para utilizadores com perfil user, não para simpatizantes -->
                    <?php elseif ($u['role'] === 'simpatizante'): ?>
                        <form method="POST" action="../../controllers/user_action.php" style="display:inline">
                            <input type="hidden" name="action" value="demote">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit">Remover simpatizante</button>
                        </form>
                    <?php endif; ?>
                        <!-- o botão de suspender ou reativar aparece para todos os utilizadores exceto admins, e muda consoante o estado atual -->
                    <form method="POST" action="../../controllers/user_action.php" style="display:inline">
                        <input type="hidden" name="action" value="toggle_active">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button type="submit"><?= $u['is_active'] ? 'Suspender' : 'Reativar' ?></button>
                    </form>
                        <!-- o botão de apagar aparece para todos os utilizadores exceto admins, e tem uma confirmação para evitar apagamentos acidentais -->
                    <form method="POST" action="../../controllers/user_action.php" style="display:inline"
                          onsubmit="return confirm('Apagar utilizador e todos os seus dados?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button type="submit" style="color:red">Apagar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<a href="dashboard.php">&#8592; Voltar ao painel</a>
</body>
</html>