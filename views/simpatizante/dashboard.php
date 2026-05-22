<?php
// tal como no dashboard do admin começamos por verificar as permissões antes de mostrar qualquer coisa

require_once '../../auth.php';
require_once '../../db.php';

// neste caso o nível mínimo exigido é simpatizante, o perfil logo acima do utilizador comum
// um utilizador com perfil user que tentasse aceder a esta página receberia um 403
// um admin também consegue aceder porque tem peso maior na hierarquia definida no auth.php
require_role('simpatizante');
?>
<!DOCTYPE html>
<html lang="pt"><head><meta charset="UTF-8"><title>Painel Simpatizante</title></head>
<body>
<!-- o login_action.php redireciona automaticamente para aqui quando o perfil é simpatizante -->
<h1>Painel Simpatizante</h1>

<!-- é aqui que ele cria as categorias secundárias dentro das categorias principais do admin -->
<a href="subcategories.php">As minhas subcategorias</a> |
<a href="upload.php">Novo registo</a> |
<a href="batch_upload.php">Upload em lote</a> |

<!-- os meus registos mostra todos os spots do utilizador, públicos e privados, com opções de editar e apagar -->
<a href="my_spots.php">Os meus registos</a> |



<!-- a página de perfil permite ao simpatizante ver os seus dados e alterar a visibilidade do perfil -->
<a href="profile.php">O meu perfil</a> |
<a href="../../controllers/logout.php">Logout</a><hr>

<h2>Registos recentes</h2>
<?php
$stmt = $pdo->prepare(
    "SELECT s.*, u.username FROM spots s
     JOIN users u ON u.id = s.user_id
     WHERE (
         s.user_id = ?
         OR (
             s.visibility = 'publico'
             AND u.profile_visibility = 'publico'
             AND u.role IN ('simpatizante','admin')
             AND u.is_active = 1
         )
     )
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
    <div style="border:1px solid #ccc; margin-bottom:16px; padding:12px;">
        <?php if ($spot['user_id'] === $_SESSION['user_id']): ?>
            <strong><?= htmlspecialchars("Me") ?></strong>
            <?php else: ?>
             <strong><?= htmlspecialchars($spot['username']) ?></strong>
        <?php endif; ?>
        <?php
        $chk = $pdo->prepare("SELECT 1 FROM user_follows WHERE user_id = ? AND simpatizante_id = ?");
        $chk->execute([$_SESSION['user_id'], $spot['user_id']]);
        $is_following = (bool)$chk->fetchColumn();
        ?>
        <?php if ($spot['user_id'] !== $_SESSION['user_id']): ?>
        <form method="POST" action="../../controllers/follow_action.php" style="display:inline">
            <input type="hidden" name="action" value="<?= $is_following ? 'unfollow' : 'follow' ?>">
            <input type="hidden" name="followed_id" value="<?= $spot['user_id'] ?>">
            <button type="submit"><?= $is_following ? 'Deixar de seguir' : 'Seguir' ?></button>
        </form>
        <?php endif; ?><br>
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
        <a href="spot.php?id=<?= $spot['id'] ?>">Ver detalhe</a>
        <?php if ($spot['visibility'] === 'publico'): ?>
        <?php
        $url_spot = 'http://' . $_SERVER['HTTP_HOST'] . '/views/simpatizante/spot.php?id=' . $spot['id'];
        $texto    = urlencode($spot['description']);
        $url_enc  = urlencode($url_spot);
        ?>
        | <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $url_enc ?>" target="_blank">Facebook</a>
        | <a href="https://twitter.com/intent/tweet?text=<?= $texto ?>&url=<?= $url_enc ?>" target="_blank">Twitter</a>
<?php endif; ?>
            </div>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>