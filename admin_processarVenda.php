<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'cliente') {
    echo "<script>alert('Acesso negado! Apenas clientes podem realizar compras.'); window.location.href='index.php';</script>";
    exit();
}

$cliente_id = $_SESSION['usuario_id'];

$valor = 50.00; 

if (isset($_POST['comprar'])) {
    $sql_venda = "INSERT INTO vendas (cliente_id, valor) VALUES (?, ?)";
    $stmt_venda = $conn->prepare($sql_venda);
    $stmt_venda->bind_param("id", $cliente_id, $valor);
    if ($stmt_venda->execute()) {
        $venda_id = $conn->insert_id;

        // Cria notificação para o admin
        $mensagem_notif = "Nova compra realizada! Valor: $valor pelo cliente ID $cliente_id (Venda ID $venda_id) em " . date('d/m/Y H:i');
        $stmt_notif = $conn->prepare("INSERT INTO notificacoes (mensagem, admin_id) VALUES (?, ?)");
        $stmt_notif->bind_param("si", $mensagem_notif, $admin_id);
        $stmt_notif->execute();
        $stmt_notif->close();

        echo "<script>alert('Compra concluída com sucesso!'); window.location.href='index.php';</script>";
        $stmt_venda->close();
        exit;
    } else {
        echo "<script>alert('Erro ao processar a compra.'); window.location.href='index.php';</script>";
        $stmt_venda->close();
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processar Compra - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <div class="container">
        <h1>Confirmar Compra</h1>
        <form method="POST" action="">
            <p>Valor total: R$ <?php echo number_format($valor, 2, ',', '.'); ?></p>
            <button type="submit" name="comprar" class="submit-button">Confirmar Compra</button>
            <a href="index.php" class="back-link">Voltar</a>
        </form>
    </div>
</body>

</html>