<?php
// o require once basicamente apanha o código que está no ficheiro em questão e depois mete o aqui
require_once '../../auth.php'; // o auth define a função require_role e a hierarquia de perfis
require_once '../../db.php';   // a db define a variável $pdo que representa a ligação à base de dados

// verificamos logo à entrada que o utilizador tem pelo menos o perfil simpatizante
// só este perfil ou superior pode criar e ver as suas subcategorias
require_role('simpatizante');

// lemos as mensagens de feedback da sessão e apagamo-las
// estas mensagens são escritas pelo category_action.php depois de processar cada pedido
// por exemplo depois de criar uma subcategoria com sucesso ou de detetar um nome duplicado
// o unset logo a seguir faz com que não fiquem guardadas e não apareçam se dermos refresh
$error = $_SESSION['error'] ?? null; 
unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; 
unset($_SESSION['success']);

// apanhamos todas as categorias principais que o administrador criou
// o simpatizante não cria categorias principais, só subcategorias dentro delas por isso temos de ir apanhar a 
// lista para popular o dropdown do formulário de criação
$stmt = $pdo->query(
    "SELECT id, name FROM categories WHERE type = 'principal' ORDER BY name"
);
$principals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// vemos as subcategorias que este utilizador específico criou, usamos o user_id da sessão para filtrar só os registos 
// que pertencem a quem está logged in, depois o JOIN com a tabela categories "p" serve para mostrar o nome da categoria principal
// a que cada subcategoria pertence porque na base de dados guardamos apenas o parent_id e não o nome
// o ORDER BY p.name, c.name organiza primeiro por principal e depois por subcategoria dentro de cada uma
$stmt = $pdo->prepare(
    "SELECT c.id, c.name AS sub_name, p.name AS parent_name
     FROM categories c
     JOIN categories p ON p.id = c.parent_id
     WHERE c.type = 'secundaria' AND c.created_by = ?
     ORDER BY p.name, c.name"
);
$stmt->execute([$_SESSION['user_id']]);
$mysubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$page_title = 'Subcategorias';
require_once '../header.php';
?>

<h1>As minhas subcategorias</h1>

<?php if ($error):   ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<h2>Criar nova subcategoria</h2>
<?php if (empty($principals)): ?>
    <!-- se não existir nenhuma categoria principal ainda não faz sentido mostrar o formulário porque o simpatizante tem de obrigatoriamente -->
    <!-- associar a sua subcategoria a uma categoria principal -->
    <!-- enquanto o admin não criar pelo menos uma, esta funcionalidade fica bloqueada -->
    <p>Ainda não existem categorias principais criadas pelo administrador.</p>
<?php else: ?>
    <!-- o action aponta para o category_action.php que é o controlador de todas as operações sobre categorias -->
    <!-- o campo hidden action diz ao controlador qual o tipo de operação que queremos executar -->
    <!-- assim o mesmo ficheiro controlador consegue criar, editar ou apagar consoante o valor deste campo -->
    <form method="POST" action="../../controllers/category_action.php">
        <input type="hidden" name="action" value="create_sub">
        <label>
            Categoria principal:
            <!-- o dropdown é populado dinamicamente com as categorias que vieram da query lá em cima -->
            <!-- enviamos o id da categoria no value mas mostramos o nome ao utilizador -->
            <select name="parent_id" required>
                <option value="">— seleciona —</option>
                <?php foreach ($principals as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Nome da subcategoria: <input type="text" name="name" required></label>
        <button type="submit" class="btn">Criar</button>
    </form>
<?php endif; ?>

<h2>Subcategorias que criei</h2>
<?php if (empty($mysubs)): ?>
    <p>Ainda não criaste nenhuma subcategoria.</p>
<?php else: ?>
    <!-- mostramos as subcategorias numa tabela com o nome da principal e o da própria subcategoria -->
    <table border="1" cellpadding="6">
        <thead>
            <tr><th>Principal</th><th>Subcategoria</th></tr>
        </thead>
        <tbody>
        <?php foreach ($mysubs as $s): ?>
            <tr>
                <!-- tal como o header.php, o htmlspecialchars protege contra XSS, ou seja se alguém tiver metido HTML no nome da categoria -->
                <!-- ele converte caracteres como < e > para as versões seguras &lt; e &gt; -->
                <!-- e assim o browser não interpreta o conteúdo como código HTML mas mostra-o como texto simples -->
                <td><?= htmlspecialchars($s['parent_name']) ?></td>
                <td><?= htmlspecialchars($s['sub_name']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div style="margin-top: 20px;">
<a href="dashboard.php" class="btn btn-secondary">← Voltar ao painel</a>
</div>

<?php require_once '../footer.php'; ?>