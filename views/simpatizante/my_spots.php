<?php
// esta página lista todos os registos do simpatizante que está logged in
// ao contrário da página pública, aqui mostramos tanto os registos públicos como os privados
// porque o utilizador tem o direito de ver e gerir tudo o que publicou
require_once '../../auth.php';
require_once '../../db.php';
require_role('simpatizante');

$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);

// apanhamos todos os registos do utilizador atual independentemente da visibilidade
// o ORDER BY created_at DESC mostra os mais recentes primeiro
$stmt = $pdo->prepare(
    "SELECT id, type, filename, description, visibility, created_at
     FROM spots
     WHERE user_id = ?
     ORDER BY created_at DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$spots = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Os meus registos – SpottedIRL</title>
</head>
<body>
<h1>Os meus registos</h1>

<?php if ($error):   ?><p style="color:red"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p style="color:green"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<?php if (empty($spots)): ?>
    <p>Ainda não publicaste nenhum registo. <a href="upload.php">Criar registo</a></p>
<?php else: ?>
    <?php foreach ($spots as $spot): ?>
        <div style="border:1px solid #ccc; margin-bottom:16px; padding:12px;">
            <strong><?= htmlspecialchars($spot['type']) ?></strong>
            — <?= htmlspecialchars($spot['created_at']) ?>
            <!-- mostramos a visibilidade a cores para ser mais fácil de identificar registos privados de uma vez -->
            — Visibilidade: <em style="color:<?= $spot['visibility'] === 'publico' ? 'green' : 'red' ?>">
                <?= htmlspecialchars($spot['visibility']) ?>
            </em><br>
            <?= htmlspecialchars($spot['description']) ?><br>

            <!-- mostramos o ficheiro consoante o tipo, da mesma maneira que nas outras páginas -->
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

            <!-- link para a página de edição deste registo específico -->
            <a href="edit_spot.php?id=<?= $spot['id'] ?>">Editar</a> |

            <!-- o confirm evita apagar por engano, e o formulário POST impede que o URL seja chamado diretamente -->
            <form method="POST" action="../../controllers/spot_action.php" style="display:inline"
                  onsubmit="return confirm('Tens a certeza que queres apagar este registo?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="spot_id" value="<?= $spot['id'] ?>">
                <button type="submit" style="color:red">Apagar</button>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<a href="dashboard.php">&#8592; Voltar ao painel</a> |
<a href="upload.php">Novo registo</a>
</body>
</html>
