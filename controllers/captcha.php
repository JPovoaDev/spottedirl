<?php
session_start();

// Gerar uma string aleatória de 5 caracteres
$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
$captcha_string = '';
for ($i = 0; $i < 5; $i++) {
    $captcha_string .= $chars[random_int(0, strlen($chars) - 1)];
}

// Guardar a string na sessão
$_SESSION['captcha_code'] = $captcha_string;

// Criar a imagem base
$width = 120;
$height = 40;
$image = imagecreatetruecolor($width, $height);

// Definir cores
$bg_color = imagecolorallocate($image, 245, 245, 245);
$text_color = imagecolorallocate($image, 30, 60, 120);
$line_color = imagecolorallocate($image, 200, 200, 200);

// Preencher fundo
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Adicionar algumas linhas para dificultar a leitura a bots (simples)
for ($i = 0; $i < 4; $i++) {
    imageline($image, 0, random_int(0, $height), $width, random_int(0, $height), $line_color);
}

// Adicionar a string gerada à imagem
// Usa a fonte default do sistema (tamanho 5)
imagestring($image, 5, 35, 12, $captcha_string, $text_color);

// Definir o cabeçalho para PNG e desenhar a imagem
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
