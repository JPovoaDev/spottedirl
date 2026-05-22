<?php
// esta página permite ao simpatizante editar a descrição, localização, raridade e visibilidade de um registo seu
// o ficheiro em si não pode ser alterado, só a informação associada
require_once '../../auth.php';
require_once '../../db.php';
require_role('simpatizante');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: my_spots.php'); exit; }

// confirmamos que o registo existe e pertence ao utilizador atual
// um simpatizante não pode aceder à edição de registos que não são seus mesmo que saiba o id
$stmt = $pdo->prepare("SELECT * FROM spots WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$spot = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$spot) {
    echo '<p>Registo não encontrado ou não te pertence.</p>';
    echo '<a href="my_spots.php">&#8592; Voltar</a>';
    exit;
}

// apanhamos a metainfo atual para preencher o formulário com os valores que já existem
// assim o utilizador não tem de escrever tudo de novo, só o que quer alterar
$meta_stmt = $pdo->prepare("SELECT meta_key, meta_value FROM spot_meta WHERE spot_id = ?");
$meta_stmt->execute([$id]);
$metas = $meta_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
$page_title = 'Editar Registo';
require_once '../header.php';
?>
<h1>Editar Registo</h1>

<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

<form method="POST" action="../../controllers/spot_action.php">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="spot_id" value="<?= $spot['id'] ?>">

    <label>Descrição:
        <!-- preenchemos o textarea com a descrição atual para o utilizador não ter de a reescrever -->
        <textarea name="descricao" rows="4" required><?= htmlspecialchars($spot['description']) ?></textarea>
    </label>

    <label>Localização:
        <input type="text" name="localizacao" value="<?= htmlspecialchars($metas['localizacao'] ?? '') ?>">
    </label>

    <label>Raridade:
        <select name="raridade">
            <!-- marcamos como selected a opção que já estava guardada na bd -->
            <?php foreach (['comum', 'raro', 'excecional'] as $r): ?>
                <option value="<?= $r ?>" <?= ($metas['raridade'] ?? '') === $r ? 'selected' : '' ?>>
                    <?= ucfirst($r) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Visibilidade:
        <select name="visibilidade">
            <option value="publico"  <?= $spot['visibility'] === 'publico'  ? 'selected' : '' ?>>Público</option>
            <option value="privado"  <?= $spot['visibility'] === 'privado'  ? 'selected' : '' ?>>Privado</option>
        </select>
    </label>

    <button type="submit" class="btn">Guardar alterações</button>
</form>

<div style="margin-top: 20px;">
<a href="my_spots.php" class="btn btn-secondary">&#8592; Voltar aos meus registos</a>
</div>
<?php require_once '../footer.php'; ?>
