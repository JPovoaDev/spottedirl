<?php
// helper de notificações, este ficheiro não é um controlador, apenas é incluído por outros ficheiros
// usa o mail() nativo do PHP no ambiente Docker, o sendmail está configurado como relay
// para o Gmail via /etc/mail/sendmail.mc, portanto mail() funciona sem configuração extra

// esta função vai à base de dados apanhar as configurações de envio de emails
// sendo essas o nome do site e o endereço de email de envio (que o Administrador guardou no sistema)
function get_smtp_config(PDO $pdo): array {
    $stmt = $pdo->query("SELECT config_key, config_value FROM system_config");
    $cfg = [];
    // o fetchAll traz todas as linhas de resposta da tabela de uma vez
    // o foreach passa em cada uma dessas configurações e organiza num dicionário ($cfg)
    // fácil para usar depois (ex: $cfg['smtp_from'] devolve o valor de suporte@spottedirl.com)
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cfg[$row['config_key']] = $row['config_value'];
    }
    return $cfg;
}

function send_email(PDO $pdo, string $to, string $subject, string $body): bool {
    error_log("send_email chamado para: $to");
    
    // obtemos o endereço oficial do remetente
    $cfg = get_smtp_config($pdo);
    $from = $cfg['smtp_from'] ?? '';
    // se por acaso falhar a leitura na bd não deixamos isto em branco e forçamos o default 'SpottedIRL'
    $from_name = $cfg['smtp_from_name'] ?? 'SpottedIRL';

    error_log("smtp_from: $from");

    // se o administrador não meteu e-mail de envio no painel de administração então aborta a missão para não quebrar a página a meio
    if (!$from || !$to) {
        error_log("send_email abortou: from ou to vazios");
        return false;
    }

    // cabeçalhos do e-mail, são regras invisíveis de leitura nos clientes de webmail como gmail ou outlook
    $headers  = "From: $from_name <$from>\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    // o Content-Type text/html permite escrever usando as tags <br>, <h1>, etc no texto
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // o comando "mail()" é nativo do PHP, funciona aqui porque 
    // recorre à configuração SendMail interna pré-programada através do Docker
    return mail($to, $subject, $body, $headers);
}

// esta função organiza o envio de notificações após a criação de um novo registo (spot)
// notifica tanto os seguidores diretos do autor como os subscritores das categorias abordadas
function notify_new_spot(PDO $pdo, int $spot_id, int $uploader_id): void {
    // apanhamos a descrição do spot e o username do autor
    $q = $pdo->prepare(
        "SELECT s.description, u.username FROM spots s
         JOIN users u ON u.id = s.user_id WHERE s.id = ?"
    );
    $q->execute([$spot_id]);
    $spot = $q->fetch(PDO::FETCH_ASSOC);
    if (!$spot) return;

    // escrevemos uma mensagem com as informações que recolhemos
    $subject = "Novo registo de {$spot['username']} no SpottedIRL";
    $body    = "
        <p>O utilizador <strong>{$spot['username']}</strong> publicou um novo registo:</p>
        <blockquote>{$spot['description']}</blockquote>
        <p>Visita o SpottedIRL para ver mais.</p>
    ";

    // o array $notified vai ser usado para evitar o envio de e-mails duplicados ao mesmo destinatário
    $notified = [];
    error_log("notify_new_spot chamado: spot_id=$spot_id uploader_id=$uploader_id");

    try {
        // vemos que utilizadores ativos seguem este autor
        $followers = $pdo->prepare(
            "SELECT u.email FROM follows f
            JOIN users u ON u.id = f.follower_id
            WHERE f.followed_id = ? AND u.is_active = 1"
        );
        $followers->execute([$uploader_id]);
        $rows = $followers->fetchAll(PDO::FETCH_ASSOC);
        error_log("Seguidores encontrados: ".count($rows));
        foreach ($rows as $row) {
            error_log("A notificar: ".$row['email']);
            send_email($pdo, $row['email'], $subject, $body); // mandamos o email para todos eles
            $notified[] = $row['email']; // e registamos o contacto na lista de notificados
        }
    } catch (Exception $e) {
        error_log("ERRO seguidores: " . $e->getMessage());
    }

    // por fim identificamos os utilizadores ativos que acompanham as categorias deste spot
    $subs = $pdo->prepare(
        "SELECT DISTINCT u.email FROM subscriptions sub
         JOIN spot_categories sc ON sc.category_id = sub.category_id
         JOIN users u ON u.id = sub.user_id
         WHERE sc.spot_id = ? AND u.is_active = 1"
    );
    $subs->execute([$spot_id]);
    foreach ($subs->fetchAll(PDO::FETCH_ASSOC) as $row) {
        // apenas enviamos a notificação se o e-mail não estiver na lista de já notificados
        // para evitar uma duplicação caso um utilizador siga o autor e simultaneamente a categoria em questão
        if (!in_array($row['email'], $notified)) {
            send_email($pdo, $row['email'], $subject, $body);
            $notified[] = $row['email'];
        }
    }
}