<?php
// esta página é utilizada apenas pelo administrador e é onde ele gere as categorias principais da plataforma
// as categorias principais são o primeiro nível da hierarquia, por exemplo "Transportes Públicos" ou "Espaço Público"
// as categorias secundárias ficam dentro destas e são criadas pelos simpatizantes no subcategories.php
require_once '../../auth.php';
require_once '../../db.php';

// garantimos que só o admin chega aqui, qualquer outro perfil recebe um 403 de acesso negado
require_role('admin');

// lemos os feedbacks da sessão e apagamo-los logo a seguir
// o category_action.php é quem os escreve depois de processar cada operação de criar, editar ou apagar
// o unset faz com que não ficam guardados e não reaparecem numa visita futura à página
$error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);

// procuramos todas as categorias principais com o nome do utilizador que as criou
// o LEFT JOIN com users serve para mostrar o username mesmo que o utilizador tenha sido apagado entretanto
// ao contrário de um JOIN normal, o LEFT JOIN devolve a linha mesmo que não haja correspondência em users
// nesse caso o campo criado_por chega a NULL e nós mostramos "—" na tabela
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

<!-- formulário de criação de uma nova categoria principal -->
<!-- o campo hidden action diz ao category_action.php qual a operação a executar -->
<!-- assim o mesmo controlador consegue criar, editar ou apagar consoante este valor -->
<h2>Criar nova categoria</h2>
<form method="POST" action="../../controllers/category_action.php">
    <input type="hidden" name="action" value="create">
    <label>Nome: <input type="text" name="name" required></label>
    <button type="submit">Criar</button>
</form>

<!-- listz de todas as categorias principais existentes na base de dados -->
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
                    <!-- a edição do nome é feita inline na própria tabela com um pequeno formulário por linha -->
                    <!-- assim o admin não precisa de ir a uma página separada só para mudar o nome de uma categoria -->
                    <!-- o campo hidden id diz ao controlador qual a categoria específica a atualizar -->
                    <form method="POST" action="../../controllers/category_action.php" style="display:inline">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>" required>
                        <button type="submit">Guardar</button>
                    </form>
                </td>
                <td><?= htmlspecialchars($cat['criado_por'] ?? '—') ?></td>
                <td>
                    <!-- o onsubmit com confirm faz aparecer uma caixa de diálogo no browser a pedir confirmação  antes de enviar o formulário, 
                     o que evita que o admin apague uma categoria por engano -->
                    <!-- se clicar em cancelar o return false impede o formulário de ser enviado -->
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