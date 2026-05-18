// quando o utilizador dá login aparece lhe a página principal

<?php
// o session start inicia uma sessão HTTP
// como o php não guarda informação entre paginas o session start cria um cookie no browser com um ID único por utilizador
// e a partir daí o PHP pode guardar e ler variáveis como $_SESSION['username'] que persistem enquanto o mesmo navega 
// pelo website. sem o sesison start o utilizador daria login e já não saberiamos quem ele é
session_start();

// mostramos os header e footer com base nos seus ficheiros e chamamos a base de dados
require_once 'db.php';
require_once 'views/header.php';

// html a dizer que a base de dados foi ligada
echo "<h1>SpottedIRL</h1>";
echo "<p>Base de dados ligada com sucesso.</p>";

require_once 'views/footer.php';
?>