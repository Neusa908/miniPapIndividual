<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['utilizador_id'])) {
    $_SESSION['mensagem'] = "É necessário estar registado para finalizar a compra.";
    header("Location: login.php");
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];

// Obtém o saldo do usuário
$sql = "SELECT saldo FROM utilizadores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $utilizador_id);
$stmt->execute();
$stmt->bind_result($saldo);
$stmt->fetch();
$stmt->close();

if ($saldo === null) {
    $saldo = 0.00;
}

// Obtém endereços do utilizador
$sql = "SELECT id, nome_endereco, rua, numero, freguesia, cidade, distrito, codigo_postal FROM enderecos WHERE utilizador_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $utilizador_id);
$stmt->execute();
$enderecos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtém itens do carrinho
$sql = "SELECT c.id, c.quantidade, p.id AS produto_id, p.nome, p.preco, p.descricao, p.quantidade_estoque 
        FROM carrinho c 
        JOIN produtos p ON c.produto_id = p.id 
        WHERE c.utilizador_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $utilizador_id);
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
$stmt->close();

// Calcula frete com base no endereço selecionado
$frete = 0;
$cidade_entrega = '';
if (isset($_POST['endereco_id']) && is_numeric($_POST['endereco_id'])) {
    $endereco_id = $_POST['endereco_id'];
    $sql = "SELECT cidade FROM enderecos WHERE id = ? AND utilizador_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $endereco_id, $utilizador_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($endereco = $result->fetch_assoc()) {
        $cidade_entrega = $endereco['cidade'];
        $sql = "SELECT valor_frete FROM fretes WHERE cidade = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $cidade_entrega);
        $stmt->execute();
        $result = $stmt->get_result();
        $frete = $result->fetch_assoc()['valor_frete'] ?? 10.00;
    }
    $stmt->close();
}

// Aplica o desconto do cupom, se existir
$desconto = 0;
if (isset($_SESSION['cupao'])) {
    $desconto = $_SESSION['cupao']['desconto'];
}
$total_com_desconto = $total_carrinho - $desconto + $frete;
if ($total_com_desconto < 0) {
    $total_com_desconto = 0;
}

// Processa a finalização da compra e pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_compra']) && isset($_POST['endereco_id'])) {
    $tipo_pagamento = $_POST['tipo_pagamento'] ?? 'saldo';
    $detalhes_pagamento = $_POST['detalhes_pagamento'] ?? '';
    $endereco_id = $_POST['endereco_id'];

    // Verifica se o saldo é suficiente
    if ($saldo < $total_com_desconto) {
        $_SESSION['mensagem'] = "Saldo insuficiente. Recarregue seu saldo ou reduza os itens.";
        header("Location: finalizar_compra.php");
        exit();
    }

    // Inicia uma transação
    $conn->begin_transaction();

    try {
        // Insere o pedido na tabela pedidos
        $sql = "INSERT INTO pedidos (utilizador_id, status, total, cidade_entrega) VALUES (?, 'pendente', ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ids", $utilizador_id, $total_com_desconto, $cidade_entrega);
        $stmt->execute();
        $pedido_id = $conn->insert_id;
        $stmt->close();

        // Insere na tabela entregas
        $sql = "INSERT INTO entregas (pedido_id, endereco_id, status_entrega) VALUES (?, ?, 'preparando')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $pedido_id, $endereco_id);
        $stmt->execute();
        $stmt->close();

        foreach ($itens_carrinho as $item) {
            $sql = "INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiid", $pedido_id, $item['produto_id'], $item['quantidade'], $item['preco']);
            $stmt->execute();

            $nova_quantidade = $item['quantidade_estoque'] - $item['quantidade'];
            if ($nova_quantidade < 0) {
                throw new Exception("Estoque insuficiente para o produto: " . $item['nome']);
            }
            $sql = "UPDATE produtos SET quantidade_estoque = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $nova_quantidade, $item['produto_id']);
            $stmt->execute();
            $stmt->close();
        }

        // Atualiza o saldo do utilizador
        $novo_saldo = $saldo - $total_com_desconto;
        $sql = "UPDATE utilizadores SET saldo = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $novo_saldo, $utilizador_id);
        $stmt->execute();
        $stmt->close();

        // Registra o pagamento na tabela pagamentos
        $sql = "INSERT INTO pagamentos (utilizador_id, tipo, detalhes, data_cadastro) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $utilizador_id, $tipo_pagamento, $detalhes_pagamento);
        $stmt->execute();
        $stmt->close();

        // Atualiza o status do pedido para "pago"
        $sql = "UPDATE pedidos SET status = 'pago' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        $stmt->close();

        // Limpa o carrinho
        $sql = "DELETE FROM carrinho WHERE utilizador_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $utilizador_id);
        $stmt->execute();
        $stmt->close();

        // Registra log da compra
        $sql = "INSERT INTO logs (utilizador_id, acao, detalhes, data_log) 
                VALUES (?, 'Compra finalizada', ?, NOW())";
        $detalhes = "Utilizador ID $utilizador_id finalizou o pedido ID $pedido_id com total €" . number_format($total_com_desconto, 2, ',', '.') . " (Frete: €" . number_format($frete, 2, ',', '.') . ")";
        if (isset($_SESSION['cupao'])) {
            $detalhes .= " (Cupao: {$_SESSION['cupao']['codigo']}, Desconto: €" . number_format($desconto, 2, ',', '.') . ")";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $utilizador_id, $detalhes);
        $stmt->execute();
        $stmt->close();

        // Confirma a transação
        $conn->commit();
        unset($_SESSION['cupao']);

        $_SESSION['mensagem'] = "Compra finalizada com sucesso! Pedido ID: $pedido_id";
        $_SESSION['mensagem_sucesso'] = true;
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
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
            <p><strong>Desconto (Cupao):</strong> -€<?php echo number_format($desconto, 2, ',', '.'); ?></p>
            <?php endif; ?>
            <p><strong>Frete:</strong> €<?php echo number_format($frete, 2, ',', '.'); ?></p>
            <p><strong>Total:</strong> €<?php echo number_format($total_com_desconto, 2, ',', '.'); ?></p>
            <p><strong>Seu Saldo:</strong> €<?php echo number_format($saldo, 2, ',', '.'); ?></p>
        </div>

        <h2>Selecionar Endereço de Entrega</h2>
        <?php if (!empty($enderecos)): ?>
        <form method="POST">
            <select name="endereco_id" required>
                <option value="">Selecione um endereço</option>
                <?php foreach ($enderecos as $endereco): ?>
                <option value="<?php echo $endereco['id']; ?>">
                    <?php echo htmlspecialchars($endereco['nome_endereco'] . ' - ' . $endereco['rua'] . ', ' . $endereco['freguesia'] . ', ' . $endereco['cidade']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <a href="adicionar_endereco.php" class="btn">Adicionar Novo Endereço</a>
            <p></p>
            <?php if ($saldo >= $total_com_desconto): ?>
            <h2>Pagamento com Saldo Virtual</h2>
            <input type="hidden" name="tipo_pagamento" value="saldo">
            <input type="hidden" name="detalhes_pagamento" value="Pagamento com saldo virtual">
            <button type="submit" name="confirmar_compra" class="btn-confirmar">Confirmar Pagamento com Saldo</button>
            <?php else: ?>
            <p class="mensagem">Saldo insuficiente. Recarregue seu saldo ou remova itens do carrinho.</p>
            <?php endif; ?>
        </form>
        <?php else: ?>
        <p>Você não tem endereços registados.</p>
        <a href="configuracoes.php" class="btn">Adicionar Endereço</a>
        <?php endif; ?>
        <div class="link">
            <a href="carrinho.php" class="btn">Voltar ao Carrinho</a>
            <br><a href="index.php">Voltar para a página principal</a>
        </div>
        <?php else: ?>
        <p>O seu carrinho está vazio.</p>
        <div class="link">
            <a href="produtos.php" class="btn">Continuar Compras</a>
            <br><a href="index.php" class="finalizarCompra">Voltar para a página principal</a>
        </div>
        <?php endif; ?>
    </div>
    <footer>
        <p>© 2024-2025 Mercado Bom Preço, onde o preço é bom!</p>
    </footer>
</body>

</html>
<?php $conn->close(); ?>