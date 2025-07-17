<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['utilizador_id'])) {
    $_SESSION['mensagem'] = "É necessário estar registado para ver os cupões.";
    header("Location: login.php");
    exit();
}


$utilizador_id = $_SESSION['utilizador_id'];

$mensagem = isset($_SESSION['mensagem']) ? $_SESSION['mensagem'] : '';
$mensagem_classe = isset($_SESSION['mensagem_sucesso']) ? 'mensagem-sucesso' : 'mensagem-erro';
unset($_SESSION['mensagem'], $_SESSION['mensagem_sucesso']);

$cupons = [];
if ($conn) {
    $sql = "SELECT id, codigo, desconto, data_inicio, data_fim FROM promocoes WHERE ativa = 1 AND data_inicio <= NOW() AND data_fim >= NOW()";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $cupons[] = $row;
        }
    } else {
        $mensagem = "Erro ao carregar os cupões.";
        $mensagem_classe = 'mensagem-erro';
    }
    $conn->close();
}
?>




<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Meus Cupões</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body style="background: url('img/mercado.jpeg') no-repeat center center; background-size: cover;">
    <div class="container">
        <h1 class="title">Meus Cupões</h1>

        <?php if ($mensagem): ?>
        <div id="mensagem" class="<?php echo $mensagem_classe; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
        <script>
        setTimeout(() => {
            document.getElementById('mensagem').style.display = 'none';
        }, 3000);
        </script>
        <?php endif; ?>

        <?php if (!empty($cupons)): ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th class="product-header">Código</th>
                    <th class="price-header">Desconto</th>
                    <th class="quantity-header">Válido de</th>
                    <th class="subtotal-header">Até</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cupons as $cupao): ?>
                <tr>
                    <td class="product-cell">
                        <?php echo htmlspecialchars($cupao['codigo']); ?>
                    </td>
                    <td class="price-cell"><b>€
                            <?php echo number_format($cupao['desconto'], 2, ',', '.'); ?>
                        </b></td>
                    <td class="quantity-cell">
                        <?php echo date('d-m-Y H:i', strtotime($cupao['data_inicio'])); ?>
                    </td>
                    <td class="subtotal-cell">
                        <?php echo date('d-m-Y H:i', strtotime($cupao['data_fim'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p class="mensagem-erro">Nenhum cupão disponível no momento.</p>
        <?php endif; ?>

        <div class="checkout-container">
            <a href="index.php" class="checkout-btn continue-shopping">Início</a>
            <a href="carrinho.php" class="checkout-btn continue-shopping">Carrinho</a>

            <a href="produtos.php" class="checkout-btn">Produtos</a>
        </div>
    </div>
    <footer class="carrinho-footer">
        <p>© 2024-2025 Mercado Bom Preço, onde o preço é bom!</p>
    </footer>
</body>

</html>