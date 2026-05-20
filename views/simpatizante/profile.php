<?php
// esta página mostra o perfil do simpatizante e permite-lhe alterar a visibilidade do seu perfil
// se o perfil estiver privado os seus registos não aparecem para outros simpatizantes na listagem pública
require_once '../../auth.php';
require_once '../../db.php';
require_role('simpatizante');

$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);

// apanhamos os dados do utilizador atual para mostrar no perfil e para saber a visibilidade atual
$stmt = $pdo->prepare(
    "SELECT username, email, role, profile_visibility, created_at FROM users WHERE id = ?"
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
<p><strong>Registos públicos:</strong> <?= $contagens['publico'] ?? 0 ?></p>
<p><strong>Registos privados:</strong> <?= $contagens['privado'] ?? 0 ?></p>

<hr>

<h2>Visibilidade do perfil</h2>

<!-- mostramos o estado atual da visibilidade e um botão para alternar -->
<!-- se o perfil estiver privado os registos deste utilizador não aparecem para outros simpatizantes -->
<p>
    Estado atual:
    <strong style="color:<?= $user['profile_visibility'] === 'publico' ? 'green' : 'red' ?>">
        <?= htmlspecialchars($user['profile_visibility']) ?>
    </strong>
</p>
<form method="POST" action="../../controllers/profile_action.php">
    <input type="hidden" name="action" value="toggle_visibility">
    <button type="submit">
        <?= $user['profile_visibility'] === 'publico' ? 'Tornar perfil privado' : 'Tornar perfil público' ?>
    </button>
</form>

<br>
<a href="dashboard.php">&#8592; Voltar ao painel</a>
</body>
</html>
