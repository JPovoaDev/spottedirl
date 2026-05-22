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
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' – SpottedIRL' : 'SpottedIRL' ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<nav>
    <a href="/index.php">SpottedIRL</a>
    <a href="/views/search.php">Pesquisa</a>
    <?php if (!empty($_SESSION['username'])): ?>
        <?php
        // construímos o link do painel com base na role do utilizador
        $dashboard_url = match($_SESSION['role'] ?? '') {
            'admin'       => '/views/admin/dashboard.php',
            'simpatizante'=> '/views/simpatizante/dashboard.php',
            'user'        => '/views/user/dashboard.php',
            default       => '/index.php',
        };
        ?>
        <a href="<?= $dashboard_url ?>">Painel</a>
        <span>Olá, <?= htmlspecialchars($_SESSION['username']) ?></span>
        <a href="/controllers/logout.php">Logout</a>

    <?php else: ?>
        <!-- se não estiver logged in então mostramos os links de login e registo -->
        <a href="/views/login.php">Login</a>
        <a href="/views/register.php">Registar</a>
    <?php endif; ?>
</nav>
<main class="container">