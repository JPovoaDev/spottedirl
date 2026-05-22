<?php
// este controlador trata das operações de edição e remoção de registos feitas pelo próprio simpatizante
session_start();
require_once '../db.php';
require_once '../auth.php';

// se alguém tentar aceder diretamente pelo browser sem fazer um POST mandamos logo para os registos
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/simpatizante/my_spots.php');
    exit;
}

// só simpatizantes e acima podem ter registos e portanto editar ou apagar
require_role('simpatizante');

$action  = $_POST['action']  ?? '';
$spot_id = (int)($_POST['spot_id'] ?? 0);

if (!$spot_id) {
    $_SESSION['error'] = 'ID de registo inválido.';
    header('Location: ../views/simpatizante/my_spots.php');
    exit;
}

// verificamos que o registo existe e pertence ao utilizador atual antes de qualquer operação
// porque sem esta verificação qualquer simpatizante podia editar ou apagar registos de outros
$chk = $pdo->prepare("SELECT id, user_id FROM spots WHERE id = ?");
$chk->execute([$spot_id]);
$spot = $chk->fetch(PDO::FETCH_ASSOC);

if (!$spot || (int)$spot['user_id'] !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    echo '<h1>403 – Acesso negado</h1>';
    echo '<p>Este registo não te pertence.</p>';
    echo '<a href="../views/simpatizante/my_spots.php">Voltar aos meus registos</a>';
    exit;
}

switch ($action) {

    case 'edit':
        $descricao = trim($_POST['descricao'] ?? '');
        $visibilidade = $_POST['visibilidade'] ?? 'privado';

        if (!$descricao) {
            $_SESSION['error'] = 'A descrição não pode estar vazia.';
            header("Location: ../views/simpatizante/edit_spot.php?id=$spot_id");
            exit;
        }

        // vemos se o valor da visibilidade é um dos dois esperados, se vier outra coisa deixamos privado por defeito (por segurança)
        if (!in_array($visibilidade, ['publico', 'privado'])) {
            $visibilidade = 'privado';
        }

        $stmt = $pdo->prepare("UPDATE spots SET description = ?, visibility = ? WHERE id = ?");
        $stmt->execute([$descricao, $visibilidade, $spot_id]);

        // atualizamos a metainfo com ON DUPLICATE KEY UPDATE para não duplicar linhas
        // se a chave (spot_id, meta_key) já existir na tabela spot_meta só atualizamos o valor
        // para isto funcionar a tabela spot_meta precisa de ter um índice UNIQUE em (spot_id, meta_key)
        $loc = trim($_POST['localizacao'] ?? '');
        $pdo->prepare(
            "INSERT INTO spot_meta (spot_id, meta_key, meta_value) VALUES (?, 'localizacao', ?)
             ON DUPLICATE KEY UPDATE meta_value = ?"
        )->execute([$spot_id, $loc, $loc]);

        $rar = $_POST['raridade'] ?? 'comum';
        $pdo->prepare(
            "INSERT INTO spot_meta (spot_id, meta_key, meta_value) VALUES (?, 'raridade', ?)
             ON DUPLICATE KEY UPDATE meta_value = ?"
        )->execute([$spot_id, $rar, $rar]);

        $_SESSION['success'] = 'Registo atualizado.';
        header('Location: ../views/simpatizante/my_spots.php');
        exit;

    case 'delete':
        // apagamos a metainfo e as categorias associadas antes de apagar o registo em si
        // pela mesma razão do user_action.php, as foreign keys obrigam a limpar primeiro as tabelas filhas
        $stmtFile = $pdo->prepare("SELECT filename FROM spots WHERE id = ?");
        $stmtFile->execute([$spot_id]);
        $file = $stmtFile->fetchColumn();
        if ($file) {
            $path = '../uploads/' . $file;
            if (file_exists($path)) {
                unlink($path);
            }
        }
        $pdo->prepare("DELETE FROM spot_meta WHERE spot_id = ?")->execute([$spot_id]);
        $pdo->prepare("DELETE FROM spot_categories WHERE spot_id = ?")->execute([$spot_id]);
        $pdo->prepare("DELETE FROM spots WHERE id = ?")->execute([$spot_id]);

        $_SESSION['success'] = 'Registo apagado.';
        header('Location: ../views/simpatizante/my_spots.php');
        exit;
}

// action desconhecido, mandamos de volta para a lista de registos
header('Location: ../views/simpatizante/my_spots.php');
exit;
