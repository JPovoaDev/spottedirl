<?php
// esta página mostra os registos públicos de outros simpatizantes
// filtramos por visibilidade do registo e por visibilidade do perfil do utilizador que o publicou
// se o perfil estiver privado os seus registos não aparecem aqui mesmo que o registo em si seja público
require_once '../../auth.php';
require_once '../../db.php';
require_role('simpatizante');

// apanhamos registos públicos de outros utilizadores com perfil simpatizante ou admin
// excluímos os registos do próprio utilizador porque esses estão na página "Os meus registos"
// a condição u.profile_visibility = 'publico' faz com que só possamos ver registos de quem tem o perfil visível
$stmt = $pdo->prepare(
    "SELECT s.*, u.username FROM spots s
     JOIN users u ON u.id = s.user_id
     WHERE s.visibility = 'publico'
     AND s.user_id != ?
     AND u.profile_visibility = 'publico'
     AND u.role IN ('simpatizante', 'admin')
     AND u.is_active = 1
     ORDER BY s.created_at DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$spots = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Registos de outros simpatizantes – SpottedIRL</title>
</head>
<body>
<h1>Registos de outros simpatizantes</h1>

<?php if (empty($spots)): ?>
    <p>Não há registos públicos de outros simpatizantes para mostrar.</p>
<?php else: ?>
    <?php foreach ($spots as $spot): ?>
        <div style="border:1px solid #ccc; margin-bottom:16px; padding:12px;">
            <strong><?= htmlspecialchars($spot['username']) ?></strong>
            — <?= htmlspecialchars($spot['type']) ?>
            — <?= htmlspecialchars($spot['created_at']) ?><br>
            <?= htmlspecialchars($spot['description']) ?><br>

            <!-- mostramos o ficheiro consoante o tipo -->
            <?php if ($spot['type'] === 'foto'): ?>
                <img src="../../uploads/<?= htmlspecialchars($spot['filename']) ?>" style="max-width:300px"><br>
            <?php elseif ($spot['type'] === 'video'): ?>
                <video controls style="max-width:300px">
                    <source src="../../uploads/<?= htmlspecialchars($spot['filename']) ?>">
                </video><br>
            <?php elseif ($spot['type'] === 'audio'): ?>
                <audio controls>
                    <source src="../../uploads/<?= htmlspecialchars($spot['filename']) ?>">
                </audio><br>
            <?php endif; ?>

            <!-- o link de detalhe aponta para o spot.php que já trata da lógica de visibilidade -->
            <a href="../spot.php?id=<?= $spot['id'] ?>">Ver detalhe</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<a href="dashboard.php">&#8592; Voltar ao painel</a>
</body>
</html>
