<?php
require_once '../../auth.php';
require_once '../../db.php';
require_role('admin');

$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);

// lemos as configurações atuais para preencher o formulário com os valores já guardados
$cfg_stmt = $pdo->query("SELECT config_key, config_value FROM system_config");
$cfg = [];
foreach ($cfg_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $cfg[$row['config_key']] = $row['config_value'];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><title>Config. Email – Admin</title></head>
<body>
<h1>Configurações de Email e API</h1>

<?php if ($error):   ?><p style="color:red"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p style="color:green"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<form method="POST" action="../../controllers/email_config_action.php">
    <label>Servidor SMTP (host):
        <input type="text" name="smtp_host" value="<?= htmlspecialchars($cfg['smtp_host'] ?? 'mailhog') ?>" required>
    </label><br><br>

    <label>Porta SMTP:
        <input type="number" name="smtp_port" value="<?= htmlspecialchars($cfg['smtp_port'] ?? '1025') ?>" required>
    </label><br><br>

    <label>Email de envio (From):
        <input type="email" name="smtp_from" value="<?= htmlspecialchars($cfg['smtp_from'] ?? '') ?>" required>
    </label><br><br>

    <label>Nome de envio:
        <input type="text" name="smtp_from_name" value="<?= htmlspecialchars($cfg['smtp_from_name'] ?? 'SpottedIRL') ?>">
    </label><br><br>

    <label>Chave API DeepL (opcional):
        <input type="text" name="deepl_api_key" value="<?= htmlspecialchars($cfg['deepl_api_key'] ?? '') ?>" style="width:400px">
    </label><br><br>

    <button type="submit">Guardar configurações</button>
</form>

<a href="dashboard.php">&#8592; Voltar ao painel</a>
</body>
</html>