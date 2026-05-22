<?php
// este controlar permite que um simpatizante dê upload de múltiplos ficheiro ao mesmo tempo
// usamos um ficheiro ZIP que contém as imagens e um ficheiro XML obrigatório
// que atua como documento mestre que contém os metadados (descrição, categorias, etc.) de cada ficheiro

session_start();
require_once '../db.php';
require_once '../auth.php';
require_role('simpatizante');

// o PHP nativo apenas permite apagar diretorias se estas estiverem vazias e dado que a extração de um pacote
// ZIP pode criar um conjunto de subpastas e ficheiros, esta função invoca-se sucessivamente sempre 
// que encontra noutra pasta ("is_dir($f) ? rrmdir($f)")
// assim vai destruindo os ficheiros individuais com 'unlink()' primeiro, seguindo uma lógica de "dentro para fora", 
// até finalmente conseguir e limpar tudo
function rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (glob($dir . '/*') as $f) {
        is_dir($f) ? rrmdir($f) : unlink($f);
    }
    rmdir($dir);
}

// apenas pedidos originários do formulário de submissão (método POST) são autorizados
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/simpatizante/batch_upload.php');
    exit;
}

// asseguramos que o pacote ZIP foi efetivamente submetido e não tem de erros de transferência
if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = 'Ficheiro ZIP obrigatório.';
    header('Location: ../views/simpatizante/batch_upload.php');
    exit;
}

// a confirmação de que o ficheiro é realmente um arquivo ZIP é feita através da averiguação do seu "MIME Type"
// independentemente da extensão declarada pelo cliente
$mime = mime_content_type($_FILES['zip_file']['tmp_name']);
if (!in_array($mime, ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])) {
    $_SESSION['error'] = 'O ficheiro tem de ser um ZIP.';
    header('Location: ../views/simpatizante/batch_upload.php');
    exit;
}

$tmpDir = sys_get_temp_dir() . '/batch_' . uniqid();
$originalTmpDir = $tmpDir;
mkdir($tmpDir);

$zip = new ZipArchive();
if ($zip->open($_FILES['zip_file']['tmp_name']) !== true) {
    $_SESSION['error'] = 'Não foi possível abrir o ZIP.';
    rmdir($tmpDir);
    header('Location: ../views/simpatizante/batch_upload.php');
    exit;
}
$zip->extractTo($tmpDir);
$zip->close();

// tentamos primeiro encontrar o ficheiro XML logo na pasta de raiz que se formou durante a extração
$xmlFiles = glob($tmpDir . '/*.xml');
if (empty($xmlFiles)) {
    // caso falhe, pensamos na possibilidade de o utilizador ter ZIPado a pasta como um todo
    // e efetua uma pesquisa descendo um nível hierárquico
    $xmlFiles = glob($tmpDir . '/*/*.xml');
    if (!empty($xmlFiles)) {
        // redefine a via ativa de trabalho para essa nova subpasta
        $tmpDir = dirname($xmlFiles[0]);
    }
}
// sem plano de metadados, todo o pacote perde o seu significado e abortamos tudo
if (empty($xmlFiles)) {
    $_SESSION['error'] = 'Nenhum ficheiro XML encontrado dentro do ZIP.';
    rrmdir($originalTmpDir);
    header('Location: ../views/simpatizante/batch_upload.php');
    exit;
}

// convertemos as strings XML para objetos legíveis por PHP
$xml = simplexml_load_file($xmlFiles[0]);
if (!$xml) {
    $_SESSION['error'] = 'Ficheiro XML inválido ou mal formado.';
    rrmdir($originalTmpDir);
    header('Location: ../views/simpatizante/batch_upload.php');
    exit;
}

// critérios para validação contra ficheiros maliciosos e garantia do Enum do Model em MySQL
$allowed_mime = ['image/jpeg','image/png','image/gif','video/mp4','video/quicktime','audio/mpeg','audio/wav','audio/ogg'];
$raridades_ok = ['comum','incomum','raro','excecional'];
$horas_ok = ['manha','tarde','noite'];

$successCount = 0;
$errors = [];

// percorremos toda e cada declaração individual inserida no XML
foreach ($xml->spot as $index => $spotNode) {
    $num = $index + 1;

    $filename_orig = trim((string)$spotNode->filename);
    $description = trim((string)$spotNode->description);
    $visibility = trim((string)$spotNode->visibility) ?: 'publico';
    $localizacao = trim((string)$spotNode->localizacao);
    $hora_do_dia = trim((string)$spotNode->hora_do_dia);
    $raridade = trim((string)$spotNode->raridade) ?: 'comum';
    $cat_principal = (int)$spotNode->categoria_principal;
    $cat_secundaria = (int)$spotNode->categoria_secundaria;

    // apenas passamos o spot à próxima etapa se os dois requistos base cumprirem a exigência de preenchimento
    if (!$filename_orig || !$description) {
        $errors[] = "Entrada $num: filename e description são obrigatórios.";
        continue; // termina esta iterração, mas prossegue a avaliação no nó seguinte do ciclo
    }

    // cruza a diretriz do XML à busca do ficheiro físico correspondente guardado na dita pasta provisória
    $filePath = $tmpDir . '/' . $filename_orig;
    if (!file_exists($filePath)) {
        $errors[] = "Entrada $num: ficheiro '$filename_orig' não encontrado no ZIP.";
        continue;
    }

    // reconfirma de perto o formato do ficheiro em questão
    $fileMime = mime_content_type($filePath);
    if (!in_array($fileMime, $allowed_mime)) {
        $errors[] = "Entrada $num: tipo '$fileMime' não permitido.";
        continue;
    }

    if (str_starts_with($fileMime, 'image')) $tipo = 'foto';
    elseif (str_starts_with($fileMime, 'video')) $tipo = 'video';
    else $tipo = 'audio';

    // se o XML tiver discrepâncias ou parâmetros alheios nas restrições de formatação optamos 
    // sistematicamente pelos valores de salvaguarda por omissão (Default Behavior)
    if (!in_array($visibility, ['publico', 'privado'])) $visibility  = 'publico';
    if (!in_array($raridade, $raridades_ok)) $raridade = 'comum';
    if (!in_array($hora_do_dia, $horas_ok)) $hora_do_dia = '';

    if ($cat_principal) {
        $chk = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND type = 'principal'");
        $chk->execute([$cat_principal]);
        if (!$chk->fetch()) {
            $errors[] = "Entrada $num: categoria principal $cat_principal não existe.";
            continue;
        }
    }

    // tal como nos uploads normais a criação de um ID Único visa escapar sistematicamente a ambiguidades
    $ext         = pathinfo($filename_orig, PATHINFO_EXTENSION);
    $newFilename = uniqid() . '.' . $ext;
    $dest        = __DIR__ . '/../uploads/' . $newFilename;

    if (!copy($filePath, $dest)) {
        $errors[] = "Entrada $num: erro ao guardar '$filename_orig'.";
        continue;
    }

    // encadear operações críticas dentro de Try para impedir ruturas em cadeia do programa na eventualidade 
    // de erros localizados na DB
    try {
        // Gravação Mestre - Introduz o esqueleto da postagem.
        $stmt = $pdo->prepare(
            "INSERT INTO spots (user_id, type, filename, description, visibility)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$_SESSION['user_id'], $tipo, $newFilename, $description, $visibility]);
        // obtém o "ID do novo record criado" porque é obrigatório o seu vínculo sequencial na categorização
        $spot_id = $pdo->lastInsertId();

        if ($cat_principal) {
            $pdo->prepare("INSERT INTO spot_categories (spot_id, category_id) VALUES (?, ?)")
                ->execute([$spot_id, $cat_principal]);
        }
        if ($cat_secundaria) {
            $chk2 = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND type = 'secundaria'");
            $chk2->execute([$cat_secundaria]);
            if ($chk2->fetch()) {
                $pdo->prepare("INSERT INTO spot_categories (spot_id, category_id) VALUES (?, ?)")
                    ->execute([$spot_id, $cat_secundaria]);
            }
        }

        if ($localizacao) {
            $pdo->prepare("INSERT INTO spot_meta (spot_id, meta_key, meta_value) VALUES (?, 'localizacao', ?)")
                ->execute([$spot_id, $localizacao]);
        }
        if ($hora_do_dia) {
            $pdo->prepare("INSERT INTO spot_meta (spot_id, meta_key, meta_value) VALUES (?, 'hora_do_dia', ?)")
                ->execute([$spot_id, $hora_do_dia]);
        }
        $pdo->prepare("INSERT INTO spot_meta (spot_id, meta_key, meta_value) VALUES (?, 'raridade', ?)")
            ->execute([$spot_id, $raridade]);

        $successCount++; // postagem concluída; conta-se na folha de reporte a emitir

    } catch (PDOException $e) {
        // na ocorrência de anomalia gravíssima que proibia a fixação da entrada de dados as instâncias físicas são demolidas
        if (file_exists($dest)) unlink($dest);
        $errors[] = "Entrada $num: erro na base de dados — " . $e->getMessage();
    }
}

// a rotina da função alojada no cimo do presente ficheiro remove o peso volátil ao fim do script
rrmdir($originalTmpDir);

// retoma o envio dos dados globais operados por este código em particular e despacha 
// em regresso com as notificações de encerramento prontas a serem despoletadas na UI
$_SESSION['batch_success'] = $successCount;
$_SESSION['batch_errors']  = $errors;
header('Location: ../views/simpatizante/batch_upload.php');
exit;