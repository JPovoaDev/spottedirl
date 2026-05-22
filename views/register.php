<?php
// iniciamos a sessão para poder ler mensagens de erro/sucesso guardadas pelos controladores
session_start();
$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);
?>
<?php require_once 'header.php'; ?>
    <h1>Registar</h1>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" action="../controllers/register_action.php">
        <label>Username: <input type="text" name="username" required></label>
        <label>Email: <input type="email" name="email" required></label>
        <label>Password: <input type="password" name="password" required></label>
        <button type="submit">Registar</button>
    </form>
    <a href="login.php">Já tenho conta</a>
<?php require_once 'footer.php'; ?>