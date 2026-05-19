<?php


$stmt = $pdo->query(
    "SELECT s.*, u.username FROM spots s
     JOIN users u ON u.id = s.user_id
     WHERE s.visibility = 'publico'
     ORDER BY s.created_at DESC"
);
$spots = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Registos públicos</h2>
<?php if (empty($spots)): ?>
    <p>Nenhum registo ainda.</p>
<?php else: ?>
    <?php foreach ($spots as $spot): ?>
        <div style="border:1px solid #ccc; margin:10px; padding:10px">
    <strong><?= htmlspecialchars($spot['username']) ?></strong>
    — <?= htmlspecialchars($spot['type']) ?>
    — <?= htmlspecialchars($spot['created_at']) ?><br>
    <?= htmlspecialchars($spot['description']) ?><br>

    <?php if ($spot['type'] === 'foto'): ?>
        <img src="uploads/<?= htmlspecialchars($spot['filename']) ?>" style="max-width:400px"><br>
    <?php elseif ($spot['type'] === 'video'): ?>
        <video controls style="max-width:400px">
            <source src="uploads/<?= htmlspecialchars($spot['filename']) ?>">
        </video><br>
    <?php elseif ($spot['type'] === 'audio'): ?>
        <audio controls>
            <source src="uploads/<?= htmlspecialchars($spot['filename']) ?>">
        </audio><br>
    <?php endif; ?>

    <a href="views/spot.php?id=<?= $spot['id'] ?>">Ver detalhe</a>
</div>
    <?php endforeach; ?>
<?php endif; ?>
