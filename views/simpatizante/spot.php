<?php
session_start();
require_once '../../db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ../../index.php'); exit; }

// a query agora tem duas condições de visibilidade: ou o registo é público ou pertence ao utilizador atual
// assim o dono consegue ver os seus registos privados mas qualquer outro utilizador só vê os públicos
// o COALESCE serve para tratar o caso em que não há sessão ativa e o user_id é null, usando 0 como fallback
$user_id = $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare(
    "SELECT s.*, u.username FROM spots s
     JOIN users u ON u.id = s.user_id
     WHERE s.id = ?
     AND (s.visibility = 'publico' OR s.user_id = ?)"
);
$stmt->execute([$id, $user_id]);
$spot = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$spot) { echo "Registo não encontrado."; exit; }

// apanha a metainformação associada a este registo, como localização, hora do dia e raridade, que estão guardados na tabela spot_meta
$meta_stmt = $pdo->prepare("SELECT meta_key, meta_value FROM spot_meta WHERE spot_id = ?");
$meta_stmt->execute([$id]);
$metas = $meta_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><title>Registo – SpottedIRL</title></head>
<body>
    <!-- O link "Voltar" leva de volta à página principal onde estão listados os registos públicos -->
<a href="dashboard.php">&#8592; Voltar</a>
<!-- Aqui mostramos o título do registo que é a descrição, o username do utilizador que fez o upload e a data de criação -->
<h1><?= htmlspecialchars($spot['description']) ?></h1>
<p>Por: <?= htmlspecialchars($spot['username']) ?> | <?= htmlspecialchars($spot['created_at']) ?></p>

<?php
// se houver um utilizador logado e ele não for o dono do registo, mostramos um botão para seguir ou deixar de seguir o criador do registo
$is_following = false;
if (!empty($_SESSION['user_id'])) {
    $chk = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
    $chk->execute([$_SESSION['user_id'], $spot['user_id']]);
    $is_following = (bool)$chk->fetchColumn();
}
?>
<!-- o botão de seguir ou deixar de seguir só aparece para utilizadores logados que não sejam o dono do registo -->
<?php if (!empty($_SESSION['user_id']) && $_SESSION['user_id'] != $spot['user_id']): ?>
    <form method="POST" action="../controllers/follow_action.php" style="display:inline">
        <input type="hidden" name="action" value="<?= $is_following ? 'unfollow' : 'follow' ?>">
        <input type="hidden" name="followed_id" value="<?= $spot['user_id'] ?>">
        <button type="submit"><?= $is_following ? 'Deixar de seguir' : 'Seguir' ?></button>
    </form>
<?php endif; ?>

<!-- se o registo for privado mostramos uma indicação para o dono saber que só ele o consegue ver -->
<?php if ($spot['visibility'] === 'privado'): ?>
    <p style="color:orange"><em>Este registo é privado e só tu o consegues ver.</em></p>
<?php endif; ?>

<!-- Aqui mostramos o ficheiro do registo, se for foto mostramos a imagem, se for video mostramos o video e se for audio mostramos 
     o audio player -->
<?php if ($spot['type'] === 'foto'): ?>
    <!-- O caminho para o ficheiro é "../uploads/" porque estamos numa subpasta views e os ficheiros estão guardados 
     na pasta uploads que está no mesmo nível de views -->
    <img src="../../uploads/<?= htmlspecialchars($spot['filename']) ?>" style="max-width:600px"><br>
<?php elseif ($spot['type'] === 'video'): ?>
    <video controls style="max-width:600px">
        <source src="../../uploads/<?= htmlspecialchars($spot['filename']) ?>">
    </video><br>
<?php elseif ($spot['type'] === 'audio'): ?>
    <audio controls>
        <source src="../../uploads/<?= htmlspecialchars($spot['filename']) ?>">
    </audio><br>
<?php endif; ?>

<!-- Aqui mostramos a metainformação do registo, que são a localização, a hora do dia e a raridade, 
que são informações adicionais que o utilizador pode escolher preencher ou não no momento do upload 
 Se alguma dessas informações não tiver sido preenchida, mostramos "—" para indicar que não há informação disponível -->
<h2>Metainfo</h2>
<p>Localização: <?= htmlspecialchars($metas['localizacao'] ?? '&mdash;') ?></p>
<p>Hora do dia: <?= htmlspecialchars($metas['hora_do_dia'] ?? '&mdash;') ?></p>
<p>Raridade: <?= htmlspecialchars($metas['raridade'] ?? '&mdash;') ?></p>

<!-- se o registo pertencer ao utilizador atual mostramos um link direto para editar -->
<?php if ((int)$spot['user_id'] === (int)($user_id)): ?>
    <br><a href="simpatizante/edit_spot.php?id=<?= $spot['id'] ?>">Editar este registo</a>
<?php endif; ?>
</body>
</html>