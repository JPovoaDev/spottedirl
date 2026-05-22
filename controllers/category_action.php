<?php
// este ficheiro é o controlador central de todas as operações sobre categorias
// tanto as operações do admin (criar, editar e apagar categorias principais) como as do simpatizante (criar subcategorias) passam todas por aqui
// o campo action que vem no POST é o que decide o que fazer em cada caso
session_start();
require_once '../db.php';
require_once '../auth.php';

// se alguém tentar aceder a este ficheiro diretamente pelo browser sem fazer um POST redirecionamos logo para a página principal 
// porque este controlador não tem HTML para mostrar, a sua única função é receber dados, processá-los e redirecionar para a view certa
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/index.php');
    exit;
}

// lemos o campo action que os formulários das views enviam como campo hidden
// é o que nos diz qual operação foi pedida: create, update, delete ou create_sub
$action = $_POST['action'] ?? '';

// as operações create, update e delete só podem ser utilizadas pelo admin
// verificamos o perfil aqui dentro de cada bloco e não à entrada do ficheiro
// porque o mesmo ficheiro serve vários perfis e cada um tem acesso a ações diferentes
if (in_array($action, ['create', 'update', 'delete'])) {
    require_role('admin');

    switch ($action) {

        case 'create':
            // lemos o nome e limpamos espaços desnecessários no início e no fim com o trim
            $name = trim($_POST['name'] ?? '');
            if (!$name) {
                $_SESSION['error'] = 'O nome da categoria não pode estar vazio.';
                header('Location: ../views/admin/categories.php');
                exit;
            }

            // verificamos se já existe uma categoria principal com o mesmo nome, usamos um prepared statement para evitar SQL injection
            // ou seja nunca concatenamos o valor que vem do utilizador diretamente na query
            // o ponto de interrogação é o placeholder que o PDO substitui pelo valor de forma segura
            $chk = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND type = 'principal'");
            $chk->execute([$name]);
            if ($chk->fetch()) {
                $_SESSION['error'] = 'Já existe uma categoria com esse nome.';
                header('Location: ../views/admin/categories.php');
                exit;
            }

            // inserimos a nova categoria com type principal e sem parent_id porque é um nível de topo
            // o created_by fica com o id do admin que a criou para termos registo de quem fez o quê
            $stmt = $pdo->prepare(
                "INSERT INTO categories (name, type, parent_id, created_by)
                 VALUES (?, 'principal', NULL, ?)"
            );
            $stmt->execute([$name, $_SESSION['user_id']]);
            $_SESSION['success'] = "Categoria '$name' criada.";
            header('Location: ../views/admin/categories.php');
            exit;

        case 'update':
            // o id vem do campo hidden do formulário inline que está na tabela do categories.php
            // fazemos cast para inteiro para garantir que é mesmo um número e não qualquer outra coisa
            $id   = (int)($_POST['id']   ?? 0);
            $name = trim($_POST['name'] ?? '');
            if (!$id || !$name) {
                $_SESSION['error'] = 'Dados inválidos.';
                header('Location: ../views/admin/categories.php');
                exit;
            }

            // atualizamos só categorias do tipo principal para garantir que esta operação
            // não consegue alterar subcategorias mesmo que alguém manipule o id no formulário
            // a condição AND type = 'principal' funciona como uma camada extra de segurança
            $stmt = $pdo->prepare(
                "UPDATE categories SET name = ? WHERE id = ? AND type = 'principal'"
            );
            $stmt->execute([$name, $id]);
            $_SESSION['success'] = 'Categoria atualizada.';
            header('Location: ../views/admin/categories.php');
            exit;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                $_SESSION['error'] = 'ID inválido.';
                header('Location: ../views/admin/categories.php');
                exit;
            }

            // apagamos primeiro as referencias em spot_categories para não dar erro
            $pdo->prepare("DELETE FROM spot_categories WHERE category_id = ? OR category_id IN (SELECT id FROM categories WHERE parent_id = ?)")->execute([$id, $id]);

            // apagamos primeiro as subcategorias filhas e só depois a categoria principal
            // porque a base de dados tem uma foreign key de parent_id que impede de apagar uma categoria
            // que ainda tenha filhas o que faz com que a ordem das queries importe
            $del_sub = $pdo->prepare("DELETE FROM categories WHERE parent_id = ?");
            $del_sub->execute([$id]);
            $del = $pdo->prepare("DELETE FROM categories WHERE id = ? AND type = 'principal'");
            $del->execute([$id]);
            $_SESSION['success'] = 'Categoria e subcategorias apagadas.';
            header('Location: ../views/admin/categories.php');
            exit;
    }
}

// a criação de subcategorias é a funcionalidade do simpatizante
// o perfil admin tecnicamente também consegue porque tem peso maior na hierarquia
// mas na lógica da plataforma é o simpatizante quem cria as subcategorias dentro das principais
if ($action === 'create_sub') {
    require_role('simpatizante');

    $name = trim($_POST['name'] ?? '');
    $parent_id = (int)($_POST['parent_id'] ?? 0);

    if (!$name || !$parent_id) {
        $_SESSION['error'] = 'Nome e categoria principal são obrigatórios.';
        header('Location: ../views/simpatizante/subcategories.php');
        exit;
    }

    // confirmamos que o parent_id que chegou no formulário corresponde de facto a uma categoria principal
    // mesmo que o dropdown do subcategories.php só mostre opções válidas, sem esta verificação no servidor
    // alguém podia inspecionar o HTML e alterar o valor do campo antes de submeter
    $chk = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND type = 'principal'");
    $chk->execute([$parent_id]);
    if (!$chk->fetch()) {
        $_SESSION['error'] = 'Categoria principal inválida.';
        header('Location: ../views/simpatizante/subcategories.php');
        exit;
    }

    // verificamos se já existe uma subcategoria com o mesmo nome dentro desta categoria principal
    // o mesmo nome pode existir em categorias principais diferentes então filtramos também pelo parent_id
    // ou seja "Metro" pode existir em "Transportes Públicos" e noutra principal sem conflito
    $dup = $pdo->prepare(
        "SELECT id FROM categories WHERE name = ? AND parent_id = ? AND type = 'secundaria'"
    );
    $dup->execute([$name, $parent_id]);
    if ($dup->fetch()) {
        $_SESSION['error'] = 'Já existe uma subcategoria com esse nome aqui.';
        header('Location: ../views/simpatizante/subcategories.php');
        exit;
    }

    // inserimos a subcategoria com o parent_id da categoria principal que foi selecionada
    // e com o created_by do simpatizante que a criou para sabermos de quem é cada subcategoria
    // é este created_by que o subcategories.php usa para mostrar só as subcategorias do utilizador atual
    $stmt = $pdo->prepare(
        "INSERT INTO categories (name, type, parent_id, created_by)
         VALUES (?, 'secundaria', ?, ?)"
    );
    $stmt->execute([$name, $parent_id, $_SESSION['user_id']]);
    $_SESSION['success'] = "Subcategoria '$name' criada.";
    header('Location: ../views/simpatizante/subcategories.php');
    exit;
}

// se chegámos aqui é porque o action não era nenhum dos esperados
// pode ser uma tentativa de acesso direto com um valor de action inventado então mandamos para a página principal sem fazer nada
header('Location: ../views/index.php');
exit;