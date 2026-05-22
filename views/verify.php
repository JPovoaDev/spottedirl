<?php
// Página de introdução do código de verificação enviado por email
session_start();

if (empty($_SESSION['pending_user_id'])) {
    header('Location: login.php');
    exit;
}

$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);
$page_title = 'Confirmar Conta';
require_once 'header.php';
?>
    <h1>Confirmar Conta</h1>
    <p>Introduz o código de 6 dígitos que enviámos para o teu email para ativar a tua conta.</p>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" action="../controllers/verify_action.php">
        <label>Código de Confirmação: 
            <input type="text" name="token" required maxlength="6" pattern="[0-9]{6}" placeholder="123456" autocomplete="off" style="font-size: 1.2rem; letter-spacing: 4px; text-align: center; width: 120px;">
        </label>
        <button type="submit">Confirmar</button>
    </form>
    
    <form method="POST" action="../controllers/resend_verification.php" style="margin-top: 10px;">
        <button type="submit" class="btn">Não recebi o email — reenviar código</button>
    </form>
    
    <div style="margin-top: 20px;">
        <a href="login.php">Voltar para o Login</a>
    </div>
<?php require_once 'footer.php'; ?>
