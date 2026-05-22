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
    error_log("send_email chamado para: $to");
    $cfg       = get_smtp_config($pdo);
    $from      = $cfg['smtp_from']      ?? '';
    $from_name = $cfg['smtp_from_name'] ?? 'SpottedIRL';

    error_log("smtp_from: $from");

    if (!$from || !$to) {
        error_log("send_email abortou: from ou to vazios");
        return false;
    }

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
    error_log("notify_new_spot chamado: spot_id=$spot_id uploader_id=$uploader_id");

    // ADICIONAR ISTO
    try {
        $followers = $pdo->prepare(
            "SELECT u.email FROM user_follows f
            JOIN users u ON u.id = f.user_id
            WHERE f.simpatizante_id = ? AND u.is_active = 1"
        );
        $followers->execute([$uploader_id]);
        $rows = $followers->fetchAll(PDO::FETCH_ASSOC);
        error_log("Seguidores encontrados: " . count($rows));
        foreach ($rows as $row) {
            error_log("A notificar: " . $row['email']);
            send_email($pdo, $row['email'], $subject, $body);
            $notified[] = $row['email'];
        }
    } catch (Exception $e) {
        error_log("ERRO seguidores: " . $e->getMessage());
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