<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['mensagem'] = "Faça login para acessar sua carteira.";
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$mensagem = isset($_SESSION['mensagem']) ? $_SESSION['mensagem'] : '';
$mensagem_classe = isset($_SESSION['mensagem_sucesso']) ? 'mensagem-sucesso' : 'mensagem';
unset($_SESSION['mensagem'], $_SESSION['mensagem_sucesso']);

$sql_saldo = "SELECT saldo FROM usuarios WHERE id = ?";
$stmt_saldo = $conn->prepare($sql_saldo);
$stmt_saldo->bind_param("i", $usuario_id);
$stmt_saldo->execute();
$saldo = $stmt_saldo->get_result()->fetch_assoc()['saldo'] ?? 0.00;
$stmt_saldo->close();

$sql_pagamentos = "SELECT id, tipo, detalhes, data_cadastro FROM pagamentos WHERE usuario_id = ?";
$stmt_pagamentos = $conn->prepare($sql_pagamentos);
$stmt_pagamentos->bind_param("i", $usuario_id);
$stmt_pagamentos->execute();
$pagamentos = $stmt_pagamentos->get_result();

$sql_pedidos = "SELECT p.id, p.data_pedido, p.status, p.total, GROUP_CONCAT(CONCAT(ip.quantidade, ' x ', pr.nome, ' (€', ip.preco_unitario, ')') SEPARATOR '<br>') as itens
                FROM pedidos p
                JOIN itens_pedido ip ON p.id = ip.pedido_id
                JOIN produtos pr ON ip.produto_id = pr.id
                WHERE p.usuario_id = ?
            GROUP BY p.id
            ORDER BY p.data_pedido DESC";
$stmt_pedidos = $conn->prepare($sql_pedidos);
$stmt_pedidos->bind_param("i", $usuario_id);
$stmt_pedidos->execute();
$pedidos = $stmt_pedidos->get_result();

$sql_usuario = "SELECT foto_perfil, nome FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();
$usuario_dados = $stmt_usuario->get_result()->fetch_assoc();
$foto_perfil = $usuario_dados['foto_perfil'] ?? null;
$nome_usuario = $usuario_dados['nome'] ?? 'Usuário';
$stmt_usuario->close();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar_pagamento'])) {
    $tipo = trim($_POST['tipo']);
    $detalhes = trim($_POST['detalhes']);
    $erros = [];

    if (empty($tipo)) {
        $erros[] = "O tipo de pagamento é obrigatório.";
    }
    if (empty($detalhes)) {
        $erros[] = "Os detalhes do pagamento são obrigatórios.";
    }

    if (empty($erros)) {
        $sql = "INSERT INTO pagamentos (usuario_id, tipo, detalhes) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $usuario_id, $tipo, $detalhes);
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
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carteira - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="carteira">
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Mercado Bom Preço</h3>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item">Voltar ao Site</a>
        </nav>
    </div>

    <div class="content-wrapper">
        <header class="header-carteira">
            <span class="profile-name"><b><?php echo htmlspecialchars($nome_usuario); ?></b></span>
            <?php if ($foto_perfil): ?>
            <div class="profile-pic" style="background-image: url('<?php echo htmlspecialchars($foto_perfil); ?>');">
            </div>
            <?php else: ?>
            <div class="profile-pic" style="background-image: url('img/default-profile.jpg');"></div>
            <?php endif; ?>
        </header>

        <main class="main-carteira">
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

            <div class="cards-container">
                <div class="card">
                    <h2 class="card-title">Saldo Atual</h2>
                    <p class="card-description">Seu saldo disponível é:
                        <strong>€<?php echo number_format($saldo, 2, ',', '.'); ?></strong>
                    </p>
                </div>

                <div class="card">
                    <h2 class="card-title">Métodos de Pagamento</h2>
                    <?php if ($pagamentos->num_rows > 0): ?>
                    <ul class="card-list">
                        <?php while ($pagamento = $pagamentos->fetch_assoc()): ?>
                        <li class="card-item">
                            <strong><?php echo htmlspecialchars($pagamento['tipo']); ?>:</strong>
                            <?php echo htmlspecialchars($pagamento['detalhes']); ?>
                            <br><small>Adicionado em:
                                <?php echo date('d/m/Y H:i', strtotime($pagamento['data_cadastro'])); ?></small>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                    <?php else: ?>
                    <p class="card-description">Nenhum método de pagamento registrado.</p>
                    <?php endif; ?>
                    <form method="POST" action="carteira.php" class="card-form">
                        <label for="tipo">Tipo:</label>
                        <select id="tipo" name="tipo" required>
                            <option value="cartão de crédito">Cartão de Crédito</option>
                            <option value="cartão de débito">Cartão de Débito</option>
                            <option value="mbway">MBWay</option>
                            <option value="paypal">PayPal</option>
                        </select>
                        <label for="detalhes">Detalhes:</label>
                        <input type="text" id="detalhes" name="detalhes" placeholder="Ex.: **** **** **** 1234"
                            required>
                        <button type="submit" name="adicionar_pagamento">Adicionar</button>
                    </form>
                </div>

                <div class="card">
                    <h2 class="card-title">Histórico de Compras</h2>
                    <?php if ($pedidos->num_rows > 0): ?>
                    <table class="card-table">
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
                    <p class="card-description">Nenhum pedido registrado.</p>
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