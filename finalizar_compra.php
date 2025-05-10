<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['mensagem'] = "É necessário estar registado para finalizar a compra.";
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Obtém itens do carrinho
$sql = "SELECT c.id, c.quantidade, p.id AS produto_id, p.nome, p.preco, p.descricao, p.quantidade_estoque 
        FROM carrinho c 
        JOIN produtos p ON c.produto_id = p.id 
        WHERE c.usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$carrinho = $stmt->get_result();

// Calcula o subtotal do carrinho
$total_carrinho = 0;
$itens_carrinho = [];
while ($item = $carrinho->fetch_assoc()) {
    $total_item = $item['preco'] * $item['quantidade'];
    $total_carrinho += $total_item;
    $itens_carrinho[] = $item;
}

// Aplica o desconto do cupom, se existir
$desconto = 0;
if (isset($_SESSION['cupom'])) {
    $desconto = $_SESSION['cupom']['desconto'];
}
$total_com_desconto = $total_carrinho - $desconto;
if ($total_com_desconto < 0) {
    $total_com_desconto = 0;
}

// Processa a finalização da compra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_compra'])) {
    // Inicia uma transação para garantir consistência
    $conn->begin_transaction();

    try {
        // Insere o pedido na tabela pedidos
        $sql = "INSERT INTO pedidos (usuario_id, status, total) VALUES (?, 'pendente', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("id", $usuario_id, $total_com_desconto);
        $stmt->execute();
        $pedido_id = $conn->insert_id;

        // Insere os itens do pedido na tabela itens_pedido e atualiza o estoque
        foreach ($itens_carrinho as $item) {
            // Insere na tabela itens_pedido
            $sql = "INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiid", $pedido_id, $item['produto_id'], $item['quantidade'], $item['preco']);
            $stmt->execute();

            // Atualiza o estoque do produto
            $nova_quantidade = $item[' disadvantidade_estoque'] - $item['quantidade'];
            if ($nova_quantidade < 0) {
                throw new Exception("Estoque insuficiente para o produto: " . $item['nome']);
            }
            $sql = "UPDATE produtos SET quantidade_estoque = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $nova_quantidade, $item['produto_id']);
            $stmt->execute();
        }

        // Limpa o carrinho
        $sql = "DELETE FROM carrinho WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();

        // Registra log da compra
        $sql = "INSERT INTO logs (usuario_id, acao, detalhes, data_log) 
                VALUES (?, 'Compra finalizada', ?, NOW())";
        $detalhes = "Usuário ID $usuario_id finalizou o pedido ID $pedido_id com total €" . number_format($total_com_desconto, 2, ',', '.');
        if (isset($_SESSION['cupom'])) {
            $detalhes .= " (Cupom: {$_SESSION['cupom']['codigo']}, Desconto: €" . number_format($desconto, 2, ',', '.') . ")";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $usuario_id, $detalhes);
        $stmt->execute();

        // Confirma a transação
        $conn->commit();
        unset($_SESSION['cupom']); // Limpa o cupom

        $_SESSION['mensagem'] = "Compra finalizada com sucesso! Pedido ID: $pedido_id";
        $_SESSION['mensagem_sucesso'] = true;
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        // Reverte a transação em caso de erro
        $conn->rollback();
        $_SESSION['mensagem'] = "Erro ao finalizar compra: " . $e->getMessage();
        header("Location: finalizar_compra.php");
        exit();
    }
}

$mensagem = isset($_SESSION['mensagem']) ? $_SESSION['mensagem'] : '';
$mensagem_classe = isset($_SESSION['mensagem_sucesso']) ? 'mensagem-sucesso' : 'mensagem';
unset($_SESSION['mensagem'], $_SESSION['mensagem_sucesso']);
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="finalizar-compra">
    <div class="finalizar-compra-container">
        <h1>Finalizar Compra</h1>

        <?php if ($mensagem): ?>
        <div id="mensagem" class="<?php echo $mensagem_classe; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
        <script>
        setTimeout(() => {
            document.getElementById('mensagem').style.display = 'none';
        }, 2000);
        </script>
        <?php endif; ?>

        <?php if (!empty($itens_carrinho)): ?>
        <h2>Resumo do Pedido</h2>
        <table class="finalizar-compra-table">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Descrição</th>
                    <th>Preço</th>
                    <th>Quantidade</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens_carrinho as $item): 
                        $total_item = $item['preco'] * $item['quantidade'];
                    ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['nome']); ?></td>
                    <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                    <td>€<?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
                    <td><?php echo $item['quantidade']; ?></td>
                    <td>€<?php echo number_format($total_item, 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="resumo-totais">
            <p><strong>Subtotal:</strong> €<?php echo number_format($total_carrinho, 2, ',', '.'); ?></p>
            <?php if ($desconto > 0): ?>
            <p><strong>Desconto (Cupom):</strong> -€<?php echo number_format($desconto, 2, ',', '.'); ?></p>
            <?php endif; ?>
            <p><strong>Total:</strong> €<?php echo number_format($total_com_desconto, 2, ',', '.'); ?></p>
        </div>
        <form method="POST">
            <button type="submit" name="confirmar_compra" class="btn-confirmar">Confirmar Compra</button>
        </form>
        <div class="link">
            <a href="carrinho.php" class="btn">Voltar ao Carrinho</a>
            <br><a href="index.php">Voltar para a página principal</a>
        </div>
        <?php else: ?>
        <p>O seu carrinho está vazio.</p>
        <div class="link">
            <a href="produtos.php" class="btn">Continuar Compras</a>
            <br><a href="index.php">Voltar para a página principal</a>
        </div>
        <?php endif; ?>
    </div>
    <footer>
        <p>© 2024-2025 Mercado Bom Preço, onde o preço é bom!</p>
    </footer>
</body>

</html>
<?php $conn->close(); ?>