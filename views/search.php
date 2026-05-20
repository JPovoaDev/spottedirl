<?php
session_start();
require_once '../db.php';

$where = ["s.visibility = 'publico'"];
$params = [];

if (!empty($_GET['q'])) {
    $where[] = "s.description LIKE ?";
    $params[] = '%' . $_GET['q'] . '%';
}
if (!empty($_GET['categoria'])) {
    $where[] = "EXISTS (SELECT 1 FROM spot_categories sc WHERE sc.spot_id = s.id AND sc.category_id = ?)";
    $params[] = (int)$_GET['categoria'];
}
if (!empty($_GET['localizacao'])) {
    $where[] = "EXISTS (SELECT 1 FROM spot_meta sm WHERE sm.spot_id = s.id AND sm.meta_key = 'localizacao' AND sm.meta_value LIKE ?)";
    $params[] = '%' . $_GET['localizacao'] . '%';
}
if (!empty($_GET['raridade'])) {
    $where[] = "EXISTS (SELECT 1 FROM spot_meta sm WHERE sm.spot_id = s.id AND sm.meta_key = 'raridade' AND sm.meta_value = ?)";
    $params[] = $_GET['raridade'];
}

$sql = "SELECT s.*, u.username FROM spots s
        JOIN users u ON u.id = s.user_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><title>Pesquisa – SpottedIRL</title></head>
<body>
<?php require_once 'header.php'; ?>
<h1>Pesquisa</h1>

<form method="GET" action="search.php">
    <input type="text" name="q" placeholder="Texto livre..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">

    <select name="categoria">
        <option value="">— Categoria —</option>
        <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($_GET['categoria'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="text" name="localizacao" placeholder="Localização..." value="<?= htmlspecialchars($_GET['localizacao'] ?? '') ?>">

    <select name="raridade">
        <option value="">— Raridade —</option>
        <option value="comum" <?= ($_GET['raridade'] ?? '') === 'comum' ? 'selected' : '' ?>>Comum</option>
        <option value="raro" <?= ($_GET['raridade'] ?? '') === 'raro' ? 'selected' : '' ?>>Raro</option>
        <option value="excecional" <?= ($_GET['raridade'] ?? '') === 'excecional' ? 'selected' : '' ?>>Excecional</option>
    </select>

    <button type="submit">Pesquisar</button>
</form>

<h2>Resultados (<?= count($spots) ?>)</h2>
<?php if (empty($spots)): ?>
    <p>Nenhum resultado encontrado.</p>
<?php else: ?>
    <?php foreach ($spots as $spot): ?>
        <div style="border:1px solid #ccc; margin:10px; padding:10px">
            <strong><?= htmlspecialchars($spot['username']) ?></strong>
            — <?= htmlspecialchars($spot['type']) ?>
            — <?= htmlspecialchars($spot['created_at']) ?><br>
            <?= htmlspecialchars($spot['description']) ?><br>
            <a href="spot.php?id=<?= $spot['id'] ?>">Ver detalhe</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<a href="../index.php">&#8592; Voltar</a>
<?php require_once 'footer.php'; ?>
</body>
</html>