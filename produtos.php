<?php
require 'conexao.php';
$sql = "SELECT nome_produto, preco FROM produtos";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos</title>
    <link rel="stylesheet" href="css/produtos.css">
</head>

<body>
    <h1>Lista de Produtos</h1>
    <ul>
        <?php while ($row = $result->fetch_assoc()) { ?>
        <li><?php echo $row['nome_produto'] . " - " . $row['preco'] . "€"; ?></li>
        <?php } ?>
    </ul>
</body>

</html>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>

<body>


    <h1>Produtos</h1>
    <p>Venha conferir a nossa lista de produtos disponíveis.</p>

    <footer>
        <p>&copy; 2024-2025 Mercado Bom Preço, onde o preço é bom!</p>
    </footer>
</body>

</html>