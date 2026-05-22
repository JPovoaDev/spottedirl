<?php
// variáveis para localizarmos a base de dados: local, nome e credenciais de acesso
$host = '127.0.0.1';
$dbname = 'spottedirl';
$user = 'root';
$pass = '';

try {
    // PDO é a ligação com a BD, o objeto que é criado é o que vai ser usado nos outros ficheiros para fazer queries
    // usamos o PDO em vez do mysqli porque permite-nos fazer debug com try/catch e exceptions caso alguma coisa falhe
    // e assim sabemos porque e porque é mais escalável (podemos alterar o MySQL por outra BD) 
    // caso o projeto no futuro fosse expandido
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    // o set attribute diz ao php para mostrar erros se alguma query falhar e assim em vez de não dizer nada, podemos ver o erro
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // neste catch damos print do erro que houve quando tentámos ligar à bd
    die("Erro na base de dados: " . $e->getMessage());
}
?>