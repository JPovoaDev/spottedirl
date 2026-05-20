<?php
// esta página é só do admin e é onde ele gere todos os utilizadores da plataforma
// pode promover um user comum a simpatizante, remover esse perfil, suspender contas ou apagá-las
require_once '../../auth.php';
require_once '../../db.php';

require_role('admin');

$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);

// apanhamos todos os utilizadores exceto o próprio admin que está logged in pois não faz sentido o admin aparecer na sua própria lista de gestão
// ordenamos por role para ficar agrupado e depois por username dentro de cada grupo
$stmt = $pdo->prepare(
    "SELECT id, username, email, role, profile_visibility, is_active, created_at
     FROM users
     WHERE id != ?
     ORDER BY FIELD(role, 'admin', 'simpatizante', 'user'), username"
);
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
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

<?php if (empty($users)): ?>
    <p>Não há outros utilizadores registados.</p>
<?php else: ?>
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
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td><?= htmlspecialchars($u['profile_visibility']) ?></td>
                <!-- mostramos o estado a cores para ficar mais legível na tabela -->
                <td style="color:<?= $u['is_active'] ? 'green' : 'red' ?>">
                    <?= $u['is_active'] ? 'Ativo' : 'Suspenso' ?>
                </td>
                <td><?= htmlspecialchars($u['created_at']) ?></td>
                <td>
                    <!-- a promoção só aparece para users comuns e a remoção só aparece para simpatizantes
                    assim evitamos mostrar botões que não fazem sentido para aquele perfil -->
                    <?php if ($u['role'] === 'user'): ?>
                        <form method="POST" action="../../controllers/user_action.php" style="display:inline">
                            <input type="hidden" name="action" value="promote">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit">Promover a simpatizante</button>
                        </form>
                    <?php elseif ($u['role'] === 'simpatizante'): ?>
                        <form method="POST" action="../../controllers/user_action.php" style="display:inline">
                            <input type="hidden" name="action" value="demote">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit">Remover simpatizante</button>
                        </form>
                    <?php endif; ?>

                    <!-- o botão muda de texto consoante o estado atual do utilizador -->
                    <form method="POST" action="../../controllers/user_action.php" style="display:inline">
                        <input type="hidden" name="action" value="toggle_active">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button type="submit"><?= $u['is_active'] ? 'Suspender' : 'Reativar' ?></button>
                    </form>

                    <!-- o confirm antes de apagar evita que o admin apague um utilizador por engano -->
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
