<?php
// antes de mostrar qualquer coisa verificamos se o utilizador tem permissão para estar aqui
// o require_once carrega o auth.php que define a função require_role e a hierarquia de perfis
require_once '../../auth.php';

// o require_role verifica se o utilizador tem pelo menos o perfil admin
// se não houver sessão ativa nenhuma é redirecionado para o login com uma mensagem de erro
// se houver sessão mas o perfil não for suficiente recebe um erro 403 de acesso negado
require_role('admin');
?>
<!DOCTYPE html>
<html lang="pt"><head><meta charset="UTF-8"><title>Painel Admin</title></head>
<body>
<!-- o login_action.php redireciona automaticamente para aqui quando deteta que o perfil é admin -->
<!-- e é a partir daqui que o admin tem acesso às funcionalidades do seu perfil -->
<h1>Painel Admin</h1>

<!-- sem categorias principais o simpatizante não consegue criar subcategorias nem associar registos -->
<a href="categories.php">Gerir Categorias</a> |

<!-- a gestão de utilizadores permite promover perfis, suspender contas e apagar utilizadores -->
<a href="users.php">Gerir Utilizadores</a> |
<a href="email_config.php">Config. Email</a> |

<!-- o logout aponta para o controlador que destrói a sessão e redireciona para o login -->
<a href="../../controllers/logout.php">Logout</a>
</body></html>