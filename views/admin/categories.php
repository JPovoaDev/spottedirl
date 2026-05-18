<?php
require_once '../../auth.php';
require_once '../../db.php';
require_role('admin');

// Mensagens de feedback
$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);

// Listar categorias principais
$stmt = $pdo->query(
    "SELECT c.*, u.username AS criado_por
     FROM categories c
     LEFT JOIN users u ON u.id = c.created_by
     WHERE c.type = 'principal'
     ORDER BY c.name"
);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Categorias Principais – Admin</title>
</head>
<body>
<h1>Gestão de Categorias Principais</h1>

<?php if ($error):   ?><p style="color:red"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p style="color:green"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<!-- Formulário de criação -->
<h2>Criar nova categoria</h2>
<form method="POST" action="../../controllers/category_action.php">
    <input type="hidden" name="action" value="create">
    <label>Nome: <input type="text" name="name" required></label>
    <button type="submit">Criar</button>
</form>

<!-- Listagem -->
<h2>Categorias existentes</h2>
<?php if (empty($categories)): ?>
    <p>Nenhuma categoria criada ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr><th>ID</th><th>Nome</th><th>Criada por</th><th>Ações</th></tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?= $cat['id'] ?></td>
                <td>
                    <!-- Edição inline via form -->
                    <form method="POST" action="../../controllers/category_action.php" style="display:inline">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>" required>
                        <button type="submit">Guardar</button>
                    </form>
                </td>
                <td><?= htmlspecialchars($cat['criado_por'] ?? '—') ?></td>
                <td>
                    <form method="POST" action="../../controllers/category_action.php"
                          onsubmit="return confirm('Apagar categoria e todas as secundárias?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <button type="submit" style="color:red">Apagar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<a href="dashboard.php">← Voltar ao painel</a>
</body>
</html>
