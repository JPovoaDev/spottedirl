<?php
session_start();
require_once '../db.php';

// a pesquisa é feita apenas sobre registos públicos, por isso começamos com essa condição base 
// e depois vamos adicionando mais condições conforme os filtros que o utilizador escolher
$where = ["s.visibility = 'publico'"];
$params = [];

// se o utilizador preencheu o campo de texto livre, adicionamos uma condição para procurar esse texto na descrição do registo
if (!empty($_GET['q'])) {
    // a condição usa LIKE para permitir encontrar o texto em qualquer parte da descrição,
    //  e os % são curingas que permitem isso. O valor do parâmetro é o texto que o utilizador digitou, 
    // mas com % antes e depois para permitir a correspondência parcial.
    $where[] = "s.description LIKE ?";
    $params[] = '%' . $_GET['q'] . '%';
}
// se o utilizador escolheu uma categoria, adicionamos uma condição para verificar se o registo pertence a essa categoria
if (!empty($_GET['categoria'])) {
    // a condição usa EXISTS para verificar se existe um registo na tabela spot_categories que liga o registo atual (s.id) 
    // à categoria escolhida (categoria_id)
    $where[] = "EXISTS (SELECT 1 FROM spot_categories sc WHERE sc.spot_id = s.id AND sc.category_id = ?)";
    // o valor do parâmetro é o ID da categoria escolhida, que vem do formulário como uma string, 
    // mas aqui convertemos para inteiro para garantir que é um número e evitar possíveis erros ou ataques de injeção SQL. 
    // O (int) é um cast que força a conversão para inteiro, e se o valor não for um número válido, ele se torna 0.
    $params[] = (int)$_GET['categoria'];
}
// se o utilizador preencheu o campo de localização, adicionamos uma condição para procurar essa localização na metainformação dos registos
if (!empty($_GET['localizacao'])) {
    // a condição é semelhante à do campo de texto livre, mas aqui procuramos na tabela spot_meta onde a chave é 'localizacao'
    //  e o valor contém o texto digitado pelo utilizador.
    $where[] = "EXISTS (SELECT 1 FROM spot_meta sm WHERE sm.spot_id = s.id AND sm.meta_key = 'localizacao' AND sm.meta_value LIKE ?)";
    $params[] = '%' . $_GET['localizacao'] . '%';
}
// se o utilizador escolheu uma raridade, adicionamos uma condição para verificar se o registo tem essa raridade na metainformação
if (!empty($_GET['raridade'])) {
    // a condição é semelhante à do campo de localização, mas aqui procuramos na tabela spot_meta onde a chave é 'raridade' 
    // e o valor é exatamente o que o utilizador escolheu (comum, raro ou excecional).
    $where[] = "EXISTS (SELECT 1 FROM spot_meta sm WHERE sm.spot_id = s.id AND sm.meta_key = 'raridade' AND sm.meta_value = ?)";
    $params[] = $_GET['raridade'];
}

// depois de construir a lista de condições e parâmetros, montamos a query final usando implode para juntar as condições com AND
$sql = "SELECT s.*, u.username FROM spots s
        JOIN users u ON u.id = s.user_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY s.created_at DESC";

// executamos a query usando os parâmetros que foram acumulados na construção das condições, e depois buscamos os resultados para mostrar na página
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// para mostrar as opções de categoria no formulário de pesquisa, precisamos buscar a lista de categorias da base de dados
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Pesquisa';
require_once 'header.php';
?>
<h1>Pesquisa</h1>

<!-- o formulário de pesquisa tem vários campos para o utilizador preencher, e cada campo corresponde a uma condição que pode ser adicionada à query de busca -->
<form method="GET" action="search.php">
    <!-- o campo de texto livre permite ao utilizador digitar qualquer palavra ou frase para procurar na descrição dos registos, e o valor do campo é preenchido
      com o que o utilizador digitou para manter a busca após o envio do formulário -->
    <input type="text" name="q" placeholder="Texto livre..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">

    <select name="categoria">
        <option value="">— Categoria —</option>
        <?php foreach ($cats as $c): ?>
            <!-- para cada categoria disponível, mostramos uma opção no select, 
             e se a categoria for a que o utilizador escolheu, marcamos como selected -->
            <option value="<?= $c['id'] ?>" <?= ($_GET['categoria'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
        <!-- o campo de localização permite ao utilizador digitar um local para procurar nos registos, 
         e o valor do campo é preenchido com o que o utilizador digitou -->
    <input type="text" name="localizacao" placeholder="Localização..." value="<?= htmlspecialchars($_GET['localizacao'] ?? '') ?>">

    <select name="raridade">
        <option value="">— Raridade —</option>
        <!-- o campo de raridade é um select com opções fixas (comum, raro, excecional),
          e se a opção for a que o utilizador escolheu, marcamos como selected -->
        <option value="comum" <?= ($_GET['raridade'] ?? '') === 'comum' ? 'selected' : '' ?>>Comum</option>
        <option value="raro" <?= ($_GET['raridade'] ?? '') === 'raro' ? 'selected' : '' ?>>Raro</option>
        <option value="excecional" <?= ($_GET['raridade'] ?? '') === 'excecional' ? 'selected' : '' ?>>Excecional</option>
    </select>

    <button type="submit" class="btn">Pesquisar</button>
</form>

<!-- depois do formulário, mostramos os resultados da pesquisa, indicando quantos foram encontrados -->
 <!-- se não houver resultados, mostramos uma mensagem indicando isso, caso contrário, mostramos uma lista dos registos encontrados -->
<h2>Resultados (<?= count($spots) ?>)</h2>
<?php if (empty($spots)): ?>
    <p>Nenhum resultado encontrado.</p>
<?php else: ?>
    <!-- para cada registo encontrado, mostramos um resumo com o username do criador, o tipo de registo, a data de criação e a descrição, 
     e um link para ver o detalhe do registo -->
    <?php foreach ($spots as $spot): ?>
        <div class="spot-card">
            <strong><?= htmlspecialchars($spot['username']) ?></strong>
            — <?= htmlspecialchars($spot['type']) ?>
            — <?= htmlspecialchars($spot['created_at']) ?><br>
            <?= htmlspecialchars($spot['description']) ?><br>

            <?php
            // buscamos as categorias associadas a este spot para mostrar os botões de subscrição
            $spot_cats = $pdo->prepare(
                "SELECT c.id, c.name FROM categories c
                 JOIN spot_categories sc ON sc.category_id = c.id
                 WHERE sc.spot_id = ?"
            );
            $spot_cats->execute([$spot['id']]);
            $spot_cat_list = $spot_cats->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <?php if (!empty($spot_cat_list) && !empty($_SESSION['user_id'])): ?>
                <?php foreach ($spot_cat_list as $sc): ?>
                    <?php
                    // verificamos se o utilizador já está subscrito a esta categoria
                    $is_sub = $pdo->prepare(
                        "SELECT 1 FROM subscriptions WHERE user_id = ? AND category_id = ?"
                    );
                    $is_sub->execute([$_SESSION['user_id'], $sc['id']]);
                    $subscribed = (bool)$is_sub->fetchColumn();
                    ?>
                    <form method="POST" action="../controllers/subscribe_action.php" style="display:inline">
                        <input type="hidden" name="action" value="<?= $subscribed ? 'unsubscribe' : 'subscribe' ?>">
                        <input type="hidden" name="category_id" value="<?= $sc['id'] ?>">
                        <button type="submit" class="btn btn-secondary">
                            <?= $subscribed ? '🔕 Cancelar' : '🔔 Subscrever' ?>
                            <?= htmlspecialchars($sc['name']) ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>
            <a href="spot.php?id=<?= $spot['id'] ?>" class="btn">Ver detalhe</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<a href="../index.php">&#8592; Voltar</a>
<?php require_once 'footer.php'; ?>