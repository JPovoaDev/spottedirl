<?php
// Página de introdução do código de verificação enviado por email
session_start();

if (empty($_SESSION['pending_user_id'])) {
    header('Location: login.php');
    exit;
}

$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Conta – SpottedIRL</title>
</head>
<body>
    <h1>Confirmar Conta</h1>
    <p>Introduz o código de 6 dígitos que enviámos para o teu email para ativar a tua conta.</p>

    <?php if ($error): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p style="color:green"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" action="../controllers/verify_action.php">
        <label>Código de Confirmação: 
            <input type="text" name="token" required maxlength="6" pattern="[0-9]{6}" placeholder="123456" autocomplete="off" style="font-size: 1.2rem; letter-spacing: 4px; text-align: center; width: 120px;">
        </label><br><br>
        <button type="submit">Confirmar</button>
    </form>
    <br>
    <a href="login.php">Voltar para o Login</a>
</body>
</html>
