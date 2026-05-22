<?php

//Aqui fazemos as juncoes necessarias das tabelas da base de dados para mostrar tudo o que necessitamos para mostrar os registos publicos
// , como o username do utilizador que fez o upload, a descrição, o tipo e a data de criação. 
// A query seleciona apenas os registos com visibilidade "publico" e ordena por data de criação decrescente para mostrar os 
// mais recentes primeiro.

$stmt = $pdo->query(
    "SELECT s.*, u.username FROM spots s
     JOIN users u ON u.id = s.user_id
     WHERE s.visibility = 'publico'
     ORDER BY s.created_at DESC"
);

$spots = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Aqui é onde mostra os registos públicos -->
<h2>Registos públicos</h2>
<?php if (empty($spots)): ?>
    <p>Nenhum registo ainda.</p>
<?php else: ?>
<!-- Mostra o username o tipo a hora e a desricao vindas do spot qeu a query retirou-->
    <?php foreach ($spots as $spot): ?>
    <div class="spot-card">
        <strong><?= htmlspecialchars($spot['username']) ?></strong><br>
        <strong><?= htmlspecialchars($spot['type']) ?></strong>
        — <?= htmlspecialchars($spot['created_at']) ?><br>

        <!-- Aqui mostramos o ficheiro do registo, se for foto mostramos a imagem, se for video mostramos o video e se for audio mostramos 
        o audio player -->
        <?php if ($spot['type'] === 'foto'): ?>
            <img src="uploads/<?= htmlspecialchars($spot['filename']) ?>">
        <?php elseif ($spot['type'] === 'video'): ?>
            <video controls>
                <source src="uploads/<?= htmlspecialchars($spot['filename']) ?>">
            </video>
        <?php elseif ($spot['type'] === 'audio'): ?>
            <audio controls>
                <source src="uploads/<?= htmlspecialchars($spot['filename']) ?>">
            </audio>
        <?php endif; ?>
        <!-- O link "Ver detalhe" leva à página spot.php onde mostramos toda a informação detalhada do registo, 
        como a localização, a hora do dia e a raridade, que são metainformações que guardamos numa tabela separada chamada spot_meta -->
        <p style="margin-bottom: 15px; font-size: 1.1em;"><?= htmlspecialchars($spot['description']) ?></p>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="views/spot.php?id=<?= $spot['id'] ?>" class="btn">Ver detalhe</a>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
