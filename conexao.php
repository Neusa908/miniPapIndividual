<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style.css">

    <title>conexão</title>
</head>

<body class="conexao">
    <?php
$localhost = "localhost";  // O servidor do banco de dados
$username = "root";         // O nome de usuário do banco de dados
$password = "root";         // A senha do banco de dados
$dbname = "mercado_bom_preco";  // O nome do banco de dados

// Criar a conexão com o uso do MySQLi
$conecta = new mysqli($localhost, $username, $password, $dbname);


//verificar algum erro
$sql = mysqli_query($conecta, "SELECT * FROM usuarios" );

echo "Existem " .mysqli_num_rows($sql). " registos.";
?>