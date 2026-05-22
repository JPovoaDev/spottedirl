<?php
// Ficheiro redundante - usar views/spot.php
header("Location: ../spot.php" . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit;

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ../../index.php'); exit; }

// a query agora tem duas condições de visibilidade: ou o registo é público ou pertence ao utilizador atual
// assim o dono consegue ver os seus registos privados mas qualquer outro utilizador só vê os públicos
$user_id = $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare(
    "SELECT s.*, u.username FROM spots s
     JOIN users u ON u.id = s.user_id
     WHERE s.id = ?
     AND (s.visibility = 'publico' OR s.user_id = ?)"
);
$stmt->execute([$id, $user_id]);
$spot = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$spot) {
    $_SESSION['error'] = 'Registo não encontrado ou acesso negado.';
    header('Location: dashboard.php');
    exit;
}

// apanha a metainformação associada a este registo
$meta_stmt = $pdo->prepare("SELECT meta_key, meta_value FROM spot_meta WHERE spot_id = ?");
$meta_stmt->execute([$id]);
$metas = $meta_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// busca a chave DeepL para saber se o botão de tradução deve aparecer
$cfg_stmt = $pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'deepl_api_key'");
$cfg_stmt->execute();
$has_deepl = (bool)$cfg_stmt->fetchColumn();
$page_title = 'Registo';
require_once '../header.php';
?>
<!-- O link "Voltar" leva de volta ao painel do simpatizante -->
<a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">&#8592; Voltar</a>

<div class="spot-detail">
<!-- Aqui mostramos o título do registo que é a descrição, o username do utilizador que fez o upload e a data de criação -->
<h1><?= htmlspecialchars($spot['description']) ?></h1>
<p>Por: <strong><?= htmlspecialchars($spot['username']) ?></strong> | <?= htmlspecialchars($spot['created_at']) ?></p>

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
    <form method="POST" action="../../controllers/follow_action.php" style="display:inline">
        <input type="hidden" name="action" value="<?= $is_following ? 'unfollow' : 'follow' ?>">
        <input type="hidden" name="followed_id" value="<?= $spot['user_id'] ?>">
        <button type="submit" class="btn"><?= $is_following ? 'Deixar de seguir' : 'Seguir' ?></button>
    </form>
<?php endif; ?>

<!-- se o registo for privado mostramos uma indicação para o dono saber que só ele o consegue ver -->
<?php if ($spot['visibility'] === 'privado'): ?>
    <p style="color:orange"><em>Este registo é privado e só tu o consegues ver.</em></p>
<?php endif; ?>

<!-- Aqui mostramos o ficheiro do registo, se for foto mostramos a imagem, se for video mostramos o video e se for audio mostramos o audio player -->
<?php if ($spot['type'] === 'foto'): ?>
    <img src="../../uploads/<?= htmlspecialchars($spot['filename']) ?>">
<?php elseif ($spot['type'] === 'video'): ?>
    <video controls>
        <source src="../../uploads/<?= htmlspecialchars($spot['filename']) ?>">
    </video>
<?php elseif ($spot['type'] === 'audio'): ?>
    <audio controls>
        <source src="../../uploads/<?= htmlspecialchars($spot['filename']) ?>">
    </audio>
<?php endif; ?>

<!-- Aqui mostramos a metainformação do registo -->
<h2>Metainfo</h2>
<p>Localização: <?= htmlspecialchars($metas['localizacao'] ?? '&mdash;') ?></p>
<p>Hora do dia: <?= htmlspecialchars($metas['hora_do_dia'] ?? '&mdash;') ?></p>
<p>Raridade: <?= htmlspecialchars($metas['raridade'] ?? '&mdash;') ?></p>


<hr>
<h2>Traduzir descrição</h2>
<p><strong>Original:</strong> <?= htmlspecialchars($spot['description']) ?></p>
<select id="target_lang">
    <option value="EN">Inglês</option>
    <option value="ES">Espanhol</option>
    <option value="FR">Francês</option>
    <option value="DE">Alemão</option>
</select>
<button class="btn" onclick="translateSpot()">Traduzir</button>
<p id="translated_text" style="color:#333;font-style:italic;margin-top:10px;"></p>
<script>
function translateSpot() {
    const text = <?= json_encode($spot['description']) ?>;
    const lang = document.getElementById('target_lang').value;
    const out  = document.getElementById('translated_text');
    out.textContent = 'A traduzir…';
    fetch('../../controllers/translate_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'text=' + encodeURIComponent(text) + '&target_lang=' + encodeURIComponent(lang)
    })
    .then(r => r.json())
    .then(d => { out.textContent = d.translated ?? d.error ?? 'Erro.'; })
    .catch(() => { out.textContent = 'Erro de ligação.'; });
}
</script>


<!-- se o registo pertencer ao utilizador atual mostramos um link direto para editar -->
<div style="display: flex; gap: 10px; align-items: center; margin-top: 20px; flex-wrap: wrap;">
<?php if ((int)$spot['user_id'] === (int)$user_id): ?>
    <a href="edit_spot.php?id=<?= $spot['id'] ?>" class="btn">Editar este registo</a>
<?php endif; ?>
<?php if ($spot['visibility'] === 'publico'): ?>
<?php
$url_spot = 'http://' . $_SERVER['HTTP_HOST'] . '/views/simpatizante/spot.php?id=' . $spot['id'];
$texto    = urlencode($spot['description']);
$url_enc  = urlencode($url_spot);
?>
<a href="https://www.facebook.com/sharer/sharer.php?u=<?= $url_enc ?>" target="_blank" class="btn btn-secondary">Facebook</a>
<a href="https://twitter.com/intent/tweet?text=<?= $texto ?>&url=<?= $url_enc ?>" target="_blank" class="btn btn-secondary">Twitter</a>
<?php endif; ?>
</div>
</div>

<?php require_once '../footer.php'; ?>