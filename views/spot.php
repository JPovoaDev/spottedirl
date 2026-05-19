<?php
session_start();
require_once '../db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

// Aqui fazemos a query para buscar o registo específico com base no ID que vem na query string, 
// e só selecionamos se a visibilidade for "publico" para garantir que não mostramos registos privados
$stmt = $pdo->prepare(
    "SELECT s.*, u.username FROM spots s
     JOIN users u ON u.id = s.user_id
     WHERE s.id = ? AND s.visibility = 'publico'"
);
$stmt->execute([$id]);
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
<a href="../index.php">← Voltar</a>
<!-- Aqui mostramos o título do registo que é a descrição, o username do utilizador que fez o upload e a data de criação -->
<h1><?= htmlspecialchars($spot['description']) ?></h1>
<p>Por: <?= htmlspecialchars($spot['username']) ?> | <?= htmlspecialchars($spot['created_at']) ?></p>

<!-- Aqui mostramos o ficheiro do registo, se for foto mostramos a imagem, se for video mostramos o video e se for audio mostramos 
     o audio player -->
<?php if ($spot['type'] === 'foto'): ?>
    <!-- O caminho para o ficheiro é "../uploads/" porque estamos numa subpasta views e os ficheiros estão guardados 
     na pasta uploads que está no mesmo nível de views -->
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

<!-- Aqui mostramos a metainformação do registo, que são a localização, a hora do dia e a raridade, 
que são informações adicionais que o utilizador pode escolher preencher ou não no momento do upload 
 Se alguma dessas informações não tiver sido preenchida, mostramos "—" para indicar que não há informação disponível -->
<h2>Metainfo</h2>
<p>Localização: <?= htmlspecialchars($metas['localizacao'] ?? '—') ?></p>
<p>Hora do dia: <?= htmlspecialchars($metas['hora_do_dia'] ?? '—') ?></p>
<p>Raridade: <?= htmlspecialchars($metas['raridade'] ?? '—') ?></p>
</body>
</html>