<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['mensagem'] = "É necessário estar registado para adicionar itens ao carrinho.";
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

$mensagem = isset($_SESSION['mensagem']) ? $_SESSION['mensagem'] : '';
$mensagem_classe = isset($_SESSION['mensagem_sucesso']) ? 'mensagem-sucesso' : 'mensagem-erro';
unset($_SESSION['mensagem'], $_SESSION['mensagem_sucesso']);

$desconto = 0;
$cupom_mensagem = '';
$cupom_mensagem_classe = '';

if (isset($_GET['limpar_carrinho'])) {
    $sql = "DELETE FROM carrinho WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            unset($_SESSION['cupom']); 
            $cupom_mensagem = "Carrinho limpo com sucesso.";
            $cupom_mensagem_classe = 'mensagem-sucesso';
        } else {
            $cupom_mensagem = "O carrinho já está vazio.";
            $cupom_mensagem_classe = 'mensagem-erro';
        }
    } else {
        $cupom_mensagem = "Erro ao limpar o carrinho.";
        $cupom_mensagem_classe = 'mensagem-erro';
    }
    header("Location: carrinho.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aplicar_cupom'])) {
    $codigo = trim($_POST['promo_code']);

    $sql = "SELECT desconto FROM promocoes WHERE codigo = ? AND ativa = 1 AND data_inicio <= NOW() AND data_fim >= NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $promocao = $result->fetch_assoc();
        $_SESSION['cupom'] = [
            'codigo' => $codigo,
            'desconto' => $promocao['desconto']
        ];
        $desconto = $promocao['desconto'];
        $cupom_mensagem = "Cupom aplicado com sucesso! Desconto de €" . number_format($desconto, 2, ',', '.');
        $cupom_mensagem_classe = 'mensagem-sucesso';
    } else {
        unset($_SESSION['cupom']);
        $cupom_mensagem = "Cupom inválido ou expirado.";
        $cupom_mensagem_classe = 'mensagem-erro';
    }
    header("Location: carrinho.php");
    exit();
}

if (isset($_SESSION['cupom'])) {
    $desconto = $_SESSION['cupom']['desconto'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_carrinho'])) {
    $produto_id = intval($_POST['produto_id']);
    $quantidade = intval($_POST['quantidade']);

    $sql = "SELECT id, quantidade_estoque, preco FROM produtos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $produto = $stmt->get_result()->fetch_assoc();

    if ($produto && $quantidade > 0 && $quantidade <= $produto['quantidade_estoque']) {
        $sql = "SELECT id, quantidade FROM carrinho WHERE usuario_id = ? AND produto_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $usuario_id, $produto_id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();

        if ($item) {
            $nova_quantidade = $item['quantidade'] + $quantidade;
            $sql = "UPDATE carrinho SET quantidade = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $nova_quantidade, $item['id']);
        } else {
            $sql = "INSERT INTO carrinho (usuario_id, produto_id, quantidade) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $usuario_id, $produto_id, $quantidade);
        }

        if ($stmt->execute()) {
            $cupom_mensagem = "Item adicionado ao carrinho.";
            $cupom_mensagem_classe = 'mensagem-sucesso';
        } else {
            $cupom_mensagem = "Erro ao adicionar item ao carrinho.";
            $cupom_mensagem_classe = 'mensagem-erro';
        }
    } else {
        $cupom_mensagem = "Quantidade inválida ou estoque insuficiente.";
        $cupom_mensagem_classe = 'mensagem-erro';
    }
    header("Location: carrinho.php");
    exit();
}

// Remove itens do carrinho
if (isset($_GET['remover'])) {
    $item_id = intval($_GET['remover']);
    if ($item_id > 0) {
        $sql = "DELETE FROM carrinho WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $item_id, $usuario_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $cupom_mensagem = "Item removido do carrinho.";
                $cupom_mensagem_classe = 'mensagem-sucesso';
            } else {
                $cupom_mensagem = "Item não encontrado.";
                $cupom_mensagem_classe = 'mensagem-erro';
            }
        } else {
            $cupom_mensagem = "Erro ao remover item.";
            $cupom_mensagem_classe = 'mensagem-erro';
        }
        $stmt->close();
    } else {
        $cupom_mensagem = "ID de item inválido.";
        $cupom_mensagem_classe = 'mensagem-erro';
    }
    header("Location: carrinho.php");
    exit();
}

// Obtém itens do carrinho
$carrinho = null;
$total_carrinho = 0;
$itens_carrinho = [];
if ($conn) {
    $sql = "SELECT c.id, c.quantidade, p.id AS produto_id, p.nome, p.preco, p.imagem 
            FROM carrinho c 
            JOIN produtos p ON c.produto_id = p.id 
            WHERE c.usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $carrinho = $stmt->get_result();

    // Calcula o subtotal
    while ($item = $carrinho->fetch_assoc()) {
        $total_item = $item['preco'] * $item['quantidade'];
        $total_carrinho += $total_item;
        $itens_carrinho[] = $item;
    }
} else {
    $cupom_mensagem = "Erro ao conectar ao banco de dados.";
    $cupom_mensagem_classe = 'mensagem-erro';
}

// Calcula o total com desconto
$total_com_desconto = $total_carrinho - $desconto;
if ($total_com_desconto < 0) {
    $total_com_desconto = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Carrinho de Compras</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body style="background: url('img/mercado.jpeg') no-repeat center center; background-size: cover;">
    <div class="container">
        <h1 class="title">Carrinho de Compras</h1>

        <?php if ($cupom_mensagem): ?>
        <div id="cupom-mensagem" class="<?php echo $cupom_mensagem_classe; ?>">
            <?php echo htmlspecialchars($cupom_mensagem); ?>
        </div>
        <script>
        setTimeout(() => {
            document.getElementById('cupom-mensagem').style.display = 'none';
        }, 3000);
        </script>
        <?php endif; ?>

        <table class="cart-table">
            <thead>
                <tr>
                    <th class="product-header">Produto</th>
                    <th class="price-header">Preço</th>
                    <th class="quantity-header">Quantidade</th>
                    <th class="subtotal-header">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($itens_carrinho)) {
                    foreach ($itens_carrinho as $item): 
                        $total_item = $item['preco'] * $item['quantidade'];
                ?>
                <tr data-item-id="<?php echo $item['id']; ?>">
                    <td class="product-cell">
                        <a href="carrinho.php?remover=<?php echo $item['id']; ?>" class="remove-btn">×</a>
                        <?php if ($item['imagem']): ?>
                        <img class="product-img" src="<?php echo htmlspecialchars($item['imagem']); ?>"
                            alt="<?php echo htmlspecialchars($item['nome']); ?>" />
                        <?php else: ?>
                        <img class="product-img"
                            src="https://placehold.co/50x50?text=<?php echo urlencode($item['nome']); ?>"
                            alt="<?php echo htmlspecialchars($item['nome']); ?>" />
                        <?php endif; ?>
                        <span class="product-name"><?php echo htmlspecialchars($item['nome']); ?></span>
                    </td>
                    <td class="price-cell"><b>€<?php echo number_format($item['preco'], 2, ',', '.'); ?></b></td>
                    <td class="quantity-cell">
                        <div class="quantity-form">
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn minus"
                                    onclick="atualizarQuantidade(<?php echo $item['id']; ?>, <?php echo $item['produto_id']; ?>, <?php echo $item['quantidade']; ?>, 'diminuir')"></button>
                                <input type="text" class="quantity-display" value="<?php echo $item['quantidade']; ?>"
                                    readonly>
                                <button type="button" class="quantity-btn plus"
                                    onclick="atualizarQuantidade(<?php echo $item['id']; ?>, <?php echo $item['produto_id']; ?>, <?php echo $item['quantidade']; ?>, 'aumentar')"></button>
                            </div>
                        </div>
                    </td>
                    <td class="subtotal-cell" data-subtotal="<?php echo $total_item; ?>">
                        €<?php echo number_format($total_item, 2, ',', '.'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php } else { ?>
                <tr>
                    <td colspan="4" class="subtotal-cell">O seu carrinho está vazio.</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <div class="bottom-section">
            <form class="promo-form" method="POST">
                <label for="promo-code" class="promo-label">Código Promocional</label>
                <input type="text" id="promo-code" name="promo_code" class="promo-input"
                    placeholder="Código de Cupão" />
                <button type="submit" name="aplicar_cupom" class="promo-btn">Aplicar Cupão</button>
            </form>
            <div class="cart-totals">
                <div class="subtotal-row">
                    <span class="subtotal-label"><b>Subtotal</b></span>
                    <span class="subtotal-value" data-total-carrinho="<?php echo $total_carrinho; ?>">
                        <b>€<?php echo number_format($total_carrinho, 2, ',', '.'); ?></b>
                    </span>
                </div>
                <?php if ($desconto > 0): ?>
                <div class="subtotal-row">
                    <span class="subtotal-label"><b>Desconto (Cupom)</b></span>
                    <span class="subtotal-value"><b>-€<?php echo number_format($desconto, 2, ',', '.'); ?></b></span>
                </div>
                <?php endif; ?>
                <div class="total-row">
                    <span class="total-label"><b>Total</b></span>
                    <span class="total-value" data-total-com-desconto="<?php echo $total_com_desconto; ?>">
                        <b>€<?php echo number_format($total_com_desconto, 2, ',', '.'); ?></b>
                    </span>
                </div>
            </div>
        </div>
        <div class="checkout-container">
            <a href="produtos.php" class="checkout-btn continue-shopping">Continuar a Comprar</a>
            <a href="carrinho.php?limpar_carrinho=1" class="checkout-btn"
                onclick="return confirm('Tem certeza que deseja limpar o carrinho?');">Limpar Carrinho</a>
            <a href="finalizar_compra.php" class="checkout-btn">Finalizar Compra</a>
        </div>
    </div>
    <footer class="carrinho-footer">
        <p>© 2024-2025 Mercado Bom Preço, onde o preço é bom!</p>
    </footer>

    <script>
    function atualizarQuantidade(itemId, produtoId, currentQuantidade, acao) {
        const novaQuantidade = acao === 'aumentar' ? currentQuantidade + 1 : currentQuantidade - 1;
        if (novaQuantidade < 1) return;
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'atualizar_carrinho.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    const mensagemDiv = document.getElementById('cupom-mensagem');
                    mensagemDiv.innerHTML = response.mensagem;
                    mensagemDiv.className = response.classe;
                    mensagemDiv.style.display = 'block';
                    setTimeout(() => {
                        mensagemDiv.style.display = 'none';
                    }, 3000);

                    if (response.sucesso) {
                        const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
                        const quantityDisplay = row.querySelector('.quantity-display');
                        quantityDisplay.value = response.nova_quantidade;

                        const subtotalCell = row.querySelector('.subtotal-cell');
                        subtotalCell.textContent = `€${response.subtotal_linha.toFixed(2).replace('.', ',')}`;
                        subtotalCell.dataset.subtotal = response.subtotal_linha;

                        const subtotalValue = document.querySelector('.subtotal-value');
                        const totalValue = document.querySelector('.total-value');
                        subtotalValue.dataset.totalCarrinho = response.total_carrinho;
                        subtotalValue.innerHTML = `<b>€${response.total_carrinho.toFixed(2).replace('.', ',')}</b>`;
                        totalValue.dataset.totalComDesconto = response.total_com_desconto;
                        totalValue.innerHTML =
                            `<b>€${response.total_com_desconto.toFixed(2).replace('.', ',')}</b>`;

                        const minusBtn = row.querySelector('.quantity-btn.minus');
                        const plusBtn = row.querySelector('.quantity-btn.plus');
                        minusBtn.setAttribute('onclick',
                            `atualizarQuantidade(${itemId}, ${produtoId}, ${response.nova_quantidade}, 'diminuir')`
                        );
                        plusBtn.setAttribute('onclick',
                            `atualizarQuantidade(${itemId}, ${produtoId}, ${response.nova_quantidade}, 'aumentar')`
                        );
                    } else {
                        alert('Erro: ' + response.mensagem);
                    }
                } else {
                    alert('Erro na requisição AJAX. Status: ' + xhr.status);
                }
            }
        };
        const data = `item_id=${itemId}&produto_id=${produtoId}&current_quantidade=${currentQuantidade}&acao=${acao}`;
        xhr.send(data);
    }
    </script>

    <?php
    if ($conn) {
        $conn->close();
    }
    ?>
</body>

</html>