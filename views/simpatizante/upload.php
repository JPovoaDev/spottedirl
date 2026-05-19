<?php
session_start();
require_once '../../auth.php';
require_once '../../db.php';
require_role('simpatizante');

$error = $_SESSION['error'] ?? null; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);

// fazemos uma query para a bd de maneira a apanahr todas as categorias, tanto primarias como secundarias
$cats = $pdo->query("SELECT id, name, type, parent_id FROM categories ORDER BY type, name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><title>Upload – SpottedIRL</title></head>
<body>
<h1>Novo Registo</h1>
<?php if ($error): ?><p style="color:red"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p style="color:green"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<!--Formulario do upload -->
<!-- Uma vez este formulario for submetido, os dados serão processados pelo script upload_action.php -->
<form method="POST" action="../../controllers/upload_action.php" enctype="multipart/form-data">
    <label>Ficheiro (foto/vídeo/áudio):<br>
        <input type="file" name="ficheiro" required accept="image/*,video/*,audio/*">
    </label><br><br>

    <label>Descrição:<br>
        <textarea name="descricao" required></textarea>
    </label><br><br>

    <label>Categoria principal:
        <select name="categoria_principal">
            <option value="">— nenhuma —</option>
            <!-- Aqui é um dropdown que mostra todas as categorias que a query  retornou, se não retornar nenhuma, mostra "-nenhuma-"-->
            <?php foreach ($cats as $c): ?>
                <?php if ($c['type'] === 'principal'): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <label>Categoria secundária:
        <select name="categoria_secundaria">
            <option value="">— nenhuma —</option>
            <?php foreach ($cats as $c): ?>
                <?php if ($c['type'] === 'secundaria'): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <label>Localização: <input type="text" name="localizacao"></label><br><br>
    <label>Hora do dia:
        <select name="hora_do_dia">
            <option value="manha">Manhã</option>
            <option value="tarde">Tarde</option>
            <option value="noite">Noite</option>
        </select>
    </label><br><br>

    <label>Raridade:
        <select name="raridade">
            <option value="comum">Comum</option>
            <option value="raro">Raro</option>
            <option value="excecional">Excecional</option>
        </select>
    </label><br><br>

    <label>Visibilidade:
        <select name="visibilidade">
            <option value="publico">Público</option>
            <option value="privado">Privado</option>
        </select>
    </label><br><br>

    <button type="submit">Publicar</button>
</form>
<a href="dashboard.php">← Voltar</a>
</body>
</html>