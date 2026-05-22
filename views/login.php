<?php
// iniciamos a sessão para poder ler mensagens de erro/sucesso guardadas por outros ficheiros
session_start();
?>
<?php require_once 'header.php'; ?>
    <h1>Login</h1>

    <?php if (!empty($_SESSION['error'])): ?>
        <!-- mostramos o erro a vermelho e apagamos da sessão com o unset para não aparecer outra vez ao dar refresh -->
        <p class="error"><?= htmlspecialchars($_SESSION['error']) ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <!-- mensagem de sucesso a verde, por exemplo quando vimos do registo de uma conta nova -->
        <p class="success"><?= htmlspecialchars($_SESSION['success']) ?></p>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- usamos method POST para os dados não aparecerem no URL -->
    <form method="POST" action="../controllers/login_action.php">
        <label>Username: <input type="text" name="username" required></label>
        <label>Password: <input type="password" name="password" required></label>
        <button type="submit">Entrar</button>
    </form>
    <a href="register.php">Criar conta</a>
<?php require_once 'footer.php'; ?>