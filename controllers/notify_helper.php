<?php
// helper de notificações — não é um controlador, é incluído por outros ficheiros
// usa mail() nativo do PHP — no ambiente Docker o sendmail está configurado como relay
// para o Gmail via /etc/mail/sendmail.mc, portanto mail() funciona sem configuração extra

function get_smtp_config(PDO $pdo): array {
    $stmt = $pdo->query("SELECT config_key, config_value FROM system_config");
    $cfg = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cfg[$row['config_key']] = $row['config_value'];
    }
    return $cfg;
}

function send_email(PDO $pdo, string $to, string $subject, string $body): bool {
    $cfg       = get_smtp_config($pdo);
    $from      = $cfg['smtp_from']      ?? '';
    $from_name = $cfg['smtp_from_name'] ?? 'SpottedIRL';

    if (!$from || !$to) return false;

    // No Linux com sendmail configurado, mail() usa o agente do sistema directamente
    // Não é necessário ini_set de SMTP/smtp_port — esses settings são apenas para Windows
    $headers  = "From: $from_name <$from>\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    return mail($to, $subject, $body, $headers);
}

// chamada após cada upload — notifica seguidores do uploader e subscritores das categorias do spot
function notify_new_spot(PDO $pdo, int $spot_id, int $uploader_id): void {
    $q = $pdo->prepare(
        "SELECT s.description, u.username FROM spots s
         JOIN users u ON u.id = s.user_id WHERE s.id = ?"
    );
    $q->execute([$spot_id]);
    $spot = $q->fetch(PDO::FETCH_ASSOC);
    if (!$spot) return;

    $subject = "Novo registo de {$spot['username']} no SpottedIRL";
    $body    = "
        <p>O utilizador <strong>{$spot['username']}</strong> publicou um novo registo:</p>
        <blockquote>{$spot['description']}</blockquote>
        <p>Visita o SpottedIRL para ver mais.</p>
    ";

    $notified = [];

    // seguidores do uploader
    $followers = $pdo->prepare(
        "SELECT u.email FROM follows f
         JOIN users u ON u.id = f.follower_id
         WHERE f.followed_id = ? AND u.is_active = 1"
    );
    $followers->execute([$uploader_id]);
    foreach ($followers->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!in_array($row['email'], $notified)) {
            send_email($pdo, $row['email'], $subject, $body);
            $notified[] = $row['email'];
        }
    }

    // subscritores das categorias do spot
    $subs = $pdo->prepare(
        "SELECT DISTINCT u.email FROM subscriptions sub
         JOIN spot_categories sc ON sc.category_id = sub.category_id
         JOIN users u ON u.id = sub.user_id
         WHERE sc.spot_id = ? AND u.is_active = 1"
    );
    $subs->execute([$spot_id]);
    foreach ($subs->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!in_array($row['email'], $notified)) {
            send_email($pdo, $row['email'], $subject, $body);
            $notified[] = $row['email'];
        }
    }
}