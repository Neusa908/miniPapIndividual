<?php 
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'cliente') {
    echo "<script>alert('Acesso negado! Apenas clientes podem aceder a esta página.'); window.location.href='index.php';</script>";
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];

// Endereços
$sql = "SELECT id, nome_endereco, rua, numero, freguesia, cidade, distrito, codigo_postal FROM enderecos WHERE utilizador_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $utilizador_id);
$stmt->execute();
$enderecos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Métodos de pagamento
$sql = "SELECT id, tipo, detalhes FROM pagamentos WHERE utilizador_id = ? ORDER BY data_cadastro DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $utilizador_id);
$stmt->execute();
$pagamentos_salvos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Carrinho
$sql = "SELECT c.quantidade, p.id AS produto_id, p.nome, p.preco, p.descricao, p.quantidade_estoque 
        FROM carrinho c 
        JOIN produtos p ON c.produto_id = p.id 
        WHERE c.utilizador_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $utilizador_id);
$stmt->execute();
$carrinho = $stmt->get_result();

$total_carrinho = 0;
$itens_carrinho = [];
while ($item = $carrinho->fetch_assoc()) {
    $total_item = $item['preco'] * $item['quantidade'];
    $total_carrinho += $total_item;
    $itens_carrinho[] = $item;
}
$stmt->close();

// Cupão de desconto
$desconto = isset($_SESSION['cupao']) ? $_SESSION['cupao']['desconto'] : 0;
$total_com_desconto = max(0, $total_carrinho - $desconto);

// Finalizar compra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_compra']) && isset($_POST['endereco_id'])) {
    $pagamento_id = $_POST['pagamento_id'] ?? '';
    $endereco_id = $_POST['endereco_id'];

    $sql = "SELECT tipo, detalhes FROM pagamentos WHERE id = ? AND utilizador_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $pagamento_id, $utilizador_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $pagamento = $resultado->fetch_assoc();
    $stmt->close();

    if (!$pagamento) {
        $_SESSION['mensagem'] = "Forma de pagamento inválida.";
        header("Location: finalizar_compra.php");
        exit();
    }

    $tipo_pagamento = $pagamento['tipo'];
    $detalhes_pagamento = $pagamento['detalhes'];

    $conn->begin_transaction();

    try {
        // Gerar número de pedido
        $sql = "SELECT COUNT(*) as total FROM pedidos WHERE utilizador_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $utilizador_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $sequencia = str_pad($row['total'] + 1, 3, '0', STR_PAD_LEFT);
        $numero_pedido = "USR" . $utilizador_id . "-" . $sequencia;
        $stmt->close();

        $sql = "INSERT INTO pedidos (utilizador_id, numero_pedido, status, total) VALUES (?, ?, 'pendente', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isd", $utilizador_id, $numero_pedido, $total_com_desconto);
        $stmt->execute();
        $pedido_id = $conn->insert_id;
        $stmt->close();

        $tempo_entrega_min = rand(10, 20);
        $data_entrega_estimada = date("Y-m-d H:i:s", strtotime("+$tempo_entrega_min minutes"));

        $sql = "INSERT INTO entregas (pedido_id, endereco_id, status_entrega, data_entrega_estimada) VALUES (?, ?, 'preparando', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $pedido_id, $endereco_id, $data_entrega_estimada);
        $stmt->execute();
        $stmt->close();

        foreach ($itens_carrinho as $item) {
            $sql = "INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiid", $pedido_id, $item['produto_id'], $item['quantidade'], $item['preco']);
            $stmt->execute();

            $nova_quantidade = $item['quantidade_estoque'] - $item['quantidade'];
            if ($nova_quantidade < 0) throw new Exception("Stock insuficiente para " . $item['nome']);

            $sql = "UPDATE produtos SET quantidade_estoque = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $nova_quantidade, $item['produto_id']);
            $stmt->execute();
            $stmt->close();
        }

        $sql = "UPDATE pedidos SET status = 'pago' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        $stmt->close();

        $sql = "DELETE FROM carrinho WHERE utilizador_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $utilizador_id);
        $stmt->execute();
        $stmt->close();

        $sql = "INSERT INTO logs (utilizador_id, acao, detalhes, data_log) 
                VALUES (?, 'Compra finalizada', ?, NOW())";
        $detalhes = "Pedido ID $pedido_id (Ref: $numero_pedido) com total €" . number_format($total_com_desconto, 2, ',', '.');
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $utilizador_id, $detalhes);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        unset($_SESSION['cupao']);

        header("Location: entregas.php?minutos=$tempo_entrega_min&pedido_id=$pedido_id");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensagem'] = "Erro: " . $e->getMessage();
        header("Location: finalizar_compra.php");
        exit();
    }
}

$mensagem = $_SESSION['mensagem'] ?? '';
$mensagem_classe = $_SESSION['mensagem_sucesso'] ?? false ? 'mensagem-sucesso' : 'mensagem';
unset($_SESSION['mensagem'], $_SESSION['mensagem_sucesso']);
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <title>Finalizar Compra - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="finalizar-compra">
    <div class="finalizar-compra-container">
        <h1>Finalizar Compra</h1>

        <?php if ($mensagem): ?>
        <div id="mensagem" class="<?= $mensagem_classe ?>">
            <?= htmlspecialchars($mensagem) ?>
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
                <?php foreach ($itens_carrinho as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['nome']) ?></td>
                    <td><?= htmlspecialchars($item['descricao']) ?></td>
                    <td>€<?= number_format($item['preco'], 2, ',', '.') ?></td>
                    <td><?= $item['quantidade'] ?></td>
                    <td>€<?= number_format($item['preco'] * $item['quantidade'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="resumo-totais">
            <p><strong>Subtotal:</strong> €<?= number_format($total_carrinho, 2, ',', '.') ?></p>
            <?php if ($desconto > 0): ?>
            <p><strong>Desconto:</strong> -€<?= number_format($desconto, 2, ',', '.') ?></p>
            <?php endif; ?>
            <p><strong>Total:</strong> €<?= number_format($total_com_desconto, 2, ',', '.') ?></p>
        </div>

        <h2>Endereço de Entrega</h2>
        <form method="POST">
            <select name="endereco_id" required>
                <option value="">Selecione</option>
                <?php foreach ($enderecos as $e): ?>
                <option value="<?= $e['id'] ?>"
                    <?= (isset($_POST['endereco_id']) && $_POST['endereco_id'] == $e['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars("{$e['nome_endereco']} - {$e['rua']}, {$e['freguesia']}, {$e['cidade']}") ?>
                </option>
                <?php endforeach; ?>
            </select>

            <p><a href="configuracoes.php" class="btn">Adicionar Novo Endereço</a></p>

            <h2>Forma de Pagamento</h2>
            <select name="pagamento_id" required>
                <option value="">Escolha um método salvo</option>
                <?php foreach ($pagamentos_salvos as $pg): ?>
                <option value="<?= $pg['id'] ?>"
                    <?= (isset($_POST['pagamento_id']) && $_POST['pagamento_id'] == $pg['id']) ? 'selected' : '' ?>>
                    <?= ucfirst($pg['tipo']) ?> - <?= htmlspecialchars($pg['detalhes']) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <p><a href="carteira.php" class="btn">Métodos de Pagamento</a></p>
            <p><button type="submit" name="confirmar_compra" class="btn">Confirmar Compra</button></p>
        </form>

        <?php else: ?>
        <p>O seu carrinho está vazio. <a href="index.php">Voltar para a loja</a>.</p>
        <?php endif; ?>
    </div>
</body>

</html>