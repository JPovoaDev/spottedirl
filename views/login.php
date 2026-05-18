<?php
// iniciamos a sessão para poder ler mensagens de erro/sucesso guardadas por outros ficheiros
session_start();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Login – SpottedIRL</title>
</head>
<body>
    <h1>Login</h1>

    <?php if (!empty($_SESSION['error'])): ?>
        <!-- mostramos o erro a vermelho e apagamos da sessão com o unset para não aparecer outra vez ao dar refresh -->
        <p style="color:red"><?= $_SESSION['error'] ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <!-- mensagem de sucesso a verde, por exemplo quando vimos do registo de uma conta nova -->
        <p style="color:green"><?= $_SESSION['success'] ?></p>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- usamos method POST para os dados não aparecerem no URL -->
    <form method="POST" action="../controllers/login_action.php">
        <label>Username: <input type="text" name="username" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <button type="submit">Entrar</button>
    </form>
    <a href="register.php">Criar conta</a>
</body>
</html>