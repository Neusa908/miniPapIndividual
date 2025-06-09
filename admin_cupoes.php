<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php'; // Inclui a conexão com o banco de dados

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cupões - Mercado Bom Preço</title>
</head>

<body>

</body>

</html>