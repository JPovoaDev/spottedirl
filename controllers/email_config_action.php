<?php
// controlador para guardar as configurações de email do sistema — só o admin tem acesso
session_start();
require_once '../db.php';
require_once '../auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/admin/email_config.php');
    exit;
}

// lista de todas as chaves esperadas do formulário
$keys = ['smtp_host', 'smtp_port', 'smtp_from', 'smtp_from_name', 'deepl_api_key'];

foreach ($keys as $key) {
    $value = trim($_POST[$key] ?? '');
    // ON DUPLICATE KEY UPDATE porque a chave pode já existir na tabela
    $pdo->prepare(
        "INSERT INTO system_config (config_key, config_value)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE config_value = ?"
    )->execute([$key, $value, $value]);
}

$_SESSION['success'] = 'Configurações guardadas.';
header('Location: ../views/admin/email_config.php');
exit;