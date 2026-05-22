<?php
// Ficheiro redundante - usar views/spot.php
header("Location: ../spot.php" . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit;
require_once '../auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Autenticação necessária.']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ../../index.php');
    exit;
}

// verifica visibilidade: publico para todos, privado só para o dono
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

// metainformação do spot
$meta_stmt = $pdo->prepare("SELECT meta_key, meta_value FROM spot_meta WHERE spot_id = ?");
$meta_stmt->execute([$id]);
$metas = $meta_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// verifica se o utilizador segue o criador
$is_following = false;
if ($user_id && $user_id != $spot['user_id']) {
    $chk = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND followed_id = ?");
    $chk->execute([$user_id, $spot['user_id']]);
    $is_following = (bool)$chk->fetchColumn();
}

// busca a chave DeepL para saber se o botão de tradução deve aparecer
$cfg_stmt = $pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'deepl_api_key'");
$cfg_stmt->execute();
$has_deepl = (bool)$cfg_stmt->fetchColumn();
$page_title = $spot['description'];
require_once '../header.php';
?>
<a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">&#8592; Voltar</a>

<div class="spot-detail">
    <h1><?= htmlspecialchars($spot['description']) ?></h1>
    <p>Por: <strong><?= htmlspecialchars($spot['username']) ?></strong> | <?= htmlspecialchars($spot['created_at']) ?></p>

<!-- botão de seguir/deixar de seguir — só para utilizadores logados que não sejam o dono -->
<?php if ($user_id && $user_id != $spot['user_id']): ?>
    <form method="POST" action="../../controllers/follow_action.php" style="display:inline">
        <input type="hidden" name="action" value="<?= $is_following ? 'unfollow' : 'follow' ?>">
        <input type="hidden" name="followed_id" value="<?= $spot['user_id'] ?>">
        <button type="submit" class="btn"><?= $is_following ? 'Deixar de seguir' : 'Seguir' ?></button>
    </form>
<?php endif; ?>

<?php if ($spot['visibility'] === 'privado'): ?>
    <p style="color:orange"><em>Este registo é privado e só tu o consegues ver.</em></p>
<?php endif; ?>

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
<?php if ($spot['visibility'] === 'publico'): ?>
<?php
$url_spot = 'http://' . $_SERVER['HTTP_HOST'] . '/views/simpatizante/spot.php?id=' . $spot['id'];
$texto    = urlencode($spot['description']);
$url_enc  = urlencode($url_spot);
?>
<div style="display: flex; gap: 10px; align-items: center; margin-top: 20px; flex-wrap: wrap;">
<a href="https://www.facebook.com/sharer/sharer.php?u=<?= $url_enc ?>" target="_blank" class="btn btn-secondary">Facebook</a>
<a href="https://twitter.com/intent/tweet?text=<?= $texto ?>&url=<?= $url_enc ?>" target="_blank" class="btn btn-secondary">Twitter</a>
</div>
<?php endif; ?>
</div>

<?php require_once '../footer.php'; ?>