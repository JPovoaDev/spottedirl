<?php
session_start();
require_once '../db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare(
    "SELECT s.*, u.username FROM spots s
     JOIN users u ON u.id = s.user_id
     WHERE s.id = ? AND s.visibility = 'publico'"
);
$stmt->execute([$id]);
$spot = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$spot) { echo "Registo não encontrado."; exit; }

// buscar metainfo
$meta_stmt = $pdo->prepare("SELECT meta_key, meta_value FROM spot_meta WHERE spot_id = ?");
$meta_stmt->execute([$id]);
$metas = $meta_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><title>Registo – SpottedIRL</title></head>
<body>
<a href="../index.php">← Voltar</a>
<h1><?= htmlspecialchars($spot['description']) ?></h1>
<p>Por: <?= htmlspecialchars($spot['username']) ?> | <?= htmlspecialchars($spot['created_at']) ?></p>

<?php if ($spot['type'] === 'foto'): ?>
    <img src="../uploads/<?= htmlspecialchars($spot['filename']) ?>" style="max-width:600px"><br>
<?php elseif ($spot['type'] === 'video'): ?>
    <video controls style="max-width:600px">
        <source src="../uploads/<?= htmlspecialchars($spot['filename']) ?>">
    </video><br>
<?php elseif ($spot['type'] === 'audio'): ?>
    <audio controls>
        <source src="../uploads/<?= htmlspecialchars($spot['filename']) ?>">
    </audio><br>
<?php endif; ?>

<h2>Metainfo</h2>
<p>Localização: <?= htmlspecialchars($metas['localizacao'] ?? '—') ?></p>
<p>Hora do dia: <?= htmlspecialchars($metas['hora_do_dia'] ?? '—') ?></p>
<p>Raridade: <?= htmlspecialchars($metas['raridade'] ?? '—') ?></p>
</body>
</html>