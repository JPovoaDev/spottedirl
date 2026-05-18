<?php
// verificamos se a sessão já está ativa antes de a iniciar porque outras páginas já chamaram session_start 
// pois o header é incluido em muitas páginas
// nas páginas simples como o login.php sabemos que é a primeira coisa a correr então chamamos session_start() diretamente
// mas aqui no header como não sabemos o contexto em que vai ser incluído verificamos primeiro
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>SpottedIRL</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<nav>
    <a href="/index.php">SpottedIRL</a>
    <?php if (!empty($_SESSION['username'])): ?>
        <!-- se o utilizador estiver logged in mostramos o seu nome e o link de logout -->
        <!-- o htmlspecialchars protege contra XSS, ou seja se alguém tiver metido HTML no nome da categoria -->
        <!-- ele converte caracteres como < e > para as versões seguras &lt; e &gt; -->
        <!-- e assim o browser não interpreta o conteúdo como código HTML mas mostra-o como texto simples -->
        <span>Olá, <?= htmlspecialchars($_SESSION['username']) ?></span> |
        <a href="/controllers/logout.php">Logout</a>
    <?php else: ?>
        <!-- se não estiver logged in então mostramos os links de login e registo -->
        <a href="/views/login.php">Login</a> |
        <a href="/views/register.php">Registar</a>
    <?php endif; ?>
</nav>