<?php
// este ficheiro processa o formulário de registo que vem do register.php
// valida os dados recebidos, verifica se o utilizador já existe e cria a conta na base de dados
// tal como os outros controladores não tem HTML nenhum, só lógica e redirecionamentos
session_start();
require_once '../db.php';

// se alguém tentar aceder diretamente pelo URL redirecionamos para o registo sem fazer nada
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/register.php');
    exit;
}

// lemos os campos do formulário e limpamos espaços desnecessários com o trim no username e email
// na password não usamos trim porque um espaço no início ou no fim pode ser intencional
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$captcha = $_POST['captcha'] ?? '';

// validamos que nenhum campo chegou vazio antes de fazer qualquer operação na base de dados
// não faz sentido ir à BD verificar duplicados se os campos estiverem vazios
if (!$username || !$email || !$password || !$captcha) {
    $_SESSION['error'] = 'Preenche todos os campos.';
    header('Location: ../views/register.php');
    exit;
}

// verificar CAPTCHA
if (!isset($_SESSION['captcha_code']) || $_SESSION['captcha_code'] !== $captcha) {
    $_SESSION['error'] = 'CAPTCHA incorreto. Tenta novamente.';
    header('Location: ../views/register.php');
    exit;
}

// o filter_var com FILTER_VALIDATE_EMAIL usa a lógica interna do PHP para verificar o formato do email
// verifica que tem um @ no sítio certo e um domínio com a estrutura esperada, mas
// não verifica se o email existe nesno, só se o formato está correto
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Email inválido.';
    header('Location: ../views/register.php');
    exit;
}

// impomos um mínimo de 6 caracteres para a password
if (strlen($password) < 6) {
    $_SESSION['error'] = 'A password deve ter pelo menos 6 caracteres.';
    header('Location: ../views/register.php');
    exit;
}

// verificamos se o username ou o email já estão em uso numa só query com OR, se qualquer um dos dois já existir na base de dados não deixamos criar a conta
// assim evitamos ter dois utilizadores com o mesmo username ou o mesmo email registado
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    $_SESSION['error'] = 'Username ou email já em uso.';
    header('Location: ../views/register.php');
    exit;
}

// o password_hash gera um hash seguro da password usando bcrypt por defeito com PASSWORD_DEFAULT
// o bcrypt é lento por design o que dificulta muito ataques de força bruta caso a base de dados seja comprometida
// e também gera automaticamente um salt único para cada hash, o que significa que dois utilizadores com a mesma password têm hashes completamente diferentes
$hash = password_hash($password, PASSWORD_DEFAULT);

// criamos o utilizador com o perfil user que é o mais básico do sistema
// só o administrador pode promover um utilizador para simpatizante ou admin depois do registo
// o NOW() pede à base de dados a data e hora atual para o campo created_at
$stmt = $pdo->prepare(
    'INSERT INTO users (username, email, password_hash, role, created_at)
     VALUES (?, ?, ?, \'user\', NOW())'
);
$stmt->execute([$username, $email, $hash]);
$user_id = $pdo->lastInsertId();

// criamos o código de confirmação de 6 dígitos como temos no resend verification
// geramos um número entre 0 e 999999 e se for gerado "42" o str_pad acrescenta zeros à esquerda até ficarem 6 dígitos
$token = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
// damos uma margem de 30 minutos para o user confirmar a conta e usar o token
$expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

// guardamos este novo código e a respetiva data de expiração na base de dados associados ao utilizador
$pdo->prepare(
    "INSERT INTO email_verifications (user_id, token, expires_at)
     VALUES (?, ?, ?)"
)->execute([$user_id, $token, $expires]);

// e enviamos o email com o código, chamando a nossa função já feita
require_once 'notify_helper.php';
$subject = "Confirma a tua conta - SpottedIRL";
$body = "
    <h2>Bem-vindo ao SpottedIRL!</h2>
    <p>O teu código de confirmação é:</p>
    <h1 style='letter-spacing: 8px;'>{$token}</h1>
    <p>Este código expira em 30 minutos.</p>
";
send_email($pdo, $email, $subject, $body);

// guardamos temporariamente o id do utilizador na sessão para saber quem está a tentar verificar a conta
// neste momento o utilizador ainda não tem login iniciado (não tem $_SESSION['user_id'] verdadeiro),
// este "pending_user_id" vai ser usado pelo verify_action.php na página seguinte
// para saber de quem é o código de ativação que for submetido
$_SESSION['pending_user_id'] = $user_id;
$_SESSION['success'] = 'Conta criada com sucesso. Verifica o teu email para obter o código de ativação!';
header('Location: ../views/verify.php');
exit;