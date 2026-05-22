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
$page_title = 'Config. Email';
require_once '../header.php';
?>
<h1>Configurações de Email e API</h1>

<?php if ($error):   ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

<form method="POST" action="../../controllers/email_config_action.php">
    <label>Servidor SMTP (host):
        <input type="text" name="smtp_host" value="<?= htmlspecialchars($cfg['smtp_host'] ?? 'mailhog') ?>" required>
    </label>

    <label>Porta SMTP:
        <input type="number" name="smtp_port" value="<?= htmlspecialchars($cfg['smtp_port'] ?? '1025') ?>" required>
    </label>

    <label>Email de envio (From):
        <input type="email" name="smtp_from" value="<?= htmlspecialchars($cfg['smtp_from'] ?? '') ?>" required>
    </label>

    <label>Nome de envio:
        <input type="text" name="smtp_from_name" value="<?= htmlspecialchars($cfg['smtp_from_name'] ?? 'SpottedIRL') ?>">
    </label>

    <label>Chave API DeepL (opcional):
        <input type="text" name="deepl_api_key" value="<?= htmlspecialchars($cfg['deepl_api_key'] ?? '') ?>" style="max-width: 100%;">
    </label>

    <button type="submit" class="btn">Guardar configurações</button>
</form>

<div style="margin-top: 20px;">
<a href="dashboard.php" class="btn btn-secondary">&#8592; Voltar ao painel</a>
</div>

<?php require_once '../footer.php'; ?>