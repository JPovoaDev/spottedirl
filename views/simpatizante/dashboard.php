<?php
// tal como no dashboard do admin começamos por verificar as permissões antes de mostrar qualquer coisa
require_once '../../auth.php';

// neste caso o nível mínimo exigido é simpatizante, o perfil logo acima do utilizador comum
// um utilizador com perfil user que tentasse aceder a esta página receberia um 403
// um admin também consegue aceder porque tem peso maior na hierarquia definida no auth.php
require_role('simpatizante');
?>
<!DOCTYPE html>
<html lang="pt"><head><meta charset="UTF-8"><title>Painel Simpatizante</title></head>
<body>
<!-- o login_action.php redireciona automaticamente para aqui quando o perfil é simpatizante -->
<h1>Painel Simpatizante</h1>

<!-- é aqui que ele cria as categorias secundárias dentro das categorias principais do admin -->
<a href="subcategories.php">As minhas subcategorias</a> |
<a href="../../controllers/logout.php">Logout</a>
</body></html>