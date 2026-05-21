<?php
// tal como no dashboard do admin começamos por verificar as permissões antes de mostrar qualquer coisa
require_once '../../auth.php';
require_once '../../db.php';
require_once '../header.php';

// neste caso o nível mínimo exigido é simpatizante, o perfil logo acima do utilizador comum
// um utilizador com perfil user que tentasse aceder a esta página receberia um 403
// um admin também consegue aceder porque tem peso maior na hierarquia definida no auth.php
require_role('user');
?>
<!DOCTYPE html>

<html lang="pt"><head><meta charset="UTF-8"><title>Painel Simpatizante</title></head>
<body>
<!-- o login_action.php redireciona automaticamente para aqui quando o perfil é simpatizante -->
<h1>Painel User</h1>
<!-- a página de perfil permite ao simpatizante ver os seus dados e alterar a visibilidade do perfil -->
<a href="profile.php">O meu perfil</a> |


<a href="../../controllers/logout.php">Logout</a><hr>
<h2>Registos recentes</h2>
<?php
$stmt = $pdo->prepare(
    "SELECT s.*, u.username FROM spots s
     JOIN users u ON u.id = s.user_id
     WHERE s.visibility = 'publico'
     AND s.user_id != ?
     AND u.profile_visibility = 'publico'
     AND u.role IN ('simpatizante','admin')
     AND u.is_active = 1
     ORDER BY s.created_at DESC
     LIMIT 20"
);
$stmt->execute([$_SESSION['user_id']]);
$spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($spots)): ?>
<!-- se não houver registos públicos de outros utilizadores mostramos uma mensagem em vez de uma lista vazia -->
    <p>Nenhum registo público de outros utilizadores ainda.</p>
<?php else: ?>
    <?php foreach ($spots as $spot): ?>
        <!-- mostramos o username do criador do registo, o tipo, a data de criação e a descrição -->
    <div style="border:1px solid #ccc; margin-bottom:20px; padding:15px;">
        <strong><?= htmlspecialchars($spot['username']) ?></strong>
        <form method="POST" action="../../controllers/follow_action.php" style="display:inline">
            <input type="hidden" name="action" value="<?= $is_following ? 'unfollow' : 'follow' ?>">
            <input type="hidden" name="followed_id" value="<?= $spot['user_id'] ?>">
            <button type="submit"><?= $is_following ? 'Deixar de seguir' : 'Seguir' ?></button><br>
        </form>
        <strong><?= htmlspecialchars($spot['type']) ?></strong>
        — <?= htmlspecialchars($spot['created_at']) ?><br>
        
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
        <?= htmlspecialchars($spot['description']) ?><br>
        <a href="../spot.php?id=<?= $spot['id'] ?>">Ver detalhe</a>
            <!-- se houver um utilizador logado e ele não for o dono do registo, mostramos um botão para seguir 
             ou deixar de seguir o criador do registo -->
        <?php
        $chk = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
        $chk->execute([$_SESSION['user_id'], $spot['user_id']]);
        $is_following = (bool)$chk->fetchColumn();
        ?>
        
    </div>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>