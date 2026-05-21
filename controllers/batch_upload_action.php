<?php
// controlador de upload em lote — recebe um ZIP com ficheiros de media + um XML com os metadados de cada spot
// processa entrada a entrada, regista os erros individualmente e continua para as seguintes sem abortar tudo
session_start();
require_once '../db.php';
require_once '../auth.php';
require_role('simpatizante');

function rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (glob($dir . '/*') as $f) {
        is_dir($f) ? rrmdir($f) : unlink($f);
    }
    rmdir($dir);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/simpatizante/batch_upload.php');
    exit;
}

if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = 'Ficheiro ZIP obrigatório.';
    header('Location: ../views/simpatizante/batch_upload.php');
    exit;
}

// verificamos que é mesmo um ZIP e não outro tipo de ficheiro com extensão trocada
$mime = mime_content_type($_FILES['zip_file']['tmp_name']);
if (!in_array($mime, ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])) {
    $_SESSION['error'] = 'O ficheiro tem de ser um ZIP.';
    header('Location: ../views/simpatizante/batch_upload.php');
    exit;
}

// criamos uma pasta temporária única para extrair o conteúdo do ZIP
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

// procuramos o primeiro XML na raiz do ZIP
$xmlFiles = glob($tmpDir . '/*.xml');
if (empty($xmlFiles)) {
    // procura também dentro de uma subpasta se o utilizador comprimiu a pasta inteira
    $xmlFiles = glob($tmpDir . '/*/*.xml');
    if (!empty($xmlFiles)) {
        // redefinimos o tmpDir para a subpasta para que os ficheiros de media também sejam encontrados
        $tmpDir = dirname($xmlFiles[0]);
    }
}
if (empty($xmlFiles)) {
    $_SESSION['error'] = 'Nenhum ficheiro XML encontrado dentro do ZIP.';
    rrmdir($originalTmpDir);
    header('Location: ../views/simpatizante/batch_upload.php');
    exit;
}

$xml = simplexml_load_file($xmlFiles[0]);
if (!$xml) {
    $_SESSION['error'] = 'Ficheiro XML inválido ou mal formado.';
    rrmdir($originalTmpDir);
    header('Location: ../views/simpatizante/batch_upload.php');
    exit;
}

$allowed_mime   = ['image/jpeg','image/png','image/gif','video/mp4','video/quicktime','audio/mpeg','audio/wav','audio/ogg'];
$raridades_ok   = ['comum','incomum','raro','excecional'];
$horas_ok       = ['manha','tarde','noite'];

$successCount = 0;
$errors       = [];

foreach ($xml->spot as $index => $spotNode) {
    $num = $index + 1;

    $filename_orig  = trim((string)$spotNode->filename);
    $description    = trim((string)$spotNode->description);
    $visibility     = trim((string)$spotNode->visibility)  ?: 'publico';
    $localizacao    = trim((string)$spotNode->localizacao);
    $hora_do_dia    = trim((string)$spotNode->hora_do_dia);
    $raridade       = trim((string)$spotNode->raridade)    ?: 'comum';
    $cat_principal  = (int)$spotNode->categoria_principal;
    $cat_secundaria = (int)$spotNode->categoria_secundaria;

    // validação dos campos obrigatórios
    if (!$filename_orig || !$description) {
        $errors[] = "Entrada $num: filename e description são obrigatórios.";
        continue;
    }

    // verificamos que o ficheiro referenciado no XML existe dentro do ZIP extraído
    $filePath = $tmpDir . '/' . $filename_orig;
    if (!file_exists($filePath)) {
        $errors[] = "Entrada $num: ficheiro '$filename_orig' não encontrado no ZIP.";
        continue;
    }

    $fileMime = mime_content_type($filePath);
    if (!in_array($fileMime, $allowed_mime)) {
        $errors[] = "Entrada $num: tipo '$fileMime' não permitido.";
        continue;
    }

    // determinamos o tipo do spot com base no MIME real do ficheiro e não na extensão
    if (str_starts_with($fileMime, 'image'))      $tipo = 'foto';
    elseif (str_starts_with($fileMime, 'video'))  $tipo = 'video';
    else                                           $tipo = 'audio';

    // normalizamos e validamos os campos enum
    if (!in_array($visibility, ['publico', 'privado']))  $visibility  = 'publico';
    if (!in_array($raridade,   $raridades_ok))           $raridade    = 'comum';
    if (!in_array($hora_do_dia, $horas_ok))              $hora_do_dia = '';

    // validamos a categoria principal se vier preenchida
    if ($cat_principal) {
        $chk = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND type = 'principal'");
        $chk->execute([$cat_principal]);
        if (!$chk->fetch()) {
            $errors[] = "Entrada $num: categoria principal $cat_principal não existe.";
            continue;
        }
    }

    // movemos o ficheiro para a pasta de uploads com nome único para evitar colisões
    $ext         = pathinfo($filename_orig, PATHINFO_EXTENSION);
    $newFilename = uniqid() . '.' . $ext;
    $dest        = __DIR__ . '/../uploads/' . $newFilename;

    if (!copy($filePath, $dest)) {
        $errors[] = "Entrada $num: erro ao guardar '$filename_orig'.";
        continue;
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO spots (user_id, type, filename, description, visibility)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$_SESSION['user_id'], $tipo, $newFilename, $description, $visibility]);
        $spot_id = $pdo->lastInsertId();

        // categorias — só inserimos se existirem
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

        // metainfo
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

        $successCount++;

    } catch (PDOException $e) {
        // se a BD falhar nesta entrada apagamos o ficheiro que já foi copiado para não deixar ficheiros órfãos
        if (file_exists($dest)) unlink($dest);
        $errors[] = "Entrada $num: erro na base de dados — " . $e->getMessage();
    }
}

// limpamos a pasta temporária no fim — rrmdir() já foi declarada no topo do ficheiro
// o tmpDir pode ter sido redefinido para uma subpasta, por isso guardamos sempre o pai original
rrmdir($originalTmpDir);

// guardamos os resultados na sessão para a view os mostrar
$_SESSION['batch_success'] = $successCount;
$_SESSION['batch_errors']  = $errors;
header('Location: ../views/simpatizante/batch_upload.php');
exit;