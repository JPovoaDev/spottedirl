<?php
// esta página mostra o perfil do simpatizante e permite-lhe alterar a visibilidade do seu perfil
// se o perfil estiver privado os seus registos não aparecem para outros simpatizantes na listagem pública
require_once '../../auth.php';
require_once '../../db.php';
require_role('user');

$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);

// apanhamos os dados do utilizador atual para mostrar no perfil e para saber a visibilidade atual
$stmt = $pdo->prepare(
    "SELECT username, email, role, created_at FROM users WHERE id = ?"
);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// contamos quantos registos públicos e privados o utilizador tem para mostrar no perfil
$stmt_count = $pdo->prepare(
    "SELECT visibility, COUNT(*) AS total FROM spots WHERE user_id = ? GROUP BY visibility"
);
$stmt_count->execute([$_SESSION['user_id']]);
$contagens = [];
foreach ($stmt_count->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $contagens[$row['visibility']] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>O meu perfil – SpottedIRL</title>
</head>
<body>
<h1>O meu perfil</h1>

<?php if ($error):   ?><p style="color:red"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p style="color:green"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
<p><strong>Perfil:</strong> <?= htmlspecialchars($user['role']) ?></p>
<p><strong>Membro desde:</strong> <?= htmlspecialchars($user['created_at']) ?></p>
<hr>

<h2>Pedido de subida de perfil</h2>
<?php
// verificamos se já existe um pedido pendente para não criar duplicados
$req = $pdo->prepare("SELECT id, status FROM role_requests WHERE user_id = ? ORDER BY requested_at DESC LIMIT 1");
$req->execute([$_SESSION['user_id']]);
$pedido = $req->fetch(PDO::FETCH_ASSOC);
?>
<!-- se o pedido já existe mostramos o estado, se não existe mostramos o botão para criar um novo pedido -->
<?php if ($pedido && $pedido['status'] === 'pendente'): ?>
    <p style="color:orange">Pedido pendente — aguarda resposta do administrador.</p>
<?php elseif ($pedido && $pedido['status'] === 'rejeitado'): ?>
    <p style="color:red">O teu último pedido foi rejeitado.</p>
    <form method="POST" action="../../controllers/profile_action.php">
        <input type="hidden" name="action" value="request_promotion">
        <button type="submit">Pedir novamente</button>
    </form>
<?php else: ?>
    <!-- se não existe pedido pendente mostramos o botão para criar um novo pedido -->
    <form method="POST" action="../../controllers/profile_action.php">
        <input type="hidden" name="action" value="request_promotion">
        <button type="submit">Pedir subida de perfil</button>
    </form>
<?php endif; ?>
<a href="dashboard.php">&#8592; Voltar ao painel</a>
</body>
</html>
