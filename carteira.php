<?php
session_start();
require_once 'conexao.php';

$mensagem = '';
$mensagem_classe = 'mensagem';

if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'cliente') {
    echo "<script>alert('Acesso negado! Apenas clientes podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];

// Função para formatar o tipo de pagamento
function formatarTipoPagamento($tipo) {
    $tipos = [
        'cartao credito' => 'Cartão de Crédito',
        'cartao debito' => 'Cartão de Débito'
    ];

    $tipoLower = strtolower(trim($tipo));
    return $tipos[$tipoLower] ?? ucfirst($tipo);
}

// Obter saldo
$sql_saldo = "SELECT saldo FROM utilizadores WHERE id = ?";
$stmt_saldo = $conn->prepare($sql_saldo);
$stmt_saldo->bind_param("i", $utilizador_id);
$stmt_saldo->execute();
$saldo = $stmt_saldo->get_result()->fetch_assoc()['saldo'] ?? 0.00;
$stmt_saldo->close();

// Adicionar saldo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar_dinheiro'])) {
    $pagamento_id = intval($_POST['metodo_pagamento']);
    $valor = floatval(str_replace(',', '.', $_POST['valor']));

    if ($valor > 0) {
        $sql = "UPDATE utilizadores SET saldo = saldo + ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $valor, $utilizador_id);
        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Saldo adicionado com sucesso!";
            $_SESSION['mensagem_sucesso'] = true;
        } else {
            $_SESSION['mensagem'] = "Erro ao adicionar saldo.";
        }
        $stmt->close();
    } else {
        $_SESSION['mensagem'] = "Valor inválido.";
    }
    header("Location: carteira.php");
    exit;
}

// Remover pagamento
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['remover_pagamento'])) {
    $pagamento_id = intval($_POST['pagamento_id']);
    $stmt = $conn->prepare("DELETE FROM pagamentos WHERE id = ? AND utilizador_id = ?");
    $stmt->bind_param("ii", $pagamento_id, $utilizador_id);
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = "Método de pagamento removido com sucesso.";
        $_SESSION['mensagem_sucesso'] = true;
    }
    header("Location: carteira.php");
    exit;
}

// Adicionar pagamento
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar_pagamento'])) {
    $tipo = trim($_POST['tipo']);
    $erros = [];

    if (empty($tipo)) {
        $erros[] = "O tipo de pagamento é obrigatório.";
    }

    if ($tipo === 'cartao_credito' || $tipo === 'cartao_debito') {
        $numero_cartao = preg_replace('/\D/', '', $_POST['numero_cartao']);
        $validade_cartao = trim($_POST['validade_cartao']);
        $cvv_cartao = trim($_POST['cvv_cartao']);

        if (strlen($numero_cartao) < 13 || strlen($numero_cartao) > 19) {
            $erros[] = "Número do cartão inválido.";
        }

        if (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $validade_cartao)) {
            $erros[] = "Data de validade inválida. Use MM/AA.";
        }

        if (!preg_match('/^[0-9]{3,4}$/', $cvv_cartao)) {
            $erros[] = "CVV inválido.";
        }

        if (empty($erros)) {
            $ultimos4 = substr($numero_cartao, -4);
            // CORREÇÃO: Guardar apenas os dados, sem repetir o tipo
            $detalhes = "**** **** **** $ultimos4 ($validade_cartao)";
        }
    } else {
        $detalhes = trim($_POST['detalhes'] ?? '');
        if (empty($detalhes)) {
            $erros[] = "Os detalhes do pagamento são obrigatórios.";
        }
    }

    if (empty($erros)) {
        $sql = "INSERT INTO pagamentos (utilizador_id, tipo, detalhes) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $utilizador_id, $tipo, $detalhes);
        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Método de pagamento adicionado com sucesso!";
            $_SESSION['mensagem_sucesso'] = true;
            header("Location: carteira.php");
            exit;
        } else {
            $mensagem = "Erro ao adicionar método de pagamento.";
            $mensagem_classe = 'mensagem';
        }
        $stmt->close();
    } else {
        $mensagem = implode("<br>", $erros);
        $mensagem_classe = 'mensagem';
    }
}

// Obter dados
$sql_pagamentos = "SELECT id, tipo, detalhes, data_cadastro FROM pagamentos WHERE utilizador_id = ?";
$stmt_pagamentos = $conn->prepare($sql_pagamentos);
$stmt_pagamentos->bind_param("i", $utilizador_id);
$stmt_pagamentos->execute();
$pagamentos = $stmt_pagamentos->get_result();

$sql_pedidos = "SELECT p.id, p.data_pedido, p.status, p.total, GROUP_CONCAT(CONCAT(ip.quantidade, ' x ', pr.nome, ' (€', ip.preco_unitario, ')') SEPARATOR '<br>') as itens
                FROM pedidos p
                JOIN itens_pedido ip ON p.id = ip.pedido_id
                JOIN produtos pr ON ip.produto_id = pr.id
                WHERE p.utilizador_id = ?
                GROUP BY p.id
                ORDER BY p.data_pedido DESC";
$stmt_pedidos = $conn->prepare($sql_pedidos);
$stmt_pedidos->bind_param("i", $utilizador_id);
$stmt_pedidos->execute();
$pedidos = $stmt_pedidos->get_result();

$sql_utilizador = "SELECT foto_perfil, nome FROM utilizadores WHERE id = ?";
$stmt_utilizador = $conn->prepare($sql_utilizador);
$stmt_utilizador->bind_param("i", $utilizador_id);
$stmt_utilizador->execute();
$utilizador_dados = $stmt_utilizador->get_result()->fetch_assoc();
$foto_perfil = $utilizador_dados['foto_perfil'] ?? null;
$nome_utilizador = $utilizador_dados['nome'] ?? 'Utilizador';
$stmt_utilizador->close();
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <title>Carteira - Mercado Bom Preço</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="carteira-body">
    <div class="carteira-sidebar">
        <div class="carteira-sidebar-header">
            <h3>Mercado Bom Preço</h3>
        </div>
        <nav class="carteira-sidebar-nav">
            <a href="index.php">Voltar ao Site</a>
        </nav>
    </div>

    <div class="carteira-content">
        <header class="carteira-header">
            <span class="carteira-profile-name"><b><?php echo htmlspecialchars($nome_utilizador); ?></b></span>
            <div class="carteira-profile-pic"
                style="background-image: url('<?php echo $foto_perfil ? htmlspecialchars($foto_perfil) : 'img/default-profile.jpg'; ?>');">
            </div>
        </header>

        <main class="carteira-main">
            <?php if ($mensagem): ?>
            <div id="carteira-mensagem" class="<?php echo $mensagem_classe; ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
            <script>
            setTimeout(() => {
                document.getElementById('carteira-mensagem').style.display = 'none';
            }, 3000);
            </script>
            <?php endif; ?>

            <div class="carteira-cards">
                <div class="carteira-card">
                    <h2>Saldo Atual</h2>
                    <p>Seu saldo disponível é: <strong>€<?php echo number_format($saldo, 2, ',', '.'); ?></strong></p>
                </div>

                <div class="carteira-card">
                    <h2>Métodos de Pagamento</h2>
                    <?php if ($pagamentos->num_rows > 0): ?>
                    <ul>
                        <?php while ($pagamento = $pagamentos->fetch_assoc()): ?>
                        <li class="carteira-metodo-item">
                            <strong><?php echo htmlspecialchars(formatarTipoPagamento(str_replace('_',' ', $pagamento['tipo']))); ?>:</strong>
                            <?php echo htmlspecialchars($pagamento['detalhes']); ?>
                            <br><small>Adicionado em:
                                <?php echo date('d/m/Y H:i', strtotime($pagamento['data_cadastro'])); ?></small>
                            <form method="POST"
                                onsubmit="return confirm('Tem a certeza que deseja remover este método de pagamento?');">
                                <input type="hidden" name="pagamento_id" value="<?php echo $pagamento['id']; ?>">
                                <button type="submit" name="remover_pagamento"
                                    class="carteira-btn-remover">Remover</button>
                            </form>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                    <?php else: ?>
                    <p>Não há métodos de pagamento registados.</p>
                    <?php endif; ?>

                    <form method="POST" class="carteira-form">
                        <label for="tipo">Tipo:</label>
                        <select id="tipo" name="tipo" required>
                            <option value="cartao_credito">Cartão de Crédito</option>
                            <option value="cartao_debito">Cartão de Débito</option>
                        </select>

                        <label for="numero_cartao">Número do Cartão:</label>
                        <input type="text" id="numero_cartao" name="numero_cartao" maxlength="19"
                            placeholder="1234 5678 9012 3456" required pattern="[0-9 ]{13,19}"
                            title="Insira o número do cartão">

                        <label for="validade_cartao">Data de Validade (MM/AA):</label>
                        <input type="text" id="validade_cartao" name="validade_cartao" maxlength="5" placeholder="MM/AA"
                            required pattern="(0[1-9]|1[0-2])\/[0-9]{2}" title="Formato MM/AA">

                        <label for="cvv_cartao">CVV:</label>
                        <input type="password" id="cvv_cartao" name="cvv_cartao" maxlength="4" placeholder="123"
                            required pattern="[0-9]{3,4}" title="Código CVV (3 ou 4 dígitos)">

                        <button type="submit" name="adicionar_pagamento">Adicionar</button>
                    </form>

                    <?php if ($pagamentos->num_rows > 0): ?>
                    <hr>
                    <h3>Adicionar Dinheiro</h3>
                    <form method="POST" class="carteira-form">
                        <label for="metodo_pagamento">Método:</label>
                        <select name="metodo_pagamento" id="metodo_pagamento" required>
                            <?php
                            $stmt_pagamentos->execute();
                            $pagamentos_novos = $stmt_pagamentos->get_result();
                            while ($pag = $pagamentos_novos->fetch_assoc()):
                            ?>
                            <option value="<?= $pag['id'] ?>">
                                <?= htmlspecialchars(formatarTipoPagamento(str_replace('_',' ', $pag['tipo']))) ?> -
                                <?= htmlspecialchars($pag['detalhes']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>

                        <label for="valor">Valor (€):</label>
                        <input type="number" step="0.01" name="valor" id="valor" placeholder="Ex.: 10.00" required>

                        <button type="submit" name="adicionar_dinheiro">Adicionar Dinheiro</button>
                    </form>
                    <?php endif; ?>
                </div>

                <div class="carteira-card">
                    <h2>Histórico de Compras</h2>
                    <?php if ($pedidos->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Data</th>
                                <th>Itens</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($pedido = $pedidos->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $pedido['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></td>
                                <td><?php echo $pedido['itens']; ?></td>
                                <td>€<?php echo number_format($pedido['total'], 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($pedido['status']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p>Não há pedidos registados.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>

<?php
$stmt_pagamentos->close();
$stmt_pedidos->close();
$conn->close();
?>