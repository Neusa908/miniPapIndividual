<?php
date_default_timezone_set('Europe/Lisbon');
    
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "mercado_bom_preco";

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}
?>