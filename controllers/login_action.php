<?php
// este ficheiro é o controlador do login, recebe os dados do formulário do login.php e tenta autenticar o utilizador contra a base de dados
session_start();
require_once '../db.php';

// garantimos que só aceitamos pedidos POST, se alguém tentar aceder diretamente pelo URL mandamos logo para o login sem fazer nada
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/login.php');
    exit;
}

// lemos os campos do formulário com o operador ?? que devolve string vazia se o campo não existir
// o trim no username remove espaços no início e no fim que o utilizador possa ter metido sem querer
// na password não usamos trim porque um espaço pode ser parte da password intencional
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// validação básica antes de ir à base de dados, se algum campo estiver vazio não vale a pena fazer a query
if (!$username || !$password) {
    $_SESSION['error'] = 'Preenche todos os campos.';
    header('Location: ../views/login.php');
    exit;
}

// procuramos o utilizador pelo username com um prepared statement, nunca devemos concatenar dados do utilizador diretamente numa query
// porque isso abre a porta ao SQL injection, por exemplo se alguém pusesse "' OR '1'='1" no campo de username poderia contornar a autenticação por completo
$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// o password_verify compara a password que o utilizador escreveu com o hash guardado na base de dados
// nunca guardamos passwords em texto simples, guardamos o hash gerado pelo password_hash no registo
// o PHP trata da comparação de forma segura porque o hash tem um salt único incorporado
// por isso não é uma comparação simples de strings, o password_verify sabe como lidar com isso
if (!$user || !password_verify($password, $user['password_hash'])) {
    // damos sempre a mesma mensagem de erro quer o username não exista quer a password esteja errada
    // se disséssemos qual dos dois está errado estaríamos a dar informação útil a quem está a tentar entrar sem permissão
    $_SESSION['error'] = 'Credenciais inválidas.';
    header('Location: ../views/login.php');
    exit;
}

// se a conta estiver suspensa pelo admin não deixamos entrar e damos uma mensagem adequada
// a verificação é feita depois da password para não revelarmos se a conta existe antes de validar as credenciais
if (!$user['is_active']) {
    $_SESSION['error'] = 'A tua conta está suspensa. Contacta o administrador.';
    header('Location: ../views/login.php');
    exit;
}

// se a conta não estiver verificada, não deixamos entrar e mandamos para a página de verificação
if (!$user['is_verified']) {
    $_SESSION['pending_user_id'] = $user['id'];
    $_SESSION['error'] = 'Conta ainda não verificada. Insere o código enviado para o teu email.';
    header('Location: ../views/verify.php');
    exit;
}

// se chegámos até aqui a autenticação foi bem sucedida e guardamos os dados essenciais do utilizador na sessão
// são estes valores que o resto da aplicação usa para saber quem está logged in e que permissões tem
// o auth.php lê o role da sessão para decidir se o utilizador pode aceder a cada página
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

// se havia um URL guardado antes do redirect para o login, vamos para lá
if (!empty($_SESSION['locationAfterAuth'])) {
    $dest = $_SESSION['locationAfterAuth'];
    unset($_SESSION['locationAfterAuth']);
    header("Location: $dest");
    exit;
}

// redirecionamos para o dashboard certo consoante o perfil do utilizador, o admin vai para o painel de administração, o simpatizante para o seu painel próprio
// e qualquer outro perfil como o utilizador comum vai para a página principal
switch ($user['role']) {
    case 'admin':
        header('Location: ../views/admin/dashboard.php');
        break;
    case 'simpatizante':
        header('Location: ../views/simpatizante/dashboard.php');
        break;
    case 'user':
        header('Location: ../views/user/dashboard.php');
        break;
    default:
        header('Location: ../index.php');
}
exit;