<?php
// o logout tem de destruir completamente a sessão do utilizador para que não fique nada guardado
// iniciamos a sessão para conseguir aceder e destruir os seus dados, sem o session_start não conseguimos chamar as funções que a limpam
session_start();

// o session_unset limpa todas as variáveis da sessão, como o user_id, o username e o role
session_unset();

// o session_destroy apaga a sessão do servidor completamente, o session_unset sozinho não chega porque a sessão ainda existia no servidor, mas vazia
// o destroy é quem a elimina por completo
session_destroy();

// depois de destruir a sessão mandamos o utilizador para o login, não faz sentido ir para outra página porque já não está autenticado
// e qualquer página protegida redirecionaria para aqui outra vez
header('Location: ../index.php');
exit;