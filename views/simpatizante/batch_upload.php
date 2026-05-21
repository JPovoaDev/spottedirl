<?php
session_start();
require_once '../../auth.php';
require_once '../../db.php';
require_role('simpatizante');

$error   = $_SESSION['error']         ?? null; unset($_SESSION['error']);
$success = $_SESSION['batch_success'] ?? null; unset($_SESSION['batch_success']);
$errors  = $_SESSION['batch_errors']  ?? [];   unset($_SESSION['batch_errors']);
?>
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><title>Upload em Lote – SpottedIRL</title></head>
<body>
<h1>Upload em Lote (ZIP + XML)</h1>

<?php if ($error): ?><p style="color:red"><?= htmlspecialchars($error) ?></p><?php endif; ?>

<?php if ($success !== null): ?>
    <p style="color:green"><?= (int)$success ?> registo(s) importado(s) com sucesso.</p>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <h3 style="color:red">Erros encontrados:</h3>
    <ul>
        <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>Como preparar o ZIP</h2>
<p>O ZIP deve conter os ficheiros de media (jpg, png, mp4, mp3…) e um ficheiro <code>spots.xml</code> com a seguinte estrutura:</p>
<pre style="background:#f4f4f4;padding:10px">
&lt;spots&gt;
  &lt;spot&gt;
    &lt;filename&gt;foto.jpg&lt;/filename&gt;
    &lt;description&gt;Descrição do spot&lt;/description&gt;
    &lt;visibility&gt;publico&lt;/visibility&gt;
    &lt;localizacao&gt;Lisboa&lt;/localizacao&gt;
    &lt;hora_do_dia&gt;manha&lt;/hora_do_dia&gt;
    &lt;raridade&gt;raro&lt;/raridade&gt;
    &lt;categoria_principal&gt;1&lt;/categoria_principal&gt;
    &lt;categoria_secundaria&gt;2&lt;/categoria_secundaria&gt;
  &lt;/spot&gt;
&lt;/spots&gt;
</pre>

<form method="POST" action="../../controllers/batch_upload_action.php" enctype="multipart/form-data">
    <label>Ficheiro ZIP:<br>
        <input type="file" name="zip_file" accept=".zip" required>
    </label><br><br>
    <button type="submit">Importar</button>
</form>

<a href="dashboard.php">&#8592; Voltar ao painel</a>
</body>
</html>