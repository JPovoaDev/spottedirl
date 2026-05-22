<?php

// começamos por verificar se já temos a sessão inicializada senão o PHP pode dar erro
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// pra diferenciarmos os tipos de utilizadores que temos decidimos fazer uma espécie de dicionário com pesos, ou seja,
// o utilizador comum não tem permissões para alem do básico, então só pode aceder ao seu nivel 0, mas o utilizador admin
// já tem acesso a tudo o que seja possível, então é o nível maior que houver e portanto tem acesso a todos os níveis inferiores
// como uma pirâmide de pesos, quanto maior, mais permissões
const ROLE_WEIGHT = [
    'guest'        => 0,
    'user'         => 1,
    'simpatizante' => 2,
    'admin'        => 3,
];

// isto devolve o perfil do utilizador atual, por default o utilizador é um guest, e então atribuímos isso caso não tenha role
// porque basicamente se não estiver logged in é tratado como guest
function current_role(): string {
    return $_SESSION['role'] ?? 'guest';
}

// aqui vemos que o utilizador pode aceder a uma determinada página
// digamos que queremos aceder a uma página privada de um simpatizante, a min role será 2, então se a role do utilizador atual
// for igual ou maior, é porque terá acesso à página
function has_role(string $min_role): bool {
    $current = ROLE_WEIGHT[current_role()] ?? 0; // caso o utilizador não tenha uma role é tratado como convidado
    $needed = ROLE_WEIGHT[$min_role];
    return $current >= $needed;
}



// esta é a função principal que usamos nas páginas que são protegidas e tem duas verificações:
function require_role(string $min_role): void {
    // primeiro verifica se o utilizador está autenticado (tem pelo menos perfil user)
    // se não estiver, guarda o URL atual para redirecionar após o login e manda-o para o login
    if (!has_role('user')) {
        $_SESSION['error'] = 'Tens de fazer login para aceder a esta página.';
        // guardamos o URL atual para poder redirecionar o utilizador de volta após o login
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $_SESSION['locationAfterAuth'] = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('Location: /views/login.php');
        exit; // o exit para a execução do PHP mesmo após o redirect
    }

    // a segunda verificação é se ele estiver autenticado mas não tem o perfil suficiente
    // para aquela página específica então devolve um erro 403 (acesso negado)
    if (!has_role($min_role)) {
        http_response_code(403);
        echo '<h1>403 – Acesso negado</h1>';
        echo '<p>Não tens permissão para aceder a esta página.</p>';
        echo '<a href="/views/index.php">Voltar ao início</a>';
        exit; // o exit para a execução do PHP mesmo após o redirect
    }
}

function safe_redirect(string $fallback): void {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $safe_ref = $fallback;
    if ($ref && filter_var($ref, FILTER_VALIDATE_URL)) {
        $ref_host = parse_url($ref, PHP_URL_HOST);
        $own_host = $_SERVER['HTTP_HOST'] ?? '';
        if ($ref_host === $own_host) {
            $safe_ref = $ref;
        }
    }
    header("Location: $safe_ref");
    exit;
}
