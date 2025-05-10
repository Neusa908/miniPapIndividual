<?php
require 'conexao.php'; // Inclui a conexão

session_start(); // Inicia a sessão

// Limpa todas as variáveis de sessão
$_SESSION = array();

// Se você estiver usando cookies de sessão, é importante também destruí-los
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, 
        $params["path"], 
        $params["domain"], 
        $params["secure"], 
        $params["httponly"]
    );
}

// Finalmente, destrói a sessão
session_destroy();

// Redireciona para a página inicial
header("Location: index.php"); // Redireciona para index
exit();
?>