<?php
session_start();
require_once '../db.php';
require_once '../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/index.php');
    exit;
}

$action = $_POST['action'] ?? '';

// ─── ADMIN: criar / editar / apagar categoria PRINCIPAL ───────────────────

if (in_array($action, ['create', 'update', 'delete'])) {
    require_role('admin');

    switch ($action) {

        case 'create':
            $name = trim($_POST['name'] ?? '');
            if (!$name) {
                $_SESSION['error'] = 'O nome da categoria não pode estar vazio.';
                header('Location: ../views/admin/categories.php');
                exit;
            }
            // Verificar duplicado
            $chk = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND type = 'principal'");
            $chk->execute([$name]);
            if ($chk->fetch()) {
                $_SESSION['error'] = 'Já existe uma categoria com esse nome.';
                header('Location: ../views/admin/categories.php');
                exit;
            }
            $stmt = $pdo->prepare(
                "INSERT INTO categories (name, type, parent_id, created_by)
                 VALUES (?, 'principal', NULL, ?)"
            );
            $stmt->execute([$name, $_SESSION['user_id']]);
            $_SESSION['success'] = "Categoria '$name' criada.";
            header('Location: ../views/admin/categories.php');
            exit;

        case 'update':
            $id   = (int)($_POST['id']   ?? 0);
            $name = trim($_POST['name'] ?? '');
            if (!$id || !$name) {
                $_SESSION['error'] = 'Dados inválidos.';
                header('Location: ../views/admin/categories.php');
                exit;
            }
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
            // Apagar secundárias filhas primeiro
            $del_sub = $pdo->prepare("DELETE FROM categories WHERE parent_id = ?");
            $del_sub->execute([$id]);
            $del = $pdo->prepare("DELETE FROM categories WHERE id = ? AND type = 'principal'");
            $del->execute([$id]);
            $_SESSION['success'] = 'Categoria e subcategorias apagadas.';
            header('Location: ../views/admin/categories.php');
            exit;
    }
}

// ─── SIMPATIZANTE: criar categoria SECUNDÁRIA ─────────────────────────────

if ($action === 'create_sub') {
    require_role('simpatizante');

    $name      = trim($_POST['name']      ?? '');
    $parent_id = (int)($_POST['parent_id'] ?? 0);

    if (!$name || !$parent_id) {
        $_SESSION['error'] = 'Nome e categoria principal são obrigatórios.';
        header('Location: ../views/simpatizante/subcategories.php');
        exit;
    }

    // Confirmar que o parent existe e é principal
    $chk = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND type = 'principal'");
    $chk->execute([$parent_id]);
    if (!$chk->fetch()) {
        $_SESSION['error'] = 'Categoria principal inválida.';
        header('Location: ../views/simpatizante/subcategories.php');
        exit;
    }

    // Verificar duplicado dentro da mesma principal
    $dup = $pdo->prepare(
        "SELECT id FROM categories WHERE name = ? AND parent_id = ? AND type = 'secundaria'"
    );
    $dup->execute([$name, $parent_id]);
    if ($dup->fetch()) {
        $_SESSION['error'] = 'Já existe uma subcategoria com esse nome aqui.';
        header('Location: ../views/simpatizante/subcategories.php');
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO categories (name, type, parent_id, created_by)
         VALUES (?, 'secundaria', ?, ?)"
    );
    $stmt->execute([$name, $parent_id, $_SESSION['user_id']]);
    $_SESSION['success'] = "Subcategoria '$name' criada.";
    header('Location: ../views/simpatizante/subcategories.php');
    exit;
}

// Ação desconhecida
header('Location: ../views/index.php');
exit;
